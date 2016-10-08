<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage PocztaPl
 */
class Actor_PocztaPl_Messenger
    extends Actor_PocztaPl
    implements Actor_Interface_Messenger
{
    const MAILBOX_INBOX = 'Inbox';
    const MAILBOX_SPAM  = 'Spam';

    const MAILBOX_URL = '/mail/showmail.php';
    const MESSAGE_URL = '/mail/reademail.php';


    /**
     * @see Actor_Interface_Messenger::send()
     */
    public function send($recipient, $msg, $subj='')
    {
        return false;
    }

    public function get_messages($mailbox=self::MAILBOX_INBOX)
    {
        $this->log('Fetching messages');

        $this->get(self::HOST . self::MAILBOX_URL, array(
            'Folder' => &$mailbox
        ));
        $this->_dump("mailbox.{$mailbox}.html");
        if (preg_match_all(
            '#<FONT class="itemb?"><img id="stat(?P<msgid>[^"]+)"[^>]+>&nbsp;\s+(?P<subject>.+?)\s+</FONT>\s+</TD>\s+<TD[^>]+>&nbsp;<FONT[^>]+>(?P<from>.+?)</FONT>#',
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
                    'subject' => html_entity_decode($msg['subject'], ENT_QUOTES)
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

        $this->get(self::HOST . self::MESSAGE_URL, array(
            'id'     => $msg['msgid'],
            'folder' => $msg['mailbox'],
            'cache'  => ''
        ), self::HOST . self::MAILBOX_URL);
        $this->_dump("msg.{$msg['mailbox']}.{$msg['msgid']}.html");
        return preg_match(
            '#<font class="swb">([\s\S]+?)</font>\s+<br>\s+</td>\s+</tr>\s+</table>\s+</form>#',
            $this->_response,
            $m
        )
            ? trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES))
            : $this->_response;
    }
}
