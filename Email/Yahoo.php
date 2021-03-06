<?php
/**
 * @package Email
 */

/**
 * @package Email
 * @subpackage Yahoo
 */
class Email_Yahoo extends Email_Abstract
{
    /**
     * @see Email_Interface::login()
     */
    public function login($username, $pass)
    {
        $this->logout();

        if (!$this->_connection) {
            $this->_connection = new Connection_Curl();
            $this->_connection->proxy = $this->_proxy
                ? $this->_proxy
                : Pool_Factory::factory('Proxy')->get();
            $this->_connection->init();
        }

        $this->_client = Actor_Factory::factory('Yahoo_Messenger', $this->_log);
        $this->_client->connection = $this->_connection;

        try {
            return $this->_client->login($username, $pass);
        } catch (Actor_Exception $e) {
            if (Actor_Yahoo_Exception::INVALID_CREDENTIALS == $e->getCode()) {
                throw new Email_Exception(
                    'Invalid e-mail service username/password',
                    Email_Exception::INVALID_CREDENTIALS
                );
            }
        }
        return false;
    }

    /**
     * Sends a message
     *
     * @param string $recipient Recipient's e-mail address
     * @param string $msg       Message content
     * @param string $subj      Optional subject
     * @return bool
     */
    public function send($recipient, $msg, $subj='')
    {
        if (!$this->_client) {
            throw new Email_Exception(
                'E-mail client not found'
            );
        } else {
            return $this->_client->send($recipient, $msg, $subj);
        }
    }
}
