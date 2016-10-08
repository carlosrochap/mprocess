<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Twitter
 */
class Actor_Twitter extends Actor_Http_Abstract
{
    const HOST        = 'http://twitter.com';
    const SECURE_HOST = 'https://twitter.com';

    const LOG_IN_URL = '/login';

    const ACCOUNT_STATUS_URL   = '/status/update';
    const ACCOUNT_SETTINGS_URL = '/settings/account';
    const ACCOUNT_PROFILE_URL  = '/settings/profile';

    const CHANGE_PASS_URL  = '/account/password_reset';
    const RESET_PASS_URL   = '/account/resend_password';

    const REGISTER_URL        = '/signup';
    const REGISTER_SUBMIT_URL = '/account/create';


    protected $_twid = 0;


    /**
     * Extracts first authenticity token value from arbitrary page
     *
     * @param string $src
     * @return string|false
     */
    protected function _extract_auth_token($src=null)
    {
        if (preg_match(
            '#name="authenticity_token"[^>]+value="(?P<token>[a-f\d]+)"#',
            ((null !== $src)
                ? $src
                : $this->_response),
            $m
        )) {
            return $m['token'];
        }

        $this->log('Auth token not found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Extracts current user's twid from arbitrary twitter page
     *
     * @param string $src
     * @return int|false
     */
    protected function _extract_twid($src=null)
    {
        if (preg_match(
            '#content="(\d+)" name="session-userid"#',
            ((null !== $src)
                ? $src
                : $this->_response),
            $m
        )) {
            return (int)$m[1];
        }

        $this->log('User TWID not found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Extracts a user's Twitter ID from his profile page
     *
     * @param string $src Profile page, defaults to {@link ::_response}
     * @return int|false
     */
    protected function _extract_profile_twid($src=null)
    {
        if (preg_match(
            '#/(\d+)\.rss#',
            ((null !== $src)
                ? $src
                : $this->_response),
            $m
        )) {
            return (int)$m[1];
        }

        $this->log('TWID not found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    protected function _upload_userpic($fn)
    {
        $this->get(
            self::SECURE_HOST . self::ACCOUNT_PROFILE_URL,
            null,
            self::SECURE_HOST . '/'
        );
        $this->_dump('userpic.form.html');
        if (!$this->get_form('id', 'profile_form')) {
            $this->log('Profile form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form->add_file('profile_image[uploaded_data]', $fn);
        $this->submit();
        $this->_dump('userpic.submit.html');
        return (false === strpos($this->_response, 'images/default_profile'));
    }


    /**
     * @see Actor_Http_Abstract::init()
     */
    public function init()
    {
        $this->_twid = 0;
        return parent::init();
    }

    /**
     * Sets current user's TWID
     *
     * @param int $twid
     */
    public function set_twid($twid)
    {
        $this->_twid = (int)$twid;
        return $this;
    }

    /**
     * Returns current user's TWID
     *
     * @return int
     */
    public function get_twid()
    {
        return $this->_twid;
    }

    /**
     * @see Actor_Interface::login()
     */
    public function login($user_id, $pass)
    {
        $this->log("Logging in as {$user_id}:{$pass}");

        $this->get(self::HOST . self::LOG_IN_URL);
        $this->_dump('login.form.html');
        if (!$this->get_form('class', 'signin')) {
            throw new Actor_Twitter_Exception(
                'Login form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['session[username_or_email]'] = $user_id;
        $this->_form['session[password]'] = $pass;
        $this->_form['remember_me'] = 1;
        $this->submit();
        $this->_dump('login.submit.html');
        if (false !== strpos($this->_response, 'Wrong Username')) {
            throw new Actor_Twitter_Exception(
                'Invalid user ID or password',
                Actor_Exception::INVALID_CREDENTIALS
            );
        } else if (false !== strpos($this->_response, 'name="session-userid"')) {
            $this->user_id = $user_id;

            if ($this->is_account_suspended()) {
                throw new Actor_Twitter_Exception(
                    'Account suspended',
                    Actor_Exception::ACCOUNT_SUSPENDED
                );
            } else if (false !== strpos(
                $this->_response,
                '>Is your email address active?<'
            )) {
                throw new Actor_Twitter_Exception(
                    "Account's e-mail is suspended",
                    Actor_Exception::EMAIL_SUSPENDED
                );
            } else if (!$this->is_account_confirmed()) {
                throw new Actor_Twitter_Exception(
                    'Account is not confirmed',
                    Actor_Exception::ACCOUNT_NOT_CONFIRMED
                );
            } else if ($this->is_default_userpic()) {
                $details = $this->call_process_method('get_profile_details', $user_id);
                if ($details && !empty($details['userpic'])) {
                    $this->upload_userpic($details['userpic']);
                }
            }
            return true;
        }

        $this->log('Login failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * @see Actor_Interface::logout()
     */
    public function logout()
    {
        if ($this->_user_id) {
            $this->get(self::HOST);
            if ($this->get_form('id', 'sign_out_form')) {
                $this->submit();
            }
        }
        $this->_twid = 0;
        return parent::logout();
    }

    /**
     * Checks whether the current account is suspended
     *
     * @return bool
     */
    public function is_account_suspended()
    {
        $url = self::HOST . '/';
        if ($url != $this->_connection->last_url) {
            $this->get($url);
        }
        return (false !== stripos($this->_response, 'Account Suspended'));
    }

    /**
     * Checks if the current account's e-mail is confirmed
     *
     * @return bool
     */
    public function is_account_confirmed()
    {
        $url = self::HOST . '/';
        if ($url != $this->_connection->last_url) {
            $this->get($url);
        }
        return (false === stripos(
            $this->_response,
            '"unconfirmed-email-banner"'
        ));
    }

    /**
     * Checks if a credential (username or e-mail) is available
     *
     * @param string $name
     * @param string $value
     * @param string $auth_token
     * @return bool
     */
    public function is_credential_available($name, $value, $auth_token)
    {
        $this->log("Checking {$name} {$value} availability");

        $s = $this->_connection->ajax(
            self::SECURE_HOST . "/users/{$name}_available",
            null,
            array(
                $name                => $value,
                'authenticity_token' => $auth_token,
            ),
            self::SECURE_HOST . self::REGISTER_URL
        );
        $this->_dump("check.{$name}.{$value}.js", $s);
        return (false !== strpos($s, '"valid":true'));
    }

    public function tweet($tweet)
    {
        $this->log('Tweeting');

        $form_attr = array('id' => 'status_update_form');
        if (!$this->get_form($form_attr)) {
            $this->get(self::HOST);
        }
        if (!$this->get_form($form_attr)) {
            $this->log('Tweet form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        if (is_array($tweet)) {
            if (!empty($tweet['url'])) {
                do {
                    try {
                        $url = Service_UrlShortener_Factory::factory(
                            null,
                            $this->_connection
                        )->shorten($tweet['url']);
                    } catch (Exception $e) {
                        $url = '';
                    }
                } while (!$url);
                $l = 140 - (mb_strlen($url) + 1);
                if ($l < mb_strlen($tweet['content'])) {
                    $tweet['content'] =
                        mb_substr($tweet['content'], 0, $l - 3) . '...';
                }
                $tweet = "{$tweet['content']} {$url}";
            } else {
                $tweet = $tweet['content'];
            }
        }

        $this->_connection->ajax(
            self::HOST . self::ACCOUNT_STATUS_URL,
            array(
                'return_rendered_status' => 'true',
                'status'                 => mb_substr($tweet, 0, 140),
                'twttr'                  => 'true',
                'authenticity_token'     => $this->_form['authenticity_token']
            )
        );
        return (false !== strpos(
            $this->_connection->response,
            'status_count'
        ));
    }

    /**
     * Follows a twitter-er
     *
     * @param string $user_id
     * @return bool
     */
    public function follow($user_id)
    {
        $this->log("Following {$user_id}");

        $this->get(self::HOST . "/{$user_id}");
        $this->_dump("{$user_id}.profile.html");
        if (404 == $this->_connection->status_code) {
            throw new Actor_Twitter_Exception(
                "{$user_id} not found",
                Actor_Exception::RECIPIENT_NOT_FOUND
            );
        } else if (false !== strpos($this->_response, 'has been suspended')) {
            throw new Actor_Twitter_Exception(
                "{$user_id} has been suspended",
                Actor_Exception::RECIPIENT_SUSPENDED
            );
        } else if (false !== strpos($this->_response, 'send a request before')) {
            throw new Actor_Twitter_Exception(
                "{$user_id} has protected his tweets",
                Actor_Exception::RECIPIENT_PROTECTED
            );
        } else if (false !== strpos($this->_response, 'class="user following"')) {
            $this->log("{$user_id} is already being followed",
                       Log_Abstract::LEVEL_ERROR);
            return true;
        }

        $twid = $this->_extract_profile_twid();
        $auth_token = $this->_extract_auth_token();
        if (!$twid || !$auth_token) {
            return false;
        } else {
            $this->log("{$user_id}'s TWID is {$twid}",
                       Log_Abstract::LEVEL_DEBUG);
        }

        $this->ajax(self::HOST . "/friendships/create/{$twid}", array(
            'authenticity_token' => $auth_token,
            'twttr'              => 'true',
        ));
        $this->_dump("{$user_id}.follow.js");
        if (false !== strpos($this->_response, '"result":"added"')) {
            return true;
        }

        $this->log("Failed following {$user_id}",
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * Unfollows a twitter-er
     *
     * @param string $user_id
     * @return bool
     */
    public function unfollow($user_id)
    {
        $this->log("Unfollowing {$user_id}");

        $this->get(self::HOST . "/{$user_id}");
        $this->_dump("{$user_id}.profile.html");
        $twid = $this->_extract_profile_twid();
        if (!$twid) {
            return false;
        }

        $post = array('twttr' => 'true');
        $post['authenticity_token'] = $this->_extract_auth_token();
        if (!$post['authenticity_token']) {
            return false;
        }

        $this->ajax(
            self::HOST . "/friendships/destroy/{$twid}",
            $post
        );
        if (false !== strpos($this->_response, 'success')) {
            return true;
        }

        $this->log("Failed unfollowing {$user_id}",
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function change_email($email, $pass)
    {
        $this->log("Changing e-mail to {$email}");

        $this->get(self::SECURE_HOST . self::ACCOUNT_SETTINGS_URL, array(
            'change_email' => 'true'
        ));
        $this->_dump('change-email.form.html');
        if (!$this->get_form('id', 'account_settings_form')) {
            $this->log('Account settings form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form['user[email]'] = $email;
        $this->_form['user[discoverable_by_email]'] = 0;
        $this->_form['auth_password'] = $pass;
        $this->_dump('change-email.submit.txt', $this->_form->to_array());
        $this->submit();
        $this->_dump('change-email.submit.js');
        if (false !== strpos($this->_response, 'message has been sent')) {
            return true;
        }

        $this->log('Failed changing the e-mail',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function change_password($pass, $email, $email_pass)
    {
        $this->log("Changing {$email}:{$email_pass} password to {$pass}");

        try {
            $mailer = Email_Factory::factory($email, $this->_log, $this->_connection);
            if (!$mailer->login($email, $email_pass)) {
                $this->log('Failed logging in to e-mail service',
                           Log_Abstract::LEVEL_ERROR);
                return false;
            }
        } catch (Email_Exception $e) {
            throw new Actor_Twitter_Exception(
                $e->getMessage(),
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        $url = $mailer->get_message(
            'Twitter',
            'Reset your Twitter password',
            '#' . preg_quote(self::CHANGE_PASS_URL) . '([^"]+token=[a-f\d]+)#'
        );
        if (!$url) {
            $this->log('Password change message/URL not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->get(self::HOST . self::CHANGE_PASS_URL . $url);
        $this->_dump('pass.change.form.html');
        if (!$this->get_form('id', 'reset-pw')) {
            $this->log('Password change form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form['user_password'] =
            $this->_form['user_password_confirmation'] = $pass;
        $this->submit();
        $this->_dump('pass.change.submit.html');
        if (false !== strpos($this->_response, 'password has been changed')) {
            return true;
        }

        $this->log('Failed changing password',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function reset_password($user_id)
    {
        $this->log("Resetting {$user_id} password");

        $this->get(self::SECURE_HOST . self::RESET_PASS_URL);
        $this->_dump('pass.reset.form.html');
        if (!$this->get_form('id', 'reset-pw')) {
            $this->log('Password reset form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form['email'] = $user_id;
        $this->submit();
        $this->_dump('pass.reset.submit.html');
        if (false !== strpos($this->_response, 'sent the instructions')) {
            return true;
        } else if (false !== strpos($this->_response, 'Oh, snap!')) {
            throw new Actor_Twitter_Exception(
                'Account not found or removed',
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        $this->log('Failed resetting password',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function is_default_userpic($src=null)
    {
        $this->log('Checking for default userpic');
        return preg_match(
            '#src="[^"]+images/default_profile[^>]+><span id="me_name"#',
            ((null !== $src)
                ? $src
                : $this->_response)
        );
    }

    public function __call($method, $args)
    {
        $response = parent::__call($method, $args);
        if ($response && (503 == $this->_connection->status_code)) {
            $this->_dump('service-down.html', $response);
            throw new Actor_Twitter_Exception(
                'Service temporarily unavailable',
                Actor_Exception::SERVICE_ERROR
            );
        }
        return $response;
    }
}
