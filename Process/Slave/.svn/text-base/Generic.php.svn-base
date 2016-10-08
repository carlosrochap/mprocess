<?php
/**
 * @package
 */

/**
 * Generic slave process with most common actions handlers
 *
 * @package Process
 * @subpackage Slave
 */
abstract class Process_Slave_Generic extends Process_Slave_Abstract
{
    /**
     * Handles exceptions thrown by project actions
     *
     * @param Actor_Exception $e
     * @param array           $account
     */
    protected function _handle_actor_exception(Actor_Exception $e, array $account)
    {
        $this->log($e, Log_Abstract::LEVEL_ERROR);
        if (empty($account['profile'])) {
            return;
        }

        switch ($e->getCode()) {
        case Actor_Exception::ACCOUNT_SUSPENDED:
        case Actor_Exception::INVALID_CREDENTIALS;
            $this->_send('suspend_account', $account['profile']);
            return;

        case Actor_Exception::ACCOUNT_NOT_CONFIRMED:
            $this->_send('unconfirm_account', $account['profile']);
            break;

        case Actor_Exception::ACCOUNT_ALREADY_CONFIRMED:
            $this->_send('confirm_account', $account['profile']);
            break;

        case Actor_Exception::SERVICE_ERROR:
            sleep(self::RETRY_INTERVAL_LONG);
            // Fall through

        case Actor_Exception::PROXY_BANNED:
            $this->_send('revert_account_usage_time', $account['profile']);
            break;
        }
    }

    protected function _prepare_message(array $account)
    {
        if (!empty($account['url']) && (false !== strpos(
            $account['message'],
            '[URL]'
        ))) {
            for ($s = false, $i = self::RETRY_COUNT; $i && !$s; $i--) {
                $s = Service_UrlShortener_Factory::shorten(
                    $account['url'],
                    null,
                    $this->_connection
                );
            }
            if (!$s) {
                $this->log('Failed shortening profile URL, using the long form',
                           Log_Abstract::LEVEL_ERROR);
                $s = $account['url'];
            }
            return str_replace('[URL]', $s, $account['message']);
        }
        return $account['message'];
    }

    protected function _send_message(
        Actor_Interface_Messenger $actor,
        array $account,
        $to_send=1
    )
    {
        $failures = self::RETRY_COUNT;
        while ((0 < $to_send) && (0 < $failures)) {
            $recipient = $this->get_recipient();
            if (!$recipient) {
                return false;
            }

            try {
                if ($actor->send(
                    $recipient,
                    $account['message'],
                    $account['subject']
                )) {
                    $this->_send('add_message', array(
                        'recipient' => &$recipient,
                        'profile'   => &$account['profile'],
                    ));
                    $to_send--;
                    $failures = self::RETRY_COUNT;
                } else {
                    $failures--;
                }
            } catch (Actor_Exception $e) {
                $this->log($e, Log_Abstract::LEVEL_ERROR);

                switch ($e->getCode()) {
                case Actor_Exception::RECIPIENT_NOT_FOUND:
                case Actor_Exception::RECIPIENT_PROTECTED:
                case Actor_Exception::RECIPIENT_SUSPENDED:
                    $this->_send('disable_recipient', $recipient);
                    break;

                case Actor_Exception::LIMIT_REACHED:
                case Actor_Exception::SERVICE_ERROR:
                case Actor_Exception::MESSAGE_BLACKLISTED:
                    $failures = 0;
                    // Fall through

                default:
                    $failures--;
                }
            }
        }
        return true;
    }


    protected function __handle_action_register($actor, &$account, &$details)
    {
        $project = strtolower($this->_config['project']['name']);
        $this->_send('add_account', array(
            "{$project}_id" => $actor->{"{$project}_id"},
            'user_id'       => $actor->user_id,
            'pass'          => &$account['pass'],
            'email'         => &$account['email'],
            'email_pass'    => &$account['email_pass'],
            'username'      => &$details['username'],
            'gender'        => &$details['gender'],
            'full_name'     =>
                "{$details['first_name']} {$details['last_name']}",
            'birthday'      => &$details['birthday'],
            'country'       => &$details['country'],
            'region'        => &$details['region'],
            'postal_code'   => &$details['postal_code'],
            'proxy'         => &$account['proxy'],
        ));
    }

    /**
     * Registers an account
     *
     * @param array $account Future account credentials, at least -- e-mail & e-mail pass
     * @return bool
     */
    protected function _handle_action_register(array $account)
    {
        $this->init();
        $this->set_proxy($account['proxy']);

        $actor = $this->get_actor('Registrator');
        $actor->init();

        try {
            $details = $actor->register($account['email'], $account['pass']);
            if ($details) {
                foreach (array('_before', '', '_after') as $k) {
                    $m = "__handle_action_register{$k}";
                    if (method_exists($this, $m)) {
                        $this->{$m}($actor, $account, $details);
                    }
                }
                $actor->logout();
                return true;
            }
        } catch (Actor_Exception $e) {
            if (Actor_Exception::INVALID_CREDENTIALS == $e->getCode()) {
                $this->log($e, Log_Abstract::LEVEL_ERROR);
                $this->_send('add_used_email', $account['email']);
            } else {
                $this->_handle_actor_exception($e, $account);
            }
        }
        return false;
    }

