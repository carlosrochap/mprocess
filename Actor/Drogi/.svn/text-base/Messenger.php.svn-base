<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Drogi
 */
class Actor_Drogi_Messenger
    extends Actor_Drogi
    implements Actor_Interface_Messenger
{
    const MAILBOX_INBOX = 'INBOX';
    const MAILBOX_SPAM  = 'Spam';

    const MAILBOX_URL = '/email/scripts/main.pl';
    const MSG_URL     = '/email/scripts/view.pl';


    /**
     * @see Actor_Interface_Messenger::send()
     */
    public function send($recipient, $msg, $subj='')
    {
        return false;
    }

    public function confirm_membership()
    {
        $this->log('Confirming membership');

        $msgs = $this->get_messages(self::MAILBOX_INBOX);
        if (!$msgs) {
            return false;
        }

        foreach ($msgs as $msg) {
            if (('asdf' == $msg['subject']) && preg_match(
                '#<a href="([^"]+)"[^>]+>confirm your membership#',
                $this->get_message($msg),
                $m
            )) {
                $this->get(html_entity_decode($m[1], ENT_QUOTES));
                $this->_dump('confirm.html');
                if (false !== stripos($this->_connection->last_url, 'thankyou')) {
                    return true;
                }
            }
        }

        $this->log('Failed confirming membership',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function get_messages($mailbox=self::MAILBOX_INBOX)
    {
        $this->log("Fetching {$mailbox} messages");

        $this->post(self::HOST . self::MAILBOX_URL, http_build_query(array(
            'folder' => $mailbox
        )));
        $this->_dump("mailbox.{$mailbox}.html");
        if (preg_match_all(
            '#<td><b>(?P<from>[^<]+)&nbsp;&nbsp;</b></td>\s+<td><a href="Javascript: View\(\'(?P<msgid>\d+)\'\)"><b>(?P<subject>.+?)(?:\.{3})?</b></a>#',
            $this->_response,
            $m,
            PREG_SET_ORDER
        )) {
            $msgs = array();
            foreach ($m as $msg) {
                $msgs[] = array(
                    'mailbox' => $mailbox,
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
     * @param array $msg
     * @return string|false
     */
    public function get_message(array $msg)
    {
        $this->log("Fetching message {$msg['msgid']}");

        $tokens = $this->_extract_tokens();
        if (!$tokens) {
            return false;
        }

        $this->post(self::HOST . self::MSG_URL, http_build_query(array(
            'command'        => '',
            'doDelete'       => '',
            'doEmpty'        => '',
            'doMove'         => '',
            'doNewSort'      => '',
            'doSpam'         => '',
            'doSpamImproved' => '',
            'filefolder'     => '',
            'filefolder2'    => '',
            'folder'         => &$msg['mailbox'],
            'goToMenu'       => '',
            'index'          => 0,
            'isSearch'       => 0,
            'jumpList'       => '',
            'jumpList2'      => '',
            'mid'            => &$msg['msgid'],
            'midseq'         => &$msg['msgid'],
            'movefolder'     => '',
            'page'           => 'summary',
            'showDraft'      => 0,
            'sort'           => 'date',
            'sortdir'        => 0,
        )), $tokens);
        $this->_dump("msg.{$msg['mailbox']}.{$msg['msgid']}.html");
        return preg_match('#<form [^>]+>([\s\S]+)</form>#', $this->_response, $m)
            ? trim(html_entity_decode($m[1], ENT_QUOTES))
            : $this->_response;
    }
}
