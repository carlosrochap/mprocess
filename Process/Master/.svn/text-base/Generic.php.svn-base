<?php
/**
 * @package Process
 */

/**
 * Generic master process, defines most used modules & actions handlers
 *
 * @package Process
 * @subpackage Master
 */
abstract class Process_Master_Generic extends Process_Master_Abstract
{
    /**
     * Fetches random first/last names
     *
     * @param string $type        Either 'first' or 'last'
     * @param string $gender      Either 'F' or 'M'
     * @param string $nationality 2-letter language code
     * @return string
     */
    protected function _get_name($type='first', $gender=null, $nationality=null)
    {
        $this->log("Fetching random {$nationality} {$gender} {$type} name");
        return $this->get_pool(ucfirst($type) . 'Name')->get($gender, $nationality);
    }

    /**
     * Fetches random proxy using project's pool
     *
     * @param int $pid Slave PID
     * @return mixed
     */
    protected function _fetch_proxy($pid)
    {
        $proxies = $this->get_pool('Proxy');
        $proxy = ($proxies instanceof Pool_ProxyDispatcher_Proxy)
            ? $proxies->get($pid)
            : $proxies->get();
        if (!$proxy) {
            $this->log('No proxies to use',
                       Log_Abstract::LEVEL_ERROR);
        }
        return $proxy;
    }

    /**
     * Instantiates proper proxy object using project configuration options
     *
     * @param string $raw_proxy
     * @return Connection_Proxy
     */
    protected function _prepare_proxy($raw_proxy)
    {
        $config = &$this->_config['proxy'];
        $proxies = $this->get_pool('Proxy');
        $proxy = new Connection_Proxy($raw_proxy);
        if (!$proxy->is_valid) {
            $proxy = new Connection_Proxy(
                (isset($config['scheme'])
                    ? $config['scheme']
                    : constant(get_class($proxies) . '::DEFAULT_SCHEME')) .
                "://{$raw_proxy}"
            );
        }
        foreach (array('port', 'userpass') as $s) {
            if (!$proxy->$s) {
                $proxy->$s = isset($config[$s])
                    ? $config[$s]
                    : constant(get_class($proxies) . '::DEFAULT_' . strtoupper($s));
            }
        }
        return $proxy;
    }

