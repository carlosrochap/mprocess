<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Live
 */
class Actor_Live_Registrator
    extends Actor_Live
    implements Actor_Interface_Registrator
{
    const REG_HOST = 'https://signup.live.com';

    const REG_FORM_URL   = '/signup.aspx';
    const REG_SUBMIT_URL = '/create.aspx';

    const CHECK_CREDENTIAL_URL = '/checkavail.aspx';

    const CAPTCHA_URL = 'https://hipservice.live.com/hipImageDirect.srf';


    protected $_common_query = array(
        'rollrs' => 12,
        'lic'    => 1,
    );


    /**
     * Decodes Live.com CAPTCHA
     *
     * @param string $src Optional registration page content
     * @return array|false
     */
    protected function _decode_captcha($src=null)
    {
        if (!preg_match(
            '#SignUp\.Hip={cfg:"(?P<cfg>[^"]+)",id:(?P<id>\d+)#',
            ((null !== $src)
                ? $src
                : $this->_response),
            $m
        )) {
            throw new Actor_Live_Exception(
                'CAPTCHA id/cfg not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $url = new Url(self::CAPTCHA_URL);
        $url->query = array(
            'id'     => (int)$m['id'],
            'config' => 'Easy4Char',  //$m['cfg'],
            'tk'     => $this->_get_timestamp(),
        );
        $result = parent::_decode_captcha($url);
        if ($result) {
            $result[1] = strtoupper($result[1]);
        }
        return $result;
    }

    /**
     * Updates locality details
     *
     * @param array
     * @return array
     */
    protected function _update_location(array $details)
    {
        do {
            $location = $this->call_process_method('get_location', 'US');
            if (!$location) {
                throw new Actor_Live_Exception('No US locations to use');
            }
            $location['region_id'] = $this->call_process_method(
                'get_region_id',
                $location['country'],
                $location['region']
            );
        } while (!$location['region_id']);
        return array_merge($details, $location);
    }

    /**
     * Encrypts Live.com sign up data
     *
     * @param string    $src  Optional registration page content
     * @param Html_Form $form Optional form to take the data from
     * @return string
     */
    protected function _encrypt_signup_data($src=null, Html_Form $form=null)
    {
        $this->log('Encrypting sign up data');

        if (null === $src) {
            $src = $this->_response;
        }
        if (!$form) {
            $form = $this->_form;
        }

        $data = array("{$form['imembernamelive']}@{$form['idomain']}");
        if ($form['iAltEmail']) {
            $enc_type = 'pwd';
            $data[] = $form['iAltEmail'];
            $data[] = '';
        } else {
            $enc_type = 'pwdsa';
            $data[] = '';
            $data[] = $form['iSQ'];
        }
        $enc_keys = $this->_get_encryption_keys();
        $s = $enc_keys
            ? $this->_encrypt($form['iPwd'], $enc_keys, $form['iSA'], $enc_type)
            : null;
        if (!$s) {
            throw new Actor_Live_Exception('Signup data encryption failed');
        } else {
            $data[] = $s['data'];
            $data[] = $enc_keys['SKI'];
        }
        $data[] = $form['iHIP'];
        $data[] = $form['iCountry'];
        foreach (array(
            'iRegion',
            'iZipCode',
            'iBirthYear',
            'profile_gender',
            'iFirstName',
            'iLastName',
        ) as $k) {
            $data[] = !empty($form[$k])
                ? $form[$k]
                : '';
        }
        $data[] = 0;  // Timezone offset in minutes
        $data[] = 0;  // Whether the user has selected a member name offered
        $data[] = isset($form['iOptinEmail']);
        return implode('$', $data);
    }

    /**
     * Registers Live.com account
     *
     * @param string $email
     * @param string $pass
     * @param array  $details
     * @return bool
     */
    protected function _register($email, $pass, array $details)
    {
        $this->get(self::HOST);
        $this->_dump('homepage.html');
        $this->get(
            self::REG_HOST . self::REG_FORM_URL,
            $this->_common_query
        );
        $this->_dump('form.html');
        if (!$this->get_form('id', 'SignUpForm')) {
            throw new Actor_Live_Exception(
                'Registration form not found',
                Actor_Exception::PROXY_BANNED
            );
        }
        if (!preg_match_all(
            '#<option value="(?P<domain>(?:live|hotmail)\.[^"]+)#',
            $this->response,
            $m
        )) {
            throw new Actor_Live_Exception(
                'Domains list not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $this->_form['idomain'] = $details['domain'] =
                $m['domain'][array_rand($m['domain'])];
        }

        $details = $this->_update_location($details);

        $k = 'user_id';
        for ($i = self::RETRY_COUNT; $i; $i--) {
            if (!$this->is_email_available(
                "{$details[$k]}@{$details['domain']}"
            )) {
                $details[$k] = Pool_Generator::generate_username($details);
            } else {
                break;
            }
        }
        if (!$i) {
            $this->log('Available user ID not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $this->_form['imembernamelive'] = $details[$k];
        }

        unset($this->_form['iOptinEmail']);
        $this->_form['iPwd'] = $this->_form['iRetypePwd'] = $pass;
        $this->_form['iAltEmail'] = $email;
        $this->_form['iFirstName'] = $details['first_name'];
        $this->_form['iLastName'] = $details['last_name'];
        $this->_form['profile_gender'] = strtolower($details['gender']);
        $this->_form['iBirthYear'] = $details['birthday'][0];

        for ($i = self::RETRY_COUNT; $i; $i--) {
            $this->_form['iCountry'] = $details['country'];
            $this->_form['iRegion'] = $details['region_id'];
            $this->_form['iZipCode'] = $details['postal_code'];

            $captcha = $this->_decode_captcha();
            if (!$captcha) {
                continue;
            }

            $this->_form['iHIP'] = $captcha[1];
            $this->_dump("submit.{$i}.data.txt", $this->_form->to_array());
            $signup_data = $this->_encrypt_signup_data();
            $this->_connection->set_cookie(
                'signupdata',
                str_replace('%40', '@', urlencode($signup_data)),
                str_replace('https://', '.', self::REG_HOST)
            );
            $this->_dump("submit.{$i}.cookie.txt", $signup_data);
            $this->_connection->get(
                self::REG_HOST . self::REG_SUBMIT_URL,
                array_merge(
                    $this->_common_query,
                    array('sutk' => $this->_get_timestamp())
                )
            );
            $result = explode('~', $this->_connection->get_cookie('signupdata'), 3);
            $this->_dump("submit.{$i}.result.txt", $result);
            if (false === strpos($result[0], 'error')) {
                if ('loginurl' == $result[0]) {
                    $this->get(urldecode($result[1]));
                    $this->_dump('submit.redirect.html');
                }
                $this->user_id =
                    $details['user_id'] . '@' . ltrim($details['domain'], '@');
                return $details;
            } else if (('errorredirect' == $result[0]) && (450 == $result[1])) {
                throw new Actor_Live_Exception(
                    'Proxy overused',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $result[1] = explode(',', $result[1]);
            if ('iHIPError' == $result[1][0]) {
                $this->log('Invalid CAPTCHA',
                           Log_Abstract::LEVEL_ERROR);
                $this->_get_captcha_decoder()->report($captcha[0], false);
            } else if ('iZipCodeError' == $result[1][0]) {
                $this->log("Invalid {$details['country']} postal code {$details['postal_code']}",
                           Log_Abstract::LEVEL_ERROR);
                $details = $this->_update_location($details);
            }
        }

        return false;
    }


    /**
     * Checks if Live.com email is available
     *
     * @param string $email
     * @return bool
     */
    public function is_email_available($email)
    {
        $this->log("Checking {$email} availability");

        $this->_connection->get(
            self::REG_HOST . self::CHECK_CREDENTIAL_URL,
            array_merge($this->_common_query, array(
                'chkavail' => &$email,
                'tk'       => $this->_get_timestamp(),
            )),
            self::REG_HOST . self::REG_FORM_URL
        );
        return ('Error_1006' == $this->_connection->get_cookie('CheckAvail'));
    }

    public function register($email, $pass)
    {
        $this->log("Registering {$email}:{$pass}");

        $details = $this->call_process_method('get_profile_details');
        if (!$details) {
            $this->log('Profile details not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $details['user_id'] = Pool_Generator::generate_username($details);
            $details['secret_question'] =
                $this->call_process_method('get_secret_question');
        }

        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        try {
            $result = $this->_register($email, $pass, $details);
        } catch (Actor_Exception $e) {
            $result = $e;
        }
        $this->_connection->follow_refresh = $old_follow_refresh;

        if ($result instanceof Exception) {
            throw $result;
        } else {
            if (!$result) {
                $this->log('Registration failed',
                           Log_Abstract::LEVEL_ERROR);
            }
            return $result;
        }
    }
}
