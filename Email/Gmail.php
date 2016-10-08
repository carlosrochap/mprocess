<?php
/**
 * @package Email
 */

/**
 * Gmail e-mail service provider
 *
 * @property string|Connection_Proxy $proxy Proxy to use/in use
 *
 * @package Email
 * @subpackage Google
 */
class Email_Gmail extends Email_Abstract
{
    //const SERVER = '{74.125.113.109:995/pop3/ssl/notls/novalidate-cert}';
    const SERVER = '{74.125.91.109:993/imap/ssl/novalidate-cert}';


    protected $_username = '';
    protected $_pass     = '';


    /**
     * @see Email_Interface::close()
     */
    public function close()
    {
        $this->logout();

        return parent::close();
    }

    /**
     * @ignore
     */
    protected function _open($ref=self::SERVER)
    {
        $mb = @imap_open(
            $ref,
            $this->_username,
            $this->_pass,
            ($this->is_verbose
                ? OP_DEBUG
                : 0),
            1
        );
        if (false === $mb) {
            if ('Too many login failures' == imap_last_error()) {
                throw new Email_Exception(
                    'Invalid e-mail service username/password',
                    Email_Exception::INVALID_CREDENTIALS
                );
            }
        }
        return $mb;
    }

    /**
     * @see Email_Interface::login()
     */
    public function login($username, $pass)
    {
        $this->logout();

        list($this->_username, $this->_pass) = array($username, $pass);
        return true;
    }

    /**
     * @see Email_Interface::logout()
     */
    public function logout()
    {
        return $this;
    }

    /**
     * @ignore
     */
    protected function _get_mailboxes()
    {
        $mb = $this->_open();
        if (false === $mb) {
            return false;
        } else if ($boxes = @imap_list($mb, self::SERVER, '*')) {
            @imap_close($boxes);
            return array_map('imap_utf7_decode', $boxes);
        } else if (!imap_last_error()) {
            @imap_close($boxes);
            return false;
        }
    }

    /**
     * @see Email_Interface::get_message()
     */
    public function get_message($from, $subj=null, $content_regex=null)
    {
        $result = false;

        $criteria = 'FROM "' . $from . '"';
        #if ($subj) {
        #    $criteria .= ' SUBJECT "' . $subj . '"';
        #}

        foreach ($this->_get_mailboxes() as $mailbox) {
            if ($mb = $this->_open($mailbox)) {
                $messages = @imap_search($mb, $criteria, SE_UID);
                if (!empty($messages)) {
                    foreach ($messages as &$uid) {
                        $msg = @imap_body($mb, $messages[0], FT_UID);
                        if ($msg) {
                            $result = $content_regex
                                ? (preg_match($content_regex, $msg, $a)
                                    ? html_entity_decode($a[1], ENT_QUOTES)
                                    : false)
                                : $msg;
                        }
                        if ($result) {
                            break;
                        }
                    }
                }
                imap_close($mb);
                if ($result) {
                    break;
                }
            }
        }

        return $result;
    }
}
