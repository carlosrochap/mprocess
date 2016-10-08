<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Live
 */
class Actor_Live extends Actor_Http_Abstract
{
    const HOST               = 'http://www.live.com';
    const HOME_HOST          = 'http://home.live.com';
    const PROFILE_HOST       = 'http://profile.live.com';
    const PROFILE_HOME_HOST  = 'http://home.profile.live.com';
    const PEOPLE_HOST        = 'http://people.live.com';
    const SPACES_HOST        = 'http://spaces.live.com';
    const SPACES_HOME_HOST   = 'http://home.spaces.live.com';
    const LOG_IN_HOST        = 'https://login.live.com';
    const ACCOUNT_HOST       = 'https://account.live.com';
    const PASSPORT_HOST      = 'https://accountservices.passport.net';

    const LOG_IN_PWDPAD = 'IfYouAreReadingThisYouHaveTooMuchFreeTime';
    const LOG_OUT_URL   = '/logout.srf';

    const PROFILE_DETAILS_URL = '/details/Edit/About';
    const PROFILE_CONTACT_URL = '/details/Edit/Contact';

    const NAME_CONFIRM_URL      = '/details/Edit/Name';
    const NAME_CONFIRM_SAVE_URL = '/details/edit/savename';

    const USERPIC_UPLOAD_URL      = '/details/Edit/Pic';
    const USERPIC_UPLOAD_SAVE_URL = '/details/Edit/SavePic';

    const PASSWORD_RESET_URL         = '/resetpw.srf';
    const PASSWORD_RESET_CAPTCHA_URL = '/gethip.srf';
    const PASSWORD_CHANGE_URL        = '/ChangePW.srf';

    const ENC_KEYS_URL = '/ppsecure/JSPublicKey.srf';

    const ALT_EMAIL_CHANGE_URL       = '/ChangeAltEmail.aspx';
    const SECRET_QUESTION_CHANGE_URL = '/ChangeSQSA.aspx';


    protected $_pass = '';
    protected $_cid = '';


    protected function _get_timestamp()
    {
        return (string)time() . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
    }

    protected function _get_profile_host($cid=null, $home=false)
    {
        return str_replace(
            '://',
            '://cid-' . ((null === $cid) ? $this->_cid : $cid) . '.',
            ($home ? self::PROFILE_HOME_HOST : self::PROFILE_HOST)
        );
    }

    protected function _get_spaces_host($cid=null, $home=false)
    {
        return str_replace(
            '://',
            '://cid-' . ((null === $cid) ? $this->_cid : $cid) . '.',
            ($home ? self::SPACES_HOME_HOST : self::SPACES_HOST)
        );
    }

