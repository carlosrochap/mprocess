<?php
/**
 * @package Actor
 */

/**
 * Base Google actor
 *
 * @package Actor
 * @subpackage Google
 */
class Actor_Google extends Actor_Http_Abstract
{
    const HOST        = 'http://www.google.com';
    const SECURE_HOST = 'https://www.google.com';
    const NEWS_HOST   = 'http://news.google.com';

    const LOG_IN_URL = '/accounts/Login?hl=en&continue=http://www.google.com/';


    /**
     * Logs into Google-powered service
     *
     * @param string $host    Service host
     * @param string $user_id Service user ID
     * @param string $pass    Service user password
     * @return bool
     */
    protected function _login($host, $user_id, $pass)
    {
        $this->log("Logging in as {$user_id}:{$pass}@{$host}");

        $this->get(self::HOST);
        $this->_dump('homepage.html');
        $this->get($host);
        $this->_dump('login.form.html');
        if (!$this->get_form('id', 'gaia_loginform')) {
            throw new Actor_Google_Exception(
                'Login form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['Email'] = $user_id;
        $this->_form['Passwd'] = $pass;
        $this->_dump('login.submit.txt', $this->form->to_array());
        $this->submit();
        $this->_dump('login.submit.html');
        if (false === strpos($this->_response, 'gaia_loginform')) {
            $this->user_id = $user_id;
            return true;
        } else if (false !== strpos($this->_response, 'errormsg_0_Passwd')) {
            throw new Actor_Google_Exception(
                'Invalid user ID or password',
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        $this->log('Login failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }


    /**
     * @see Actor_Http_Abstract::login()
     */
    public function login($user_id, $pass)
    {
        return false;
    }

    public function __call($method, $args)
    {
        $response = parent::__call($method, $args);
        if (503 == $this->_connection->status_code) {
            throw new Actor_Google_Exception(
                'Access denied, proxy might be banned',
                Actor_Exception::PROXY_BANNED
            );
        }
        return $response;
    }
}
