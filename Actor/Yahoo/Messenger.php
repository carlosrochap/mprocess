<?php

class Actor_Yahoo_Messenger
    extends Actor_Yahoo
    implements Actor_Interface_Messenger
{
    const MAILBOX_INBOX = 'Inbox';
    const MAILBOX_SPAM  = '@B@Bulk';

    const MAILBOX_URL = '/mc/showFolder';

    const MESSAGE_URL         = '/mc/showMessage';
    const MESSAGE_COMPOSE_URL = '/mc/compose';

    const CONTACTS_URL = '/?VPC=contact_list&.src=prf&.rand=%d';


    /**
     * Returns a mailbox URL
     *
     * @param string $mailbox
     * @return Url|false
     */
    public function get_mailbox_url($mailbox=self::MAILBOX_INBOX)
    {
        $url = new Url($this->_mail_host . self::MAILBOX_URL);
        if ($url->is_valid) {
            $url->query = array(
                'ymv'   => 0,
                'fid'   => $mailbox,
                '.rand' => time(),
            );
            return $url;
        }
        return false;
    }

    /**
     * Returns a message URL
     *
     * @param array $msg Message details
     * @return Url|false
     */
    public function get_message_url(array $msg)
    {
        $url = new Url($this->_mail_host . self::MESSAGE_URL);
        if ($url->is_valid) {
            $url->query = array(
                'ymv'   => 0,
                'fid'   => $msg['mailbox'],
                'mid'   => $msg['msgid'],
                '.rand' => time(),
                'f'     => 1,
            );
            return $url;
        }
        return false;
    }


    public function get_messages($mailbox=self::MAILBOX_INBOX)
    {
        $this->log("Fetching new {$mailbox} messages");

        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        $this->get($this->get_mailbox_url($mailbox));
        $this->_connection->follow_refresh = $old_follow_refresh;
        $this->_dump("{$mailbox}.html");
        if (preg_match_all(
            '@<tr class="msgnew"><td><b>Unread</b></td><td class="fixwidth"><input type="checkbox" name="mid" value="(?P<msgid>[^"]+)">.+?<td title="(?P<from>[^"]+)"><div>.+?>(?P<subject>[^<]+)</a></h2>@i',
            $this->_response,
            $msgs,
            PREG_SET_ORDER
        )) {
            foreach ($msgs as &$msg) {
                $msg = array(
                    'mailbox' => &$mailbox,
                    'msgid'   => $msg['msgid'],
                    'from'    => html_entity_decode($msg['from'], ENT_QUOTES),
                    'subject' => html_entity_decode($msg['subject'], ENT_QUOTES),
                );
            }
            return $msgs;
        }

        $this->log('No messages found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Fetches a message content
     *
     * @param array $msg Message details
     * @return string|false
     */
    public function get_message(array $msg)
    {
        $this->log("Fetching message {$msg['msgid']}");

        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        $this->get($this->get_message_url($msg));
        $this->_connection->follow_refresh = $old_follow_refresh;
        $this->_dump("{$msg['mailbox']}." . sha1($msg['msgid']) . '.html');
        return preg_match(
            '#<div id=(?:yiv\d+|"showMessagePage")[^>]*>([\s\S]+)</div><script[^>]+>hasEML#',
            $this->_response,
            $m
        )
            ? trim($m[1])
            : $this->_response;
    }

    public function add_contacts($contacts)
    {
        if (!is_array($contacts)) {
            $contacts = array($contacts);
        }

        $this->log('Adding contacts ' . serialize($contacts));

        if (false === strpos($this->_response, 'name="yabForm"')) {
            $this->get(self::CONTACTS_HOST . sprintf(self::CONTACTS_URL, time()));
            $this->_dump('contacts.landing.html');
            if (!preg_match(
                '#href="([^"]+)"[^>]+id="qa_addcont1"#',
                $this->_response,
                $m
            )) {
                throw new Actor_Yahoo_Exception(
                    'Add contact form URL not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $this->get(self::CONTACTS_HOST . html_entity_decode($m[1], ENT_QUOTES));
            $this->_dump('contacts.form.html');
        }

        $added = array();
        foreach ($contacts as $contact) {
            if (!$this->get_form('name', 'yabForm')) {
                throw new Actor_Yahoo_Exception(
                    'Add contact form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            unset(
                $this->_form['submit[action_cancel]'],
                $this->_form['submit[action_save]']
            );
            list($screen_name, $domain) = explode('@', $contact, 2);
            $this->_form['fields[F:3::-4:0:]'] =  $screen_name;
            $this->_form['fields[F:4::-5:0:]'] = $contact;
            $this->submit();
            $this->_dump("contacts.{$contact}.submit.html");
            if (false !== stripos($this->_response, 'was added')) {
                $added[] = $contact;
            }
        }
        return $added
            ? $added
            : false;
    }

    public function remove_message($msg)
    {
        $this->log("Removing message {$msg['msgid']}");

        $content = $this->get_message($msg, false);
        if ($content) {
            if (!$form = Html_Form::get(
                $content,
                $this->get_message_url($msg),
                'name',
                'showMessageForm'
            )) {
                throw new Actor_Yahoo_Exception(
                    'Message remove form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $form['top_bpress_delete'] = 'Delete';
            $form->submit($this->_connection);
            return true;
        }
        return false;
    }

    public function send($recipient, $msg, $subj='')
    {
        $this->log("Sending a message to {$recipient}");
        $this->log("{$subj}: {$msg}", Log_Abstract::LEVEL_DEBUG);

        $this->get($this->_mail_host . self::MESSAGE_COMPOSE_URL, array(
            'ymv'   => 0,
            '.rand' => time(),
        ));
        $this->_dump('compose.form.html');
        if (!$this->get_form('id', 'Compose')) {
            throw new Actor_Yahoo_Exception(
                'Message compose form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        unset(
            $this->_form['action_msg_savedraft'],
            $this->_form['action_cancel_compose']
        );
        $this->_form['to'] = $recipient;
        $this->_form['jsonEmails'] = json_encode(array($recipient => false));
        $this->_form['Subj'] = $subj;
        $this->_form['Content'] = nl2br($msg);
        $this->_dump("compose.{$recipient}.submit.txt", $this->_form->to_array());
        $this->submit();
        $this->_dump("compose.{$recipient}.submit.html");
        if (
            (false !== strpos($this->_response, '<h2>Message Sent</h2>')) ||
            (false !== strpos($this->_response, 'has been sent'))
        ) {
            return true;
        }

        $this->log('Failed sending a message',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