    protected function _get_encryption_keys()
    {
        $this->log('Fetching encryption keys');
        $s = $this->_connection->get(self::LOG_IN_HOST . self::ENC_KEYS_URL);
        $this->_dump('encryption-keys.js', $s);
        if (!preg_match_all('#(?:^|;) var (\S+)\s?=\s?"([^"]+)#', $s, $m)) {
            $this->log('Encryption keys not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            return array_combine($m[1], array_map('stripcslashes', $m[2]));
        }
    }

    protected function _encrypt($pass, $enc_keys=null, $sqsa='', $type='pwd', $new_pass='')
    {
        if (!$enc_keys) {
            $enc_keys = $this->_get_encryption_keys();
            if (!$enc_keys) {
                return false;
            }
        }

        $this->log(trim("Encrypting {$type} {$pass} {$sqsa} {$new_pass}"));

        $s = JavaScript_Executor::execute(
            str_replace('.php', '.encryptor.js', __FILE__),
            array(
                $enc_keys['Key'],
                $enc_keys['randomNum'],
                $pass,
                $sqsa,
                $type,
                $new_pass
            )
        );
        if (!$s) {
            $this->log('Encryption failed, check the corresponding script',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            return array(
                'keys' => $enc_keys,
                'data' => $s,
            );
        }
    }

    /**
     * Handles MSN redirects
     *
     * @param string $response
     * @param string $dump_prefix
     * @return string
     */
    protected function _redirect($response=null, $dump_prefix='')
    {
        if (null === $response) {
            $response = &$this->_response;
        }
        $i = 0;
        while ($form = Html_Form::get(
            $response,
            $this->_connection,
            'id',
            'fmHF'
        )) {
            $i++;
            $this->log("Redirect to: {$form->action}",
                       Log_Abstract::LEVEL_DEBUG);
            $response = $form->submit($this->_connection);
            $this->_dump(
                ($dump_prefix ? "{$dump_prefix}." : '') . "redirect.{$i}.html",
                $response
            );
        }
        while (preg_match(
            '#function rdr\(\).+?replace\(\'([^\']+)#',
            $response,
            $url
        )) {
            $i++;
            $url = stripcslashes($url[1]);
            $this->log("Redirect to: {$url}",
                       Log_Abstract::LEVEL_DEBUG);
            $response = $this->_connection->get($url);
            $this->_dump(
                ($dump_prefix ? "{$dump_prefix}." : '') . "redirect.{$i}.html",
                $response
            );
        }
        return $response;
    }

    protected function _upload_userpic($fn)
    {
        return false;

        $profile_host = $this->_get_profile_host();
        $this->get($profile_host . self::USERPIC_UPLOAD_URL, array(
            'ru' => "{$profile_host}/"
        ), $profile_host);
        $this->_dump('userpic.form.html');
        if (!$this->get_form('id', 'frmUpload')) {
            $this->log('Userpic upload form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $sc = $this->_form['sc'];
        }

        $this->_form['fileUpload'] = $fn;
        $this->_dump('userpic.upload.txt', $this->_form);
        $this->_connection->remove_cookie('LD');
        $this->submit();
        $this->_dump('userpic.upload.html');
        if (!preg_match(
            '#"ResourceId":("[^"]+")#',
            $this->_response,
            $rid
        )) {
            $this->log('Userpic resource ID not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $rid = json_decode($rid[1], true);
        }

        // Hardcoded values for 150x120 userpics
        $this->post($profile_host . self::USERPIC_UPLOAD_SAVE_URL, array(
            'sc'                 => $sc,
            'resourceId'         => &$rid,
            'x'                  => 15,
            'y'                  => 0,
            'width'              => 120,
            'height'             => 120,
            'isWebReadyUpToDate' => 'true',
        ));
        $this->_dump('userpic.save.html');
        return (false !== strpos($this->_response, '"HasSucceeded":"true"'));
    }

    protected function _init_pass_reset_form($user_id)
    {
        $this->get(self::LOG_IN_HOST);
        $this->_dump('login.html');
        if (!preg_match(
            '#href="([^"]+' . preg_quote(self::PASSWORD_RESET_URL) . '[^"]+)#',
            $this->_response,
            $url
        )) {
            throw new Actor_Live_Exception(
                'Password reset URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $url = html_entity_decode($url[1], ENT_QUOTES);
        }

        $this->get($url);
        for ($i = self::RETRY_COUNT; $i; $i--) {
            $this->_dump("pass-reset.form.{$i}.html");
            if (!$this->get_form('name', 'f1')) {
                throw new Actor_Live_Exception(
                    'Password reset form not found',
                    Actor_Exception::PROXY_BANNED
                );
            } else if (!preg_match(
                '#src="([^"]+' . preg_quote(self::PASSWORD_RESET_CAPTCHA_URL) . '[^"]+)#',
                $this->_response,
                $captcha_url
            )) {
                throw new Actor_Live_Exception(
                    'CAPTCHA script URL not found',
                    Actor_Exception::PROXY_BANNED
                );
            } else {
                $captcha_url = html_entity_decode($captcha_url[1], ENT_QUOTES);
            }

            $captcha_script = $this->_connection->get($captcha_url);
            $this->_dump('pass.reset.captcha.js', $captcha_script);
            if (!preg_match('#,imageurl:"([^"]+)#', $captcha_script, $captcha_url)) {
                throw new Actor_Live_Exception(
                    'CAPTCHA image URL not found',
                    Actor_Exception::PROXY_BANNED
                );
            } else {
                $captcha_url = stripcslashes($captcha_url[1]);
            }
            if (!preg_match('#,hipfrtoken:"([^"]+)#', $captcha_script, $captcha_token)) {
                throw new Actor_Live_Exception(
                    'CAPTCHA token not found',
                    Actor_Exception::PROXY_BANNED
                );
            } else {
                $captcha_token = stripcslashes($captcha_token[1]);
            }

            for ($captcha = false, $j = self::RETRY_COUNT; $j && !$captcha; $j--) {
                $captcha_started = time();
                $captcha = $this->_decode_captcha($captcha_url);
            }
            if (!$captcha) {
                return false;
            }

            unset($this->_form['Continue']);
            $challenge = '';
            $j = 0;
            while ($s = $this->_connection->get_cookie("HIPChallenge{$j}")) {
                $challenge .= "|HIPChallenge{$j}={$s}";
                $j++;
            }
            $this->_form['HIPSolution'] =
                'Fr=hard,Solution=' . $captcha[1] .
                ',HIPFrame=' . $captcha_token . $challenge .
                '|HIPTime=' . (time() - $captcha_started) . (string)rand(100, 999);
            $this->_form['login'] = $user_id;
            $this->_form['cancel'] = 0;
            $this->_dump('pass-reset.email.submit.txt', $this->_form);
            $this->submit();
            $this->_dump('pass-reset.email.submit.html');
            if (false !== strpos($this->_response, 'Reset your password')) {
                return true;
            } else if (false !== strpos($this->_response, 'HIP.error = 1')) {
                $this->log('Invalid CAPTCHA',
                           Log_Abstract::LEVEL_ERROR);
                $this->_get_captcha_decoder()->report($captcha[0], false);
            } else if (false !== strpos($this->_response, 'e-mail address is incorrect')) {
                throw new Actor_Live_Exception(
                    'Invalid e-mail',
                    Actor_Exception::INVALID_CREDENTIALS
                );
            }
        }

        return false;
    }

    protected function _change_pass($user_id, $new_pass)
    {
        $this->log("Changing {$user_id} password to {$new_pass}");

        if (
            (false === strpos($this->_response, 'NewPW1')) ||
            !$this->get_form('name', 'f1')
        ) {
            throw new Actor_Live_Exception(
                'Password change form not found',
                Actor_Exception::PROXY_BANNED
            );
        } else if (!preg_match(
            '#(' . preg_quote(self::PASSWORD_CHANGE_URL) . '[^\']+)#',
            $this->_response,
            $submit_url
        )) {
            throw new Actor_Live_Exception(
                'Password change form action URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $submit_url = html_entity_decode($submit_url[1], ENT_QUOTES);
        }

        $this->_form['NewPW1'] = $this->_form['NewPW2'] = $new_pass;
        $this->_form['PasswordStrength'] = 3;
        $this->_form['PwdPad'] = substr(
            self::LOG_IN_PWDPAD,
            0,
            strlen(self::LOG_IN_PWDPAD) - strlen($new_pass)
        );
        $this->_form->action = self::LOG_IN_HOST . $submit_url;
        $this->_dump('pass-change.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('pass-change.submit.html');
        return (false !== strpos($this->_response, 'changed your password'));
    }


    public function set_cid($cid)
    {
        $this->_cid = str_replace('cid-', '', (string)$cid);
        return $this;
    }

    public function get_cid()
    {
        return $this->_cid;
    }

    /**
     * @see Actor_Http_Abstract::init()
     */
    public function init()
    {
        $this->_cid = $this->_pass = '';
        return parent::init();
    }

    /**
     * @see Actor_Interface::login()
     */
    public function login($user_id, $pass)
    {
        $this->_connection->follow_refresh = false;

        $this->log("Logging in as {$user_id}:{$pass}");

        /* First login attempt fails with invalid credentials response
           sometimes with perfectly fine accounts; guess some cookies
           are missing the first time or something, so let's start the
           login sequence by submitting an empty form. */
        $this->get(self::LOG_IN_HOST);
        if ($this->get_form('name', 'f1')) {
            $this->submit();
        }
        $this->_dump('login.form.html');
        if (!$this->get_form('name', 'f1')) {
            throw new Actor_Live_Exception(
                'Login form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        unset($this->_form['SI']);
        $this->_form['login'] = $user_id;
        $this->_form['passwd'] = $pass;
        $this->_form['PwdPad'] = substr(
            self::LOG_IN_PWDPAD,
            0,
            strlen(self::LOG_IN_PWDPAD) - strlen($pass)
        );
        $this->_form['LoginOptions'] = 3;
        $this->_dump('login.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('login.submit.html');
        if (false !== strpos(
            $this->_response,
            'tried to sign in too many times'
        )) {
            throw new Actor_Live_Exception(
                'Too many invalid login attempts',
                Actor_Exception::INVALID_CREDENTIALS
            );
        } else if (false !== strpos(
            $this->_response,
            'e-mail address or password is incorrect'
        )) {
            throw new Actor_Live_Exception(
                'Invalid user ID/password',
                Actor_Exception::INVALID_CREDENTIALS
            );
        } else if (false !== strpos($this->_response, 'logout.')) {
            $this->user_id = $user_id;
            $this->_pass = $pass;

            $this->_connection->set_cookie('mkt', 'ep=en-US', '.live.com');
            $this->get(self::PROFILE_HOST);
            $this->_dump('profile.html');
            if (!preg_match('#cid[-=]([A-Fa-f\d]+)#', $this->_connection->last_url, $m)) {
                throw new Actor_Live_Exception(
                    "{$user_id} CID not found",
                    Actor_Exception::PROXY_BANNED
                );
            } else {
                $this->cid = $m[1];
            }

            $this->confirm_name();
            /*if (
                !rand(0, 4) &&
                (false !== strpos($this->_connection->last_url, str_replace(
                    'http://',
                    '',
                    self::PROFILE_HOST
                ))) &&
                $this->is_default_userpic()
            ) {
                $userpic =
                    $this->call_process_method('get_profile_userpic', $user_id);
                if ($userpic) {
                    $this->upload_userpic($userpic);
                    $this->get(self::PROFILE_HOST);
                }
            }*/

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
            $this->get(self::LOG_IN_HOST . self::LOG_OUT_URL);
        }
        $this->_cid = '';
        return parent::logout();
    }

    /**
     * Confirms user's name
     *
     * @return bool
     */
    public function confirm_name()
    {
        if (false === stripos($this->_response, self::NAME_CONFIRM_URL)) {
            return true;
        }

        $this->log('Confirming the name');

        $this->get($this->_get_profile_host() . self::NAME_CONFIRM_URL);
        $this->_dump('confirm-name.form.html');

        $query = array(
            'sc' => $this->_connection->get_cookie('sc'),
            'r'  => (string)time() . (string)rand(100, 999),
        );
        foreach (array('NameA' => 'fn', 'NameB' => 'ln') as $k => $v) {
            if (!preg_match(
                '#cxp_pen_' . $k . '"[^>]+value="([^"]+)#',
                $this->_response,
                $m
            )) {
                $this->log("Name {$k} field not found",
                           Log_Abstract::LEVEL_ERROR);
                return false;
            } else {
                $query[$v] = html_entity_decode($m[1], ENT_QUOTES);
            }
        }
        $this->_dump('confirm-name.submit.txt', $query);
        $this->get($this->_get_profile_host() .self::NAME_CONFIRM_SAVE_URL, $query);
        $this->_dump('confirm-name.submit.html');
        return true;
    }

    /**
     * Updates user's profile details
     *
     * @param array $details Profile details
     * @return bool
     */
    public function update_account_details(array $details)
    {
        $this->log('Updating account details');
        $this->log($details, Log_Abstract::LEVEL_DEBUG);

        $this->get($this->_get_profile_host());
        $this->confirm_name();

        $this->get($this->_get_profile_host() . self::PROFILE_DETAILS_URL);
        $this->_dump('profile.about.html');
        if (!$this->get_form('id', 'aspnetForm')) {
            $this->log('Account details editor form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form['ctl00$ctl00$MainContent$MainContent$Gender'] =
            ('M' == $details['gender'])
                ? 'Male'
                : 'Female';
        $this->_form['ctl00$ctl00$MainContent$MainContent$iMoreAboutMe'] =
            substr($details['about_me'], 0, 1024);
        $this->_form->action = self::PROFILE_DETAILS_URL;
        $this->submit();
        $this->_dump('profile.about.submit.html');
        if (false !== strpos($this->_response, 'changes were saved')) {
            return true;
        }

        $this->log('Failed updating profile details',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function is_account_email_set($email, $src=null)
    {
        if (null === $src) {
            $this->get(self::ACCOUNT_HOST);
            $this->_dump('summary.html');
            $src = $this->_response;
        }
        return preg_match(
            '#idAltEmailAvail"[^>]+>\s+' . preg_quote($email) . '\s+<#',
            $src
        );
    }

    public function update_account_email($email)
    {
        if (is_array($email)) {
            $email = $email['email'];
        }

        $this->log('Updating alternative e-mail');
        $this->log($email, Log_Abstract::LEVEL_DEBUG);

        $this->get(self::ACCOUNT_HOST);
        $this->_dump('summary.html');

        $this->get(self::ACCOUNT_HOST . self::ALT_EMAIL_CHANGE_URL);
        $this->_dump('alt-email.form.html');
        if (!$this->get_form('id', 'aspnetForm')) {
            $this->log('Alternative e-mail form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $prefix = 'ctl00$ctl00$MainContent$MainContent$AccountContent$ctl00$';
        $s = $this->_encrypt($this->_pass);
        if (!$s) {
            return false;
        } else {
            unset($this->_form["{$prefix}iCurPassword"]);
            $this->_form["{$prefix}iPwdEncrypted"] = $s['data'];
            $this->_form["{$prefix}iPublicKey"] = $s['keys']['SKI'];
        }
        $this->_form["{$prefix}iAltEmail"] =
            $this->_form["{$prefix}iRetypeAltEmail"] = $email;
        $this->_dump('alt-email.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('alt-email.submit.html');
        if ($this->is_account_email_set($email, $this->_response)) {
            return true;
        }

        $this->log('Failed updating alternative e-mail',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function is_account_secret_question_set($secret_question, $src=null)
    {
        if (!is_array($secret_question)) {
            $secret_question = explode(';', $secret_question, 2);
        }
        if (null === $src) {
            $this->get(self::ACCOUNT_HOST);
            $this->_dump('summary.html');
            $src = $this->_response;
        }
        return preg_match(
            '#idSQSAAvail"[^>]+>\s+' . preg_quote($secret_question[0]) . '\s+<#',
            $src
        );
    }


    public function update_account_secret_question($secret_question, array $location)
    {
        if (!is_array($secret_question)) {
            $secret_question = explode(';', $secret_question, 2);
        }

        $this->log('Updating secret question/location');
        $this->log($secret_question, Log_Abstract::LEVEL_DEBUG);
        $this->log($location, Log_Abstract::LEVEL_DEBUG);

        $this->get(self::ACCOUNT_HOST);
        $this->_dump('summary.html');

        $this->get(self::ACCOUNT_HOST . self::SECRET_QUESTION_CHANGE_URL);
        $this->_dump('secret-question.form.html');
        if (!$this->get_form('id', 'aspnetForm')) {
            $this->log('Secret question form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $prefix = 'ctl00$ctl00$MainContent$MainContent$AccountContent$ctl00$';
        $s = $this->_encrypt($this->_pass, null, $secret_question[1], 'chgsqsa');
        if (!$s) {
            return false;
        } else {
            unset(
                $this->_form["{$prefix}iCurPassword"],
                $this->_form["{$prefix}iSecretAnswer"]
            );
            $this->_form["{$prefix}iQuestion"] = $secret_question[0];
            $this->_form["{$prefix}iEncryptedSecretAnswer"] = $s['data'];
            $this->_form["{$prefix}iPublicKey"] = $s['keys']['SKI'];
        }
        $this->_form["{$prefix}iCountry"] = $location['country'];
        $this->_form["{$prefix}iPostal"] = $location['postal_code'];
        $this->_form["{$prefix}iRegion"] = $this->call_process_method(
            'get_region_id',
            $location['country'],
            $location['region']
        );  // Numberic region ID goes here
        $this->_dump('secret-question.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('secret-question.submit.html');
        if ($this->is_account_secret_question_set($secret_question, $this->_response)) {
            return true;
        }

        $this->log('Failed updating secret question/location',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function update_contact_details(array $details)
    {
        $this->log('Updating account contact details');
        $this->log($details, Log_Abstract::LEVEL_DEBUG);

        $this->get($this->_get_profile_host() . self::PROFILE_CONTACT_URL);
        $this->_dump('contact.form.html');
        if (!$this->get_form('name', 'aspnetForm')) {
            $this->log('Contact details form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $prefix = $this->_form['IDPrefix'];
        }

        $k = 'birthday';
        if (!empty($details[$k])) {
            list(
                $this->_form["{$prefix}iBirthYear"],
                $this->_form["{$prefix}iBirthMonth"],
                $this->_form["{$prefix}iBirthDay"]
            ) = $details[$k];
        }

        $k = 'email';
        if (!empty($details[$k])) {
            $this->_form["{$prefix}iPersonalEmail"] = is_array($details[$K])
                ? $details[$k]['email']
                : $details[$k];
        }

        $k = 'location';
        if (!empty($details[$k])) {
            $this->_form["{$prefix}iCountryCode"] = $details[$k]['country'];
            $this->_form["{$prefix}iCountryName"] = 'United States';  // @todo parse out actual country
            $this->_form["{$prefix}iRegion"] = $this->call_process_method(
                'get_region_id',
                $details[$k]['country'],
                $details[$k]['region']
            );
            $this->_form["{$prefix}iPostalCode"] = $details[$k]['postal_code'];
        }

        $this->_form->action = $this->_connection->last_url;
        $this->_dump('contact.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('contact.submit.html');
        if (false !== strpos($this->_response, 'changes were saved')) {
            return true;
        }

        $this->log('Failed updating contact details',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function update_account_birthday(array $birthday)
    {
        return $this->update_contact_details(array('birthday' => $birthday));
    }

    public function update_account_location(array $location)
    {
        return $this->update_contact_details(array('location' => $location));
    }

    /**
     * Checks if a user has default userpic
     *
     * @return bool
     */
    public function is_default_userpic($src=null)
    {
        $this->log('Checking for default userpic');
        return (false !== strpos(
            (null === $src)
                 ? $this->_response
                 : $src,
            'id="ic2_usertile" errsrc="http://secure.wlxrs.com/$live.controls.images/ic/bluemannxl.png"'
        ));
    }

    public function request_pass_reset_email($user_id)
    {
        $this->log("Requesting {$user_id} password reset e-mail");

        if (!$this->_init_pass_reset_form($user_id)) {
            return false;
        } else if (!preg_match(
            '#href="([^"]+)"[^>]+id="i1603"#',
            $this->_response,
            $reset_url
        )) {
            throw new Actor_Live_Exception(
                'E-mail password reset option URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $reset_url = html_entity_decode($reset_url[1], ENT_QUOTES);
        }

        $this->get($reset_url);
        $this->_dump('pass-reset.options.html');
        if (!$this->get_form('name', 'f1')) {
            throw new Actor_Live_Exception(
                'E-mail password reset options form not found',
                Actor_Exception::PROXY_BANNED
            );
        } else if ('Alt' != $this->_form['EmailOption']) {
            throw new Actor_Live_Exception(
                'Invalid alternative e-mail',
                Actor_Exception::INVALID_ARGUMENT
            );
        }

        $this->_dump('pass-reset.email.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('pass-reset.email.submit.html');
        if (false !== strpos($this->_response, 'message has been sent')) {
            return true;
        }

        $this->log('Failed requesting password reset e-mail',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function change_pass_by_email($user_id, $email, $email_pass, $new_pass)
    {
        $this->log("Changing {$user_id} password using {$email}");

        $mailer = $this->_get_email_client($email, $email_pass);
        if (!$mailer) {
            return false;
        }

        $url = $mailer->get_message(
            'Windows Live Team',
            'Reset your Windows Live ID password',
            '#(https://.+?/EmailPage\.srf[^\'"<]+)#'
        );
        if (!$url) {
            $this->log('Change password form URL not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->get($url);
        $this->_dump('pass-change.entry.form.html');
        if (!$this->get_form('name', 'f1')) {
            throw new Actor_Live_Exception(
                'Password change entry form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        unset($this->_form['cancel']);
        $this->_form['login'] = $user_id;
        $this->_dump('pass-change.entry.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('pass-change.form.html');
        if ($this->_change_pass($user_id, $new_pass)) {
            return true;
        }

        $this->log('Failed changing password using e-mail',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function change_pass_by_secret_question($user_id, $secret_question, $location, $new_pass)
    {
        $this->log("Changing {$user_id} password using secret question/location");
        $this->log($secret_question, Log_Abstract::LEVEL_DEBUG);
        $this->log($location, Log_Abstract::LEVEL_DEBUG);

        if (!$this->_init_pass_reset_form($user_id)) {
            return false;
        } else if (!preg_match(
            '#href="([^"]+)"[^>]+id="i1602"#',
            $this->_response,
            $reset_url
        )) {
            throw new Actor_Live_Exception(
                'Password reset option URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $reset_url = html_entity_decode($reset_url[1], ENT_QUOTES);
        }

        $this->get($reset_url);
        $this->_dump('pass-reset.options.html');
        if (!$this->get_form('name', 'f1')) {
            throw new Actor_Live_Exception(
                'Password reset option form not found',
                Actor_Exception::PROXY_BANNED
            );
        } else if (false == strpos($this->_response, $secret_question[0])) {
            throw new Actor_Live_Exception(
                'Invalid secret question ' . serialize($secret_question),
                Actor_Exception::INVALID_ARGUMENT
            );
        }

        $this->_form['sa1'] = $secret_question[1];
        $this->_form['country'] = $location['country'];
        if ('US' == $location['country']) {
            $this->_form['state'] = $this->call_process_method(
                'get_region_id',
                $location['country'],
                $location['region']
            );
            $this->_form['zip'] = $location['postal_code'];
        } else {
            unset($this->_form['state'], $this->_form['zip']);
        }
        $this->_dump('reset-pass.sqsa.submit.txt', $this->_form);
        $this->submit();
        $this->_dump('reset-pass.sqsa.submit.html');
        if ($this->_change_pass($user_id, $new_pass)) {
            return true;
        }

        $this->log('Failed resetting password',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function __call($method, $args)
    {
        if ($this->_connection) {
            if ('ajax' == $method) {
                $args = array_pad($args, 5, null);
                if (!$args[4]) {
                    $args[4] = array();
                }
                $args[4]['X-Requested-With'] = null;
                $args[4]['X-FPP-Command'] = 0;
                $args[4]['sc'] = $this->_connection->get_cookie('sc');
            }
        }
        parent::__call($method, $args);
        if (500 <= $this->_connection->status_code) {
            throw new Actor_Live_Exception(
                'Service temporarily unavailable',
                Actor_Exception::SERVICE_ERROR
            );
        } else if (false !== strpos(
            $this->_response,
            '<!-------Error Info--'
        )) {
            $this->_dump('service-error.html');
            throw new Actor_Live_Exception(
                'Service error occured',
                Actor_Exception::SERVICE_ERROR
            );
        } else {
            $this->_redirect();
            return $this->_response;
        }
    }
}
