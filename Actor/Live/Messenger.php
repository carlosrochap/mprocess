<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Live
 */
class Actor_Live_Messenger
    extends Actor_Live
    implements Actor_Interface_Messenger
{
    const MAILBOX_INBOX = '00000000-0000-0000-0000-000000000001';
    const MAILBOX_SPAM  = '00000000-0000-0000-0000-000000000005';

    const MAIL_HOST = 'http://mail.live.com';

    const MAILBOX_URL = '/mail/mail.fpp';


    /**
     * Fetches mail page, returns FPP tokens
     *
     * @return array|false
     */
    protected function _fetch_mail_page()
    {
        $this->log('Fetching mail page');

        $this->get(self::MAIL_HOST);
        $this->_dump('homepage.html');
        if (!preg_match(
            '#getUiFrame\(\)\.src = \'([^\']+)\'#',
            $this->_response,
            $m
        )) {
            throw new Actor_Live_Exception(
                'Mail landing page URL not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->get(stripcslashes($m[1]));
        $this->_dump('mail.landing.html');
        if (false !== strpos(
            $this->_response,
            'id="' . self::MAILBOX_INBOX . '"'
        )) {
            $url = new Url($this->_connection->last_url);
            $tokens = array(
                'mt'   => $this->_connection->get_cookie('mt'),
                'host' => "{$url['scheme']}://{$url['host']}",
            );
            foreach (array('SessionId', 'AuthUser') as $k) {
                if (!preg_match(
                    '#' . preg_quote($k) . ':"([^"]+)"#',
                    $this->_response,
                    $m
                )) {
                    throw new Actor_Live_Exception(
                        "FPP token {$k} not found",
                        Actor_Exception::PROXY_BANNED
                    );
                }
                $tokens[$k] = html_entity_decode($m[1], ENT_QUOTES);
            }
            return $tokens;
        }

        $this->log('Failed fetching profile page',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }


    /**
     * @see Actor_Interface_Messenger::send()
     */
    public function send($recipient, $msg, $subj='')
    {
        return false;
    }

    /**
     * Fetches unreaded messages from particular mailbox
     *
     * @param string $mailbox Mailbox UUID
     * @return array|false
     */
    public function get_messages($mailbox=self::MAILBOX_INBOX)
    {
        $this->log('Fetching messages');

        $tokens = $this->_fetch_mail_page();
        if (!$tokens) {
            return false;
        }

        $this->ajax($tokens['host'] . self::MAILBOX_URL, array(
            'cn' => 'Microsoft.Msn.Hotmail.Ui.Fpp.MailBox',
            'mn' => 'GetInboxData',
            'd'  => 'true,false,true,{"' . $mailbox . '",0,0,Date,false,null,null,1,1,false,null,-1,-1,Off},false,null',
            'mt' => $tokens['mt'],
            'v'  => 1,
        ), array(
            'cnmn' => 'Microsoft.Msn.Hotmail.Ui.Fpp.MailBox.GetInboxData',
            'ptid' => 0,
            'a'    => $tokens['SessionId'],
            'au'   => $tokens['AuthUser'],
        ));
        $this->_dump("mailbox.{$mailbox}.js");
        $i = strpos($this->_response, '"\r\n');
        if (false === $i) {
            throw new Actor_Live_Exception(
                'Invalid response',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_response = stripcslashes(substr($this->_response, $i + 1));
        if (preg_match_all(
            '#<tr[^>]+id="(?P<uuid>[-\da-f]+)"[^>]+mad="(?P<mad>[^"]+)"[^>]*>.+?<td class="Frm">(?P<from>.+?)</td>.+?<td class="Sbj">(?P<subject>.+?)</td>#',
            $this->_response,
            $m
        )) {
            $msgs = array();
            foreach ($m['uuid'] as $k => $uuid) {
                $msgs[] = array(
                    'mailbox' => $mailbox,
                    'uuid'    => $uuid,
                    'mad'     => $m['mad'][$k],
                    'from'    => html_entity_decode(strip_tags($m['from'][$k]), ENT_QUOTES),
                    'subject' => html_entity_decode(strip_tags($m['subject'][$k]), ENT_QUOTES),
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
        $this->log("Fetching message #{$msg['uuid']}");

        $tokens = $this->_fetch_mail_page();
        if (!$tokens) {
            return false;
        }

        $this->ajax($tokens['host'] . self::MAILBOX_URL, array(
            'cn' => 'Microsoft.Msn.Hotmail.Ui.Fpp.MailBox',
            'mn' => 'GetInboxData',
            'd'  => 'false,false,false,null,true,{"' . $msg['uuid'] . '","' . $msg['mailbox'] . '",' . ((self::MAILBOX_SPAM == $msg['mailbox']) ? 'true' : 'false') . ',false,-1,null,{"' . addcslashes($msg['mad'], '|') . '"},Date,false,4,true}',
            'mt' => $tokens['mt'],
            'v'  => 1,
        ), array(
            'cnmn' => 'Microsoft.Msn.Hotmail.Ui.Fpp.MailBox.GetInboxData',
            'ptid' => 0,
            'a'    => $tokens['SessionId'],
            'au'   => $tokens['AuthUser'],
        ));
        $this->_dump("msg.{$msg['uuid']}.js");
        $i = strpos($this->_response, '"\r\n');
        if (false === $i) {
            throw new Actor_Live_Exception(
                'Invalid response',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_response = stripcslashes(substr(
            $this->_response,
            $i + 1
        ));
        return $this->_response;
    }
}