    /**
     * Fetches registered account ready to use
     *
     * @param int    $pid      Slave PID
     * @param string $pool_key Optional pool key to choose accounts by
     * @return array|false
     */
    protected function _fetch_account($pid, $pool_key='account')
    {
        $account = $this->get_pool('Account')->get($pool_key);
        if (!$account) {
            $this->log('No accounts found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $proxies = $this->get_pool('Proxy');
        $k = 'proxy';
        if (
            isset($account[$k]) &&
            ($proxies instanceof Pool_ProxyDispatcher_Proxy)
        ) {
            unset($account[$k]);
        }
        if (empty($account[$k])) {
            $account[$k] = $this->_fetch_proxy($pid);
        } else if (is_int($account[$k])) {
            $account[$k] = $proxies->get_details($account[$k]);
        }
        if (!$account[$k]) {
            return false;
        }

        if (is_string($account[$k])) {
            $account[$k] = $this->_prepare_proxy($account[$k]);
        }
        return $account;
    }

    /**
     * Fetches a set of a message, a subject and a recipient to use
     *
     * @param int   $pid
     * @param array $account Optional account details
     * @return array
     */
    protected function _fetch_message($pid, array $account=array())
    {
        $src_ids = array_filter(
            array_map('intval', explode(',', @$this->_config['msg']['srcid']))
        );
        $msg_ids = array_filter(
            array_map('intval', explode(',', @$this->_config['msg']['id']))
        );

        $messages = $this->get_pool('Message');
        $msg = array(
            'domain'  => $this->get_pool('Domain')->get(),
            'subject' => $messages->get('subject'),
            'message' => str_replace(
                array('http://[PROFILE]', 'http://www.[PROFILE]', 'www.[PROFILE]'),
                array('[PROFILE]', '[PROFILE]', '[PROFILE]'),
                !empty($msg_ids)
                    ? $this->get_pool('DynamicMessage')->get($msg_ids[array_rand($msg_ids)])
                    : str_replace("\r", '', $messages->get('message'))
            ),
        );
        if (!$msg['subject']) {
            $a = explode("\n\n", $msg['message'], 2);
            if (2 == count($a)) {
                list($msg['subject'], $msg['message']) = $a;
            }
        }

        $msg['url'] = new Url("http://www.{$msg['domain']}");
        if (!empty($src_ids)) {
            $msg['url']->add_query('id', $src_ids[array_rand($src_ids)]);
        }
        if (!empty($account['username'])) {
            $msg['url']->add_query('profile', $account['username']);
        }
        $msg['url'] = $msg['url']->get();

        $sar = array(
            '[DOMAIN]'   => &$msg['domain'],
            '[USERNAME]' => @$account['username'],
            '[FULLNAME]' => @$account['full_name'],
            '[SRCID]'    => (!empty($src_ids)
                ? $src_ids[array_rand($src_ids)]
                : ''),
            '[PROFILE]'  => (@$this->_config['msg']['keep_url']
                ? '[URL]'
                : (@$this->_config['msg']['hyperlinks']
                    ? '<a href="' . htmlentities($msg['url']) . '">' . $msg['domain'] . '</a>'
                    : $msg['url'])),
        );
        $msg['message'] = str_replace(
            array_keys($sar),
            array_values($sar),
            $msg['message']
        );
        return $msg;
    }


    /**
     * Fetches and prepares an email to register
     *
     * @return array|false
     */
    protected function _handle_module_register($pid)
    {
        $proxy = $this->_fetch_proxy($pid);
        if (!$proxy) {
            return false;
        }

        $email = $this->get_pool('UserInfo')->get('email');
        if (!$email) {
            $this->log('No e-mails to register',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $email['proxy'] = &$proxy;
        $email['pass'] = Pool_Generator::generate_password();
        $email['username'] = $this->get_pool('Username')->get();
        return $email;
    }

    /**
     * Fetches and prepares an account to confirm
     *
     * @return array|false
     */
    protected function _handle_module_confirm($pid)
    {
        return $this->_fetch_account($pid, 'unconfirmed_account');
    }

    /**
     * Fetches and prepares an account to check for suspension
     *
     * @return array|false
     */
    protected function _handle_module_check_suspended($pid)
    {
        return $this->_fetch_account($pid, 'unchecked_account');
    }

    /**
     * Fetches and prepares an account to send messages
     *
     * @return array|false
     */
    protected function _handle_module_send_message($pid)
    {
        $account = $this->_fetch_account($pid);
        return $account
            ? array_merge($account, $this->_fetch_message($pid, $account))
            : false;
    }


    /**
     * Fetches (random) profile details
     *
     * @param string|int $profile Profile ID or e-mail, null for a random one
     * @param int        $pid     Slave PID
     * @return array|false
     */
    protected function _handle_action_get_profile_details($profile, $pid)
    {
        $this->log('Fetching ' . ($profile
            ? $profile
            : 'random') . ' profile details');
        return $this->_send(
            'get_profile_details',
            $this->get_pool('UserInfo')->get_profile_details($profile),
            $pid
        );
    }

    /**
     * Fetches random userpic, of some gender if specified
     *
     * @param string $gender
     * @param int    $pid
     * @return bool
     */
    protected function _handle_action_get_userpic($gender, $pid)
    {
        $this->log('Fetching ' . ($gender ? $gender : 'random') . ' userpic');
        return $this->_send(
            'get_userpic',
            $this->get_pool('Userpic')->get($gender),
            $pid
        );
    }

    /**
     * Fetches profile userpic
     *
     * @param string|int $profile Profile ID or e-mail
     * @param int        $pid     Slave PID
     * @return string|false
     */
    protected function _handle_action_get_profile_userpic($profile, $pid)
    {
        $this->log("Fetching {$profile} userpic");
        return $this->_send(
            'get_profile_userpic',
            $this->get_pool('UserInfo')->get_profile_userpic($profile),
            $pid
        );
    }

    /**
     * Fetches random first name
     *
     * @param string|array $payload
     * @param int          $pid
     * @return array|string|false
     */
    protected function _handle_action_get_first_name($payload, $pid)
    {
        if (!is_array($payload)) {
            $payload = array(
                'gender'      => $payload,
                'nationality' => null,
            );
        }
        return $this->_send(
            'get_first_name',
            $this->_get_name('first', $payload['gender'], $payload['nationality']),
            $pid
        );
    }

    /**
     * Fetches random last name
     *
     * @param string|array $payload
     * @return string|false
     */
    protected function _handle_action_get_last_name($payload, $pid)
    {
        if (!is_array($payload)) {
            $payload = array(
                'gender'      => $payload,
                'nationality' => null,
            );
        }
        return $this->_send(
            'get_last_name',
            $this->_get_name('last', $payload['gender'], $payload['nationality']),
            $pid
        );
    }

    /**
     * Fetches random e-mail & password
     *
     * @param mixed $unused
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_get_random_email($unused, $pid)
    {
        $this->log('Fetching random e-mail');
        return $this->_send(
            'get_random_email',
            $this->get_pool('UserInfo')->get('email'),
            $pid
        );
    }

    /**
     * Fetches random location
     *
     * @param string $country
     * @param int    $pid
     * @return bool
     */
    protected function _handle_action_get_random_location($country, $pid)
    {
        $this->log("Fetching random {$country} location");
        return $this->_send(
            'get_random_location',
            $this->get_pool('UserInfo')->get_location($country),
            $pid
        );
    }

    /**
     * Generates random birthday
     *
     * @param mixed $unused
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_get_random_birthday($unused, $pid)
    {
        $this->log('Generating random birthday');
        return $this->_send(
            'get_random_birthday',
            Pool_Generator::generate_birthday(),
            $pid
        );
    }

    /**
     * Confirms an account
     *
     * @param mixed $account
     * @return bool
     */
    protected function _handle_action_confirm_account($account)
    {
        $this->log("Confirming account {$account}");
        return $this->get_pool('Account')->confirm($account);
    }

    /**
     * Unconfirms an account
     *
     * @param mixed $account
     * @return bool
     */
    protected function _handle_action_unconfirm_account($account)
    {
        $this->log("Unconfirming account {$account}");
        return $this->get_pool('Account')->unconfirm($account);
    }

    /**
     * Activates an account
     *
     * @param mixed $account
     * @return bool
     */
    protected function _handle_action_activate_account($account)
    {
        $this->log("Activating account {$account}");
        return $this->get_pool('Account')->activate($account);
    }

    /**
     * Suspends an account
     *
     * @param mixed $account
     * @return bool
     */
    protected function _handle_action_suspend_account($account)
    {
        $this->log("Suspending account {$account}");
        return $this->get_pool('Account')->suspend($account);
    }

    /**
     * Adds a used e-mail
     *
     * @param string $email
     * @return bool
     */
    protected function _handle_action_add_used_email($email)
    {
        $this->log("Adding a used e-mail {$email}");
        return $this->get_pool('UserInfo')->add_used_email($email);
    }

    /**
     * Adds account
     *
     * @param array $account Account details
     * @return bool
     */
    protected function _handle_action_add_account(array $account)
    {
        $this->log("Adding account {$account['user_id']}");
        $added = $this->get_pool('Account')->add($account);
        if ($added && !empty($account['email'])) {
            $this->get_pool('UserInfo')->add_used_email($account['email']);
        }
        return $added;
    }

    /**
     * Removes account
     *
     * @param mixed $account
     * @return bool
     */
    protected function _handle_action_remove_account($account)
    {
        $this->log("Removing account {$account}");
        return $this->get_pool('Account')->remove($account);
    }

    /**
     * Reverts account usage timestamp
     *
     * @param mixed $account
     * @return bool
     */
    protected function _handle_action_revert_account_usage_time($account)
    {
        $this->log("Reverting unused account {$account} usage time");
        return $this->get_pool('Account')->revert_usage_time($account);
    }

    /**
     * Adding a new recipient
     *
     * @param mixed $recipient
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_add_recipient($recipient, $pid)
    {
        $this->log('Adding recipient ' . (is_array($recipient)
            ? serialize($recipient)
            : $recipient
        ));
        return $this->_send(
            'add_recipient',
            $this->get_pool('Recipient')->add($recipient),
            $pid
        );
    }

    /**
     * Fetches random unprocessed recipient
     *
     * @param mixed $unused
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_get_recipient($unused, $pid)
    {
        $this->log('Fetching random recipient');
        $recipient = $this->get_pool('Recipient')->get();
        if (!$recipient) {
            $this->log('No recipients left',
                       Log_Abstract::LEVEL_ERROR);
            $this->module = 'stop';
        }
        return $this->_send('get_recipient', $recipient, $pid);
    }

    /**
     * Disables recipient
     *
     * @param mixed $recipient
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_disable_recipient($recipient, $pid)
    {
        $this->log("Disabling recipient {$recipient}");
        return $this->get_pool('Recipient')->disable($recipient);
    }

    /**
     * Adds a message successfully sent
     *
     * @param array $msg Recipient and account IDs
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_add_message(array $msg, $pid)
    {
        $this->log("Adding a message to {$msg['recipient']}");
        return $this->get_pool('Message')->add($msg);
    }
}