    protected function __handle_action_confirm($actor, &$account)
    {
        $this->_send('confirm_account', $account['profile']);
    }

    /**
     * Confirms an account
     *
     * @param array $account
     * @return bool
     */
    protected function _handle_action_confirm(array $account)
    {
        $this->init();
        $this->set_proxy($account['proxy']);

        $actor = $this->get_actor('Confirmer');
        $actor->init();

        try {
            if ($actor->confirm($account['email'], $account['email_pass'])) {
                foreach (array('_before', '', '_after') as $k) {
                    $m = "__handle_action_confirm{$k}";
                    if (method_exists($this, $m)) {
                        $this->{$m}($actor, $account);
                    }
                }
                return true;
            } else {
                $m = 'request_confirmation';
                if (method_exists($actor, $m) && !rand(0, 3)) {
                    $actor->{$m}(
                        $account['user_id'],
                        $account['pass'],
                        $account['email']
                    );
                }
            }
        } catch (Actor_Exception $e) {
            $this->_handle_actor_exception($e, $account);
        }
        return false;
    }

    protected function __handle_action_send_message($actor, &$account)
    {
        $account['message'] = $this->_prepare_message($account);
        $this->_send_message($actor, $account);
    }

    /**
     * Sends a message to random unmessaged recipient
     *
     * @param array $account
     * @return bool
     */
    protected function _handle_action_send_message(array $account)
    {
        $this->init();
        $this->set_proxy($account['proxy']);

        $actor = $this->get_actor('Messenger');
        $actor->init();

        try {
            if ($actor->login($account['user_id'], $account['pass'])) {
                foreach (array('_before', '', '_after') as $k) {
                    $m = "__handle_action_send_message{$k}";
                    if (method_exists($this, $m)) {
                        $this->{$m}($actor, $account);
                    }
                }
                $actor->logout();
                return true;
            }
        } catch (Actor_Exception $e) {
            $this->_handle_actor_exception($e, $account);
        }
        return false;
    }

    /**
     * Checks if account is suspended by logging in and out
     *
     * @param array $account
     * @return bool
     */
    protected function _handle_action_check_suspended(array $account)
    {
        $this->init();
        $this->set_proxy($account['proxy']);

        $actor = $this->get_actor();
        $actor->init();

        try {
            if ($actor->login($account['user_id'], $account['pass'])) {
                foreach (array('_before', '', '_after') as $k) {
                    $m = "__handle_action_check_suspended{$k}";
                    if (method_exists($this, $m)) {
                        $this->{$m}($actor, $account);
                    }
                }
                $actor->logout();
                return true;
            }
        } catch (Actor_Exception $e) {
            $this->_handle_actor_exception($e, $account);
        }
        return false;
    }


    /**
     * Fetches (random) profile details
     *
     * @param mixed $profile Profile ID, null for a random one
     * @return array|false
     */
    public function get_profile_details($profile=null)
    {
        $details = $this->_send_and_receive('get_profile_details', $profile);
        if ($details) {
            foreach (array('first_name', 'last_name') as $k) {
                $details[$k] = $this->{"get_{$k}"}($details['gender']);
            }
        }
        return $details;
    }

    /**
     * Fetches random userpic
     *
     * @param string $gender Optional gender
     * @return string|false URL on success
     */
    public function get_userpic($gender=null)
    {
        return $this->_send_and_receive('get_userpic', $gender);
    }

    /**
     * Fetches profile userpic
     *
     * @param mixed $profile Profile ID
     * @return array|false
     */
    public function get_profile_userpic($profile)
    {
        return $this->_send_and_receive('get_profile_userpic', $profile);
    }

    /**
     * Fetches random first name
     *
     * @param string $gender
     * @param string $nationality
     * @return string|false
     */
    public function get_first_name($gender=null, $nationality=null)
    {
        return $this->_send_and_receive('get_first_name', array(
            'gender'      => $gender,
            'nationality' => $nationality,
        ));
    }

    /**
     * Fetches random last name
     *
     * @param string $gender
     * @param string $nationality
     * @return string|false
     */
    public function get_last_name($gender=null, $nationality=null)
    {
        return $this->_send_and_receive('get_last_name', array(
            'gender'      => $gender,
            'nationality' => $nationality,
        ));
    }

    /**
     * Fetches random e-mail
     *
     * @return array|false
     */
    public function get_email()
    {
        return $this->_send_and_receive('get_random_email');
    }

    /**
     * Fetches random location
     *
     * @param string $country
     * @return array|false
     */
    public function get_location($country=Pool_UserInfo::DEFAULT_COUNTRY)
    {
        return $this->_send_and_receive('get_random_location', $country);
    }

    /**
     * Fetches random birthday
     *
     * @return array|false
     */
    public function get_birthday()
    {
        return $this->_send_and_receive('get_random_birthday');
    }

    /**
     * Fetches random recipient
     *
     * @return false
     */
    public function get_recipient()
    {
        return $this->_send_and_receive('get_recipient');
    }
}
