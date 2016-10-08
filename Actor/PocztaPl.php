<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage PocztaPl
 */
class Actor_PocztaPl extends Actor_Http_Abstract
{
    const DOMAIN = 'poczta.pl';

    const HOST        = 'http://www.poczta.pl';
    const SECURE_HOST = 'https://www.poczta.pl';

    const LOG_IN_URL  = '/mail/atmail.php';
    const LOG_OUT_URL = '/mail/index.php?func=logout';


    /**
     * @see Actor_Interface::login()
     */
    public function login($user_id, $pass)
    {
        $this->log("Logging in as {$user_id}:{$pass}");

        $this->get(self::HOST);
        $this->_dump('login.form.html');
        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        $this->post(self::HOST . self::LOG_IN_URL, http_build_query(array(
            'LoginType' => 'simple',
            'NewWindow' => 0,
            'password'  => &$pass,
            'pop3host'  => self::DOMAIN,
            'powitanie' => 1,
            'username'  => &$user_id,
        )));
        $this->_dump('login.submit.html');
        $this->_connection->follow_refresh = $old_follow_refresh;
        if (preg_match(
            '#location\.href=\'(showmail[^\']+)#',
            $this->_response,
            $m
        )) {
            $url = stripcslashes($m[1]);
            $this->log("Redirecting to {$url}",
                       Log_Abstract::LEVEL_DEBUG);
            $this->get(self::HOST . "/mail/{$url}");
            $this->_dump('login.redirect.html');
            $this->user_id = $user_id;
            return true;
        } else if (false !== mb_strpos($this->_response, 'Nie ma takiego konta')) {
            throw new Actor_PocztaPl_Exception(
                'Invalid user ID or password',
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        $this->log('Login failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function logout()
    {
        if ($this->get_user_id()) {
            $this->get(self::HOST . self::LOG_OUT_URL);
        }
        return parent::logout();
    }
}
