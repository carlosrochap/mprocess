<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Yahoo
 */
class Actor_Yahoo_Registrator
    extends Actor_Yahoo
    implements Actor_Interface_Registrator
{
    const REGISTER_URL      = '/registration?intl=us&origIntl=&done=http%3A%2F%2Fmy.yahoo.com&src=&last=&partner=yahoo_default&domain=&yahooid=';
    const REGISTER_JSON_URL = '/reg_json';

    const DEFAULT_COUNTRY = 'US';


    static protected $_domains = array('yahoo', 'ymail', 'rocketmail');
    static protected $_postal_code_countries = array(
        'AD', 'AM', 'AR', 'AS', 'AU', 'AX', 'AZ',
        'BA', 'BD', 'BG', 'BH', 'BM', 'BR', 'BY',
        'CA', 'CC', 'CH', 'CN', 'CR', 'CX', 'CY', 'CZ',
        'DE', 'DK', 'DZ',
        'EE', 'EG', 'ES',
        'FM', 'FO', 'FR',
        'GB', 'GE', 'GF', 'GL', 'GP', 'GR', 'GT', 'GU', 'GW',
        'HN', 'HR', 'HT', 'HU',
        'ID', 'IL', 'IN', 'IR', 'IS', 'IT',
        'JO', 'JP',
        'KE', 'KG', 'KR', 'KZ',
        'LA', 'LI', 'LK', 'LT', 'LU', 'LV',
        'MA', 'MC', 'MD', 'ME', 'MG', 'MH', 'MK', 'MN', 'MP', 'MQ', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        'NC', 'NF', 'NO', 'NP', 'NZ',
        'OM',
        'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PR', 'PS', 'PT', 'PW', 'PY',
        'RE', 'RO', 'RS', 'RU',
        'SD', 'SE', 'SG', 'SI', 'SK', 'SM', 'SV', 'SZ',
        'TH', 'TJ', 'TL', 'TM', 'TN', 'TR', 'TW',
        'UK', 'UM', 'US', 'UY', 'UZ',
        'VA', 'VE', 'VI',
        'WF',
        'YT',
        'ZA', 'ZM'
    );


    /**
     * @ignore
     */
    protected function _register($email, $pass, array $details)
    {
        $this->get(self::HOST);
        $this->_dump('homepage.html');
        if (!preg_match(
            '#href=\'([^\']+)\'>\s+(?:Sign up|Create New Account)#',
            $this->_response,
            $signup_url
        )) {
            throw new Actor_Yahoo_Exception(
                'Sign up URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $signup_url = html_entity_decode($signup_url[1], ENT_QUOTES);
            $this->log("Sign up URL: {$signup_url}",
                       Log_Abstract::LEVEL_DEBUG);
        }

        $s = $this->get_suggested_email($details);
        if (!$s) {
            return false;
        } else {
            list($details['user_id'], $details['domain']) = explode('@', $s, 2);
        }

        $this->get($signup_url);
        $this->_dump('form.html');

        for ($i = self::RETRY_COUNT; $i; $i--) {
            if (!$this->get_form('id', 'regFormBody')) {
                throw new Actor_Yahoo_Exception(
                    'Registration form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $this->_form['jsenabled'] = 1;
            $this->_form['firstname'] = $details['first_name'];
            $this->_form['secondname'] = $details['last_name'];
            $this->_form['gender'] = strtolower($details['gender']);
            list(
                $this->_form['yyyy'],
                $this->_form['mm'],
                $this->_form['dd']
            ) = $details['birthday'];
            $this->_form['yahooid'] = $details['user_id'];
            $this->_form['domain'] = $details['domain'];
            $this->_form['password'] = $this->_form['passwordconfirm'] = $pass;
            if (false !== strpos($email, '@')) {
                $this->_form['altemail'] = $email;
            }
            foreach (array(
                ''  => $details['first_name'],
                '2' => $details['last_name'],
            ) as $k => $v) {
                if (preg_match(
                    '#<select[^>]+name="secquestion' . $k . '"[^>]*>([\s\S]+)</select>#',
                    $this->_response,
                    $m
                ) && preg_match(
                    '#<option value="([^"]+)#',
                    $m[1],
                    $m
                )) {
                    $this->_form["secquestion{$k}"] =
                        html_entity_decode($m[1], ENT_QUOTES);
                    $this->_form["secquestionanswer{$k}"] =
                        (4 <= mb_strlen($v))
                            ? $v
                            : "{$v} {$v}";
                }
            }

            $this->_form['country'] = strtolower($details['country']);
            if (in_array($details['country'], self::$_postal_code_countries)) {
                $this->_form['binMapFld'] = 1802238;//1801982;
                $k = 'postal_code';
                for ($details[$k] = '', $j = self::RETRY_COUNT; $j && !$details[$k]; $j--) {
                    $a = $this->call_process_method(
                        'get_location',
                        $details['country']
                    );
                    if (!empty($a[$k]) && $this->is_valid_postal_code(
                        $details['country'],
                        $a[$k]
                    )) {
                        $details[$k] = $a[$k];
                    }
                }
                if (!$details[$k]) {
                    $this->log("No valid {$details['country']} postal codes found",
                               Log_Abstract::LEVEL_ERROR);
                    break;
                } else {
                    $this->_form['postalcode'] = $details[$k];
                }
            } else {
                $this->_form['binMapFld'] = 128;
            }

            for ($captcha = false, $j = self::RETRY_COUNT; $j && !$captcha; $j) {
                $captcha = $this->_decode_captcha(
                    self::CAPTCHA_HOST . "/img/{$this->_form['cdata']}.jpg"
                );
            }
            if (!$captcha) {
                break;
            } else {
                $this->_form['cword'] = $captcha[1];
            }

            if (preg_match('#\srandom_field:"([^"]+)"#', $this->_response, $m)) {
                $this->_form['random_field'] = md5("{$m[1]}{$this->_form['u']}");
            }
            $this->_dump("submit.{$i}.txt", $this->_form->to_array());
            $this->submit(self::EDIT_HOST . self::REGISTER_URL);
            $this->_dump("submit.{$i}.html");
            if (false !== strpos($this->_response, 'confirmation message was sent')) {
                $this->user_id = "{$details['user_id']}@{$details['domain']}";
                return $details;
            }
            if ((false !== strpos(
                $this->_response,
                'id="captchaFld" class="ymemforminput yflderr"'
            )) || (false !== strpos(
                $this->_response,
                'yflderr" id="captchaFld"'
            ))) {
                $this->log('Invalid CAPTCHA',
                           Log_Abstract::LEVEL_ERROR);
                $this->_get_captcha_decoder()->report($captcha[0], false);
            }
            if ((false !== strpos(
                $this->_response,
                'id="zipcodeFld" class="ymemforminput yflderr"'
            )) || (false !== strpos(
                $this->_response,
                'yflderr" id="zipcodeFld"'
            ))) {
                $this->log("Invalid {$details['country']} postal code {$details['postal_code']}",
                           Log_Abstract::LEVEL_ERROR);
                $details['postal_code'] = '';
            }
        }

        return false;
    }


    public function get_suggested_email(array $details)
    {
        $this->log('Fetching available e-mail');

        $seps = array('', '-', '_', '.');
        $a = array(
            &$details['first_name'],
            &$details['last_name'],
            &$details['username'],
        );
        shuffle($a);
        $email =
            mb_strtolower($a[0] . $seps[array_rand($seps)] . $a[1]) . '@' .
            self::$_domains[array_rand(self::$_domains)] . '.com';

        $this->get(self::EDIT_HOST . self::REGISTER_JSON_URL, array(
            'PartnerName'             => 'yahoo_default',
            'RequestVersion'          => 1,
            'AccountID'               => &$email,
            'GivenName'               => &$details['first_name'],
            'FamilyName'              => &$details['last_name'],
            'ApiName'                 => 'ValidateFields',
            'intl'                    => 'us',
            substr((string)time(), 3) => '',
        ));
        $this->_dump("suggest-email.{$email}.js");
        $this->_response = json_decode($this->_response, true);
        if (!$this->_response) {
            throw new Actor_Yahoo_Exception(
                'Invalid response',
                Actor_Exception::PROXY_BANNED
            );
        } else if ('SUCCESS' == $this->_response['ResultCode']) {
            return $email;
        } else {
            foreach (array('SuggestedDBIDList', 'SuggestedIDList') as $k) {
                if (is_array(@$this->_response[$k]) && count($this->_response[$k])) {
                    return $this->_response[$k][array_rand($this->_response[$k])];
                }
            }
        }

        $this->log('No suggested e-mails found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function is_valid_postal_code($country, $postal_code)
    {
        if (!$postal_code) {
            return false;
        }

        $this->log("Checking {$country} postal code {$postal_code}");

        $s = $this->_connection->get(
            self::EDIT_HOST . self::REGISTER_JSON_URL,
            array(
                'PartnerName'             => 'yahoo_default',
                'RequestVersion'          => 1,
                'PostalCode'              => &$postal_code,
                'Country'                 => strtolower($country),
                'ApiName'                 => 'ValidateFields',
                substr((string)time(), 3) => '',
            )
        );
        $this->_dump("validate.{$country}.{$postal_code}.js", $s);
        return (false !== strpos($s, '"SUCCESS"'));
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
        } else {
            foreach (array('first_name', 'last_name') as $k) {
                $details[$k] =
                    $this->call_process_method("get_{$k}", $details['gender']);
            }
        }

        $details['country'] = self::DEFAULT_COUNTRY;
        $details['postal_code'] = $details['region'] = $details['locality'] = '';

        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        try {
            $result = $this->_register($email, $pass, $details);
        } catch (Exception $e) {
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
