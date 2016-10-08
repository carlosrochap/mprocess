<?php

class Actor_Drogi extends Actor_Http_Abstract
{
    const DOMAIN = 'drogi.com';

    const HOST = 'http://drogi.mail.everyone.net';

    const SVC_MENU_URL = '/email/scripts/serviceMenu.pl';

    const LOG_OUT_URL = '/email/scripts/logout.pl';


    protected function _extract_tokens($response=null)
    {
        return preg_match('#EV1=(\d+)#', (
            (null === $response)
                ? $this->_response
                : $response
        ), $m)
            ? array('EV1' => $m[1])
            : false;
    }


    public function login($user_id, $pass)
    {
        $this->log("Logging in as {$user_id}:{$pass}");

        $this->get(self::HOST . '/');
        $this->_dump('login.form.html');
        if (!$this->get_form('name', 'myForm')) {
            Actor_Drogi_Exception(
                'Login form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['loginName'] = $user_id;
        $this->_form['user_pwd'] = $pass;
        $this->_form['store_pwd'] = 'on';
        $this->submit();
        $this->_dump('login.submit.html');
        if ((false !== strpos(
            $this->_response,
            '>Take as many free offers as you like.<'
        )) && $this->get_form('action', 'coReg1.pl')) {
            $this->log('Skipping free offers');
            $this->submit();
            $this->_dump('login.submit.skip-free-offers.html');
        }
        if (preg_match('#\stop\.location = \'([^\']+)#', $this->_response, $m)) {
            $this->get(self::HOST . html_entity_decode($m[1], ENT_QUOTES));
            $this->_dump('login.submit.redirect.html');
        }
        $a = array_map('strtolower', explode('@', $user_id, 2));
        if (false !== stripos($this->_response, " owner:'{$a[0]}'")) {
            $this->user_id = $user_id;
            $method = 'confirm_membership';
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
            return true;
        } else if (false !== strpos($this->_response, 'Invalid Login Name')) {
            throw new Actor_Drogi_Exception(
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
        if ($this->_connection) {
            $this->get(self::HOST . self::LOG_OUT_URL);
        }
        return parent::logout();
    }
}
