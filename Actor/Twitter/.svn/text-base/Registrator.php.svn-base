<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Twitter
 */
class Actor_Twitter_Registrator
    extends Actor_Twitter
    implements Actor_Interface_Registrator
{
    /**
     * Generates random Twitter user ID based on profile username
     *
     * @param string $username
     * @return string
     */
    protected function _generate_user_id($username)
    {
        $separators = array('', '_', '-', '.');
        $transformers = array('trim', 'ucfirst', 'strtolower', 'strtoupper');
        return substr(implode($separators[array_rand($separators)], array_map(
            $transformers[array_rand($transformers)],
            str_split($username, rand(1, (int)(strlen($username) / 2)))
        )), 0, 14);
    }


    /**
     * @see Actor_Interface_Registrator::register()
     */
    public function register($email, $pass)
    {
        $this->log("Registering {$email}:{$pass}");

        $details = $this->call_process_method(
            'get_profile_details',
            $email
        );
        if (!$details) {
            $this->log('Profile details not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->get(self::HOST . self::REGISTER_URL, null, self::HOST . '/');
        $this->_dump('form.html');

        foreach (array('first_name', 'last_name') as $k) {
            $details[$k] =
                $this->call_process_method("get_{$k}", $details['gender']);
        }

        $user_id = $this->_generate_user_id($details['username']);

        for ($captcha = false, $i = self::RETRY_COUNT; $i; $i--) {
            if (!$this->get_form('action', self::REGISTER_SUBMIT_URL)) {
                $this->log('Registration form not found',
                           Log_Abstract::LEVEL_ERROR);
                break;
            }

            if (!$this->is_credential_available(
                'email',
                $email,
                $this->_form['authenticity_token']
            )) {
                throw new Actor_Twitter_Exception(
                    "E-mail {$email} already taken",
                    Actor_Twitter_Exception::INVALID_CREDENTIALS
                );
            }

            for ($j = self::RETRY_COUNT; $j; $j--) {
                if ($this->is_credential_available(
                    'username',
                    $user_id,
                    $this->_form['authenticity_token']
                )) {
                    break;
                }
                if (!$j) {
                    $this->log('Not valid usernames',
                               Log_Abstract::LEVEL_ERROR);
                    break 2;
                }
                $user_id =
                    Pool_Generator::generate_username($user_id, true, 14);
            }

            $this->_form['user[name]'] =
                "{$details['first_name']} {$details['last_name']}";
            $this->_form['user[screen_name]'] = $user_id;
            $this->_form['user[user_password]'] = $pass;
            $this->_form['user[email]'] = $email;
            $this->_form['user[discoverable_by_email]'] =
                $this->_form['user[send_email_newsletter]'] = 0;
            unset($this->_form['commit']);

            // Break captcha if present
            if (preg_match(
                '#/challenge\?k=([^&]+)#',
                $this->_response,
                $key
            )) {
                $key = html_entity_decode($key[1], ENT_QUOTES);
                for ($captcha = false, $j = self::RETRY_COUNT; $j && !$captcha; $j--) {
                    $captcha = $this->_decode_recaptcha($key, null, true);
                }
                if (!$captcha) {
                    break;
                }
            }

            $this->submit(self::SECURE_HOST . self::REGISTER_URL);
            $this->_dump("submit.{$i}.html");

            if ($captcha) {
                if (false !== strpos($this->_response, 'recaptcha_errors')) {
                    $this->log('Invalid CAPTCHA',
                               Log_Abstract::LEVEL_ERROR);
                    $this->_get_captcha_decoder()->report($captcha[0], false);
                    continue;
                }
                $captcha = false;
            }

            $twid = $this->_extract_twid();
            if ($twid) {
                $this->set_twid($twid);
                $this->set_user_id($user_id);

                foreach (array('contacts', 'anyone', 'finish_new_user_flow') as $k) {
                    $this->get(self::SECURE_HOST . "/find_sources/{$k}");
                }

                return true;
            }
        }

        $this->log('Registration failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
