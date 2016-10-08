<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage YouTube
 */
class Actor_YouTube_Registrator
    extends Actor_YouTube
    implements Actor_Interface_Registrator
{
    const SIGN_UP_URL = '/create_account';

    const CHECK_USERNAME_URL = '/user_ajax';


    /**
     * Checks username availability
     *
     * @param string $username
     * @return bool
     */
    public function is_username_available($username)
    {
        $this->log("Checking {$username} availability");

        $s = $this->_connection->get(
            self::HOST . self::CHECK_USERNAME_URL,
            array(
                'action_check_username' => '',
                'user'                  => &$username,
                'suggest'               => 1
            ),
            self::HOST . self::SIGN_UP_URL
        );
        $this->_dump("check.{$username}.html", $s);
        return (false === strpos($s, 'not available'));
    }

    /**
     * @see Actor_Interface_Registrator::register()
     */
    public function register($email, $pass)
    {
        $this->log("Registering {$email}:{$pass}");

        $this->get(self::HOST . self::SIGN_UP_URL, 'next=None');
        $this->_dump('form.html');
        if (!$this->get_form('id', 'signupForm')) {
            throw new Actor_YouTube_Exception(
                'Registration form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $details = $this->call_process_method('get_profile_details');
        if (!$details) {
            $this->log('Profile details not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $k = 'user_id';
        $details[$k] = $details['username'];
        for ($i = self::RETRY_COUNT; $i; $i--) {
            if ($this->is_username_available($details[$k])) {
                break;
            } else {
                $details[$k] = Pool_Generator::generate_username($details[$k]);
            }
        }
        if (!$i) {
            $this->log('Available user ID not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $this->_form['username'] = $details[$k];
        }

        $k = 'country';
        if (!empty($details[$k])) {
            $this->_form[$k] = $details[$k];
            if ($details['postal_code']) {
                $this->_form['postal_code'] = $details['postal_code'];
            }
        }

        $k = 'birthday';
        if (!empty($details['birthday'])) {
            list(
                $this->_form["{$k}_yr"],
                $this->_form["{$k}_mon"],
                $this->_form["{$k}_day"]
            ) = $details[$k];
        }

        $this->_form['gender'] = strtolower($details['gender']);
        $this->_dump('prepare.txt', $this->_form->to_array());
        $this->submit();
        $this->_dump('prepare.html');
        for ($i = self::RETRY_COUNT; $i; $i--) {
            if (!$this->get_form('id', 'createaccount')) {
                throw new Actor_YouTube_Exception(
                    'Google registration form not found',
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
            $this->_dump("submit.{$i}.txt", $this->_form->to_array());
            $this->submit();
            $this->_dump("submit.{$i}.html");
            if (false !== strpos($this->_response, 'success')) {
                $this->user_id = $details['user_id'];
                return true;
            }
            if (false === strpos(
                $this->_response,
                "characters you entered didn't match"
            )) {
                $this->_get_captcha_decoder()->report($captcha[0], false);
            }
            if (false !== strpos($this->_response, 'id="errormsg_0_Email"')) {
                throw new Actor_YouTube_Exception(
                    "{$email} is already taken",
                    Actor_Exception::INVALID_CREDENTIALS
                );
            }
        }

        $this->log('Registration failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
