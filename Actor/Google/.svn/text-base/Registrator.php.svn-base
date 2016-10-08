<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Google
 */
class Actor_Google_Registrator
    extends Actor_Google
    implements Actor_Interface_Registrator
{
    const REG_FORM_URL     = '/accounts/NewAccount?continue=http%3A%2F%2Fwww.google.com%2F&hl=en';
    const REG_RATE_PWD_URL = '/accounts/RatePassword';


    /**
     * @ignore
     */
    protected function _rate_pass()
    {
        $this->log('Rating password');

        $query = array(
            'Email'  => $this->_form['Email'],
            'Passwd' => $this->_form['Passwd']
        );
        foreach (array('FirstName', 'LastName') as $k) {
            $query[$k] = isset($this->_form[$k])
                ? $this->_form[$k]
                : 'undefined';
        }
        return (int)$this->_connection->post(
            self::SECURE_HOST . self::REG_RATE_PWD_URL,
            http_build_query($query),
            null,
            self::SECURE_HOST . self::REG_FORM_URL
        );
    }


    /**
     * @see Actor_Interface_Registrator::register()
     */
    public function register($email, $pass)
    {
        $this->log("Registering {$email}:{$pass}");

        $details = $this->call_process_method('get_profile_details');
        if (!$details) {
            $this->log('Profile details not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->get(self::SECURE_HOST . self::LOG_IN_URL);
        $this->_dump('landing.html');
        $this->get(self::SECURE_HOST . self::REG_FORM_URL);
        $this->_dump('form.html');
        for ($retry = self::RETRY_COUNT; $retry; $retry--) {
            if (!$this->get_form('id', 'createaccount')) {
                throw new Actor_Google_Exception(
                    'Registration form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $captcha = $this->_decode_captcha($this->_form['newaccounturl']);
            if (!$captcha) {
                continue;
            } else {
                $this->_form['newaccountcaptcha'] = $captcha[1];
            }

            unset($this->_form['smhck']);
            $this->_form['Email'] = $email;
            $this->_form['Passwd'] = $this->_form['PasswdAgain'] = $pass;
            $this->_dump("submit.{$retry}.txt", $this->_form->to_array());
            $this->submit();
            $this->_dump("submit.{$retry}.html");
            if (false === strpos(
                $this->_response,
                'createaccount'
            )) {
                $this->user_id = $email;
                return true;
            }
            if ($captcha && (false !== strpos(
                $this->_response,
                "characters you entered didn't match"
            ))) {
                $this->_get_captcha_decoder()->report($captcha[0], false);
            }
            if (false !== strpos(
                $this->_response,
                'id="errormsg_0_Email"'
            )) {
                throw new Actor_Google_Exception(
                    "{$email} is already taken",
                    Actor_Exception::INVALID_CREDENTIALS
                );
            } else if (false !== strpos($this->_response, 'smschallenge')) {
                throw new Actor_Google_Exception(
                    'SMS verification required',
                    Actor_Exception::SMS_VERIFICATION_REQUIRED
                );
            }
        }

        $this->log('Registration failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
