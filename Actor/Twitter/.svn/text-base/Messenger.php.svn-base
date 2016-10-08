<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Twitter
 */
class Actor_Twitter_Messenger
    extends Actor_Twitter
    implements Actor_Interface_Messenger
{
    const MSG_POST_URL        = '/direct_messages/create';
    const RECIPIENTS_LIST_URL = '/direct_messages/recipients_list';

    const INBOX_URL = '/inbox';


    protected $_available_recipients = array();


    protected function _get_available_recipients($auth_token)
    {
        if (empty($this->_available_recipients[$this->_user_id])) {
            $response = json_decode($this->_connection->ajax(
                self::HOST . self::RECIPIENTS_LIST_URL,
                null,
                array(
                    'authenticity_token' => $auth_token,
                    'twttr'              => 'true',
                )
            ), true);
            $this->_dump('recipients.' . time() . '.js', $response);
            if ($response) {
                $this->_available_recipients[$this->_user_id] = array();
                foreach ($response as &$a) {
                    $this->_available_recipients[$this->_user_id][$a[0]] = $a[1];
                }
            }
        }
        return !empty($this->_available_recipients[$this->_user_id])
            ? $this->_available_recipients[$this->_user_id]
            : false;
    }


    public function init()
    {
        $this->_available_recipients = array();

        return parent::init();
    }

    /**
     * @see Actor_Interface_Messenger::send()
     */
    public function send($recipient, $msg, $subj='')
    {
        $this->log("Sending a message to {$recipient}");

        $this->get(self::HOST . self::INBOX_URL);
        $this->_dump("{$recipient}.form.html");

        $token = $this->_extract_auth_token();
        if (!$token) {
            return false;
        }

        $recipients = $this->_get_available_recipients($token);
        $twid = $recipients
            ? array_search(strtolower($recipient), $recipients)
            : false;
        if (!$twid) {
            $this->log('Recipient is not messageable',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } 

        $this->ajax(self::HOST . self::MSG_POST_URL, array(
            'authenticity_token' => $token,
            'text'               => substr($msg, 0, 140),
            'user[id]'           => $twid,
            'twttr'              => 'true',
        ));
        $this->_dump("{$recipient}.submit.js");

        if (false !== strpos($this->_response, 'has been sent')) {
            return true;
        }

        $this->log('Failed sending a message',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Returns current user's direct messages inbox
     *
     * @return array|false
     */
    public function get_messages()
    {
        $this->log('Fetching direct messages received');

        $this->get(self::HOST . '/');
        $this->_dump('inbox.html');

        $token = $this->_extract_auth_token();
        if (!$token) {
            return false;
        }

        $this->_response = stripcslashes($this->ajax(
            self::HOST . self::INBOX_URL,
            null,
            array('authenticity_token' => $token)
        ));
        $this->_dump($this->get_user_id() . '.inbox.js');

        if (!preg_match_all(
            '#id="direct_message_(?P<msg_id>\d+)">(?P<msg>[\s\S]+?)<ul class="actions#',
            $this->_response,
            $m,
            PREG_SET_ORDER
        )) {
            return false;
        }

        $msgs = array();
        foreach ($m as &$msg) {
            if (preg_match(
                '#screen-name">(?P<user_id>[^<]+)<[\s\S]+?"entry-content">(?P<content>[^>]+)</span>#',
                $msg['msg'],
                $msg['msg']
            )) {
                $msgs[(int)$msg['msg_id']] = array(
                    'recipient' => $msg['msg']['user_id'],
                    'message'   => html_entity_decode(trim($msg['msg']['content']), ENT_QUOTES),
                );
            }
        }
        $this->log('INBOX: ' . serialize($msgs),
                   Log_Abstract::LEVEL_DEBUG);
        return count($msgs)
            ? $msgs
            : false;
    }
}
