<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Live
 */
class Actor_Hotmail_Registrator
    extends Actor_Hotmail
    implements Actor_Interface_Registrator
{
    const REG_CHECK_EMAIL_URL = '/pp750/memberexists.srf';


    protected $_reg_cookies = array();


    /**
     * @ignore
     */
    protected function _decode_captcha($url, $ext='jpg')
    {
        $result = parent::_decode_captcha($url, $ext);
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
        $location = $this->call_process_method('get_location', 'US');
        if (!$location) {
            $this->log('No US locations to use',
                       Log_Abstract::LEVEL_ERROR);
            return $details;
        } else {
            return array_merge($details, $location);
        }
    }

    /**
     * Registers Hotmail.com account
     *
     * @param string $email
     * @param string $pass
     * @param array  $details
     * @return bool
     */
    protected function _register($email, $pass, array $details)
    {
        $this->get(self::PASSPORT_HOST);
        $this->_dump('homepage.html');
        if (!preg_match('#"reg":u="(.+?)";break;#', $this->_response, $m)) {
            throw new Actor_Hotmail_Exception(
                'Registration entry form URL not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->get(stripcslashes($m[1]));
        $this->_dump('entry-form.html');
        if (!$this->get_form('name', 'regProfileForm')) {
            throw new Actor_Hotmail_Exception(
                'Registration entry form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['HasEmail'] = 0;
        $this->submit();
        $this->_dump('entry-form.submit.html');
        if (!preg_match('#; URL=\?([^"]+)#', $this->_response, $m)) {
            throw new Actor_Hotmail_Exception(
                'Registration form URL not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $url = new Url($this->_connection->last_url);
        $url->query = html_entity_decode($m[1], ENT_QUOTES);
        $this->get($url);
        $this->_dump('form.html');
        if (!$this->get_form('name', 'oF')) {
            throw new Actor_Hotmail_Exception(
                'Registration form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        if (!preg_match(
            '#"iEmailDomain">([^<]+)#',
            $this->_response,
            $details['domain']
        )) {
            throw new Actor_Hotmail_Exception(
                'E-mail domain not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $details['domain'] = html_entity_decode(
                $details['domain'][1],
                ENT_QUOTES
            );
        }

        if (!preg_match(
            '#>DrDw2\("[^"]+(1003~Alabama[^"]+)#',
            $this->_response,
            $m
        )) {
            throw new Actor_Hotmail_Exception(
                'Default (US) regions list not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $regions = array();
            for ($a = explode('~', $m[1]), $l = count($a), $i = 0; $i < $l; $i += 2) {
                $regions[(int)$a[$i]] = $a[$i + 1];
            }
            $this->log('Regions: ' . serialize($regions),
                       Log_Abstract::LEVEL_DEBUG);
        }

        list(
            $this->_form['p200000000000bb8'],
            $this->_form['p200000000000bb9']
        ) = $details['secret_question'];
        $this->_form['p65'] = $email;
        $this->_form['p3e8'] = $this->_form['p10000000'] = $pass;
        $this->_form['pff00000000010007'] = strtolower($details['gender']);
        $this->_form['pff00000000010001'] = $details['first_name'];
        $this->_form['pff00000000010002'] = $details['last_name'];
        $k = 'pff00000000010029';
        $this->_form[$k] = implode(':', array_reverse($details['birthday']));
        list(
            $this->_form["{$k}Year"],
            $this->_form["{$k}Month"],
            $this->_form["{$k}Day"]
        ) = $details['birthday'];
        $this->_form['pff0000000001000b'] = 0;
        $this->_form['pff0000000001000e'] = 4;
        $this->_form['pff00000020000011'] = 0;

        for ($i = self::RETRY_COUNT; $i; $i--) {
            if (!$details['region'] || !$details['postal_code']) {
                for ($j = self::RETRY_COUNT; $j; $j--) {
                    $details = $this->_update_location($details);
                    $region = array_search($details['region'], $regions);
                    if (false !== $region) {
                        break;
                    }
                }
                if (!$j) {
                    $this->log("Unknown {$details['country']} region {$details['region']}",
                               Log_Abstract::LEVEL_ERROR);
                    break;
                }
            }

            $this->_form['pff00000000010004'] = $details['country'];
            $this->_form['pff00000000010005'] = $region;
            $this->_form['pff00000000010006'] = $details['postal_code'];

            $k = 'user_id';
            for ($j = self::RETRY_COUNT; $j; $j--) {
                $details[$k] = Pool_Generator::generate_username($details);
                if ($this->is_email_available(
                    "{$details[$k]}{$details['domain']}"
                )) {
                    break;
                }
            }
            if (!$j) {
                $this->log('No valid user IDs found',
                           Log_Abstract::LEVEL_ERROR);
                break;
            }

            $captcha = false;
            if (preg_match('#(/gethip\.srf\?[^"]+)#', $this->_response, $m)) {
                $response = $this->_connection->get(
                    self::PASSPORT_HOST . html_entity_decode($m[1], ENT_QUOTES),
                    null,
                    $url
                );
                $this->_dump('captcha.data.js', $response);
                if (!preg_match('#,hipfrtoken:"([^"]+)#', $response, $ctoken)) {
                    throw new Actor_Hotmail_Exception(
                        'CAPTCHA token not found',
                        Actor_Exception::PROXY_BANNED
                    );
                } else {
                    $ctoken = stripcslashes($ctoken[1]);
                }
                if (!preg_match('#(/gethipdata\.srf\?[^\|]+)#', $response, $curl)) {
                    throw new Actor_Hotmail_Exception(
                        'CAPTCHA image URL not found',
                        Actor_Exception::PROXY_BANNED
                    );
                } else {
                    $curl = self::PASSPORT_HOST . stripcslashes($curl[1]);
                }

                for ($j = self::RETRY_COUNT; $j && !$captcha; $j--) {
                    $captcha = $this->_decode_captcha($curl);
                }
                if (!$captcha) {
                    continue;
                }

                $this->_form['p10000002'] =
                    "Fr=hard,Solution={$captcha[1]}," .
                    "HIPFrame={$ctoken}|HIPChallenge0=" .
                    urlencode($this->_connection->get_cookie('HIPChallenge0'));
            }

            $this->_form['p4181'] = $details['user_id'];
            $this->_form['p1000000e'] =
                "{$details['user_id']}{$details['domain']}";
            $this->_dump("submit.{$i}.txt", $this->_form->to_array());
            $this->submit();
            $this->_dump("submit.{$i}.html");
            if (false !== strpos($this->_response, 'regcongrats.srf')) {
                if ($this->get_form('method', 'post')) {
                    $this->submit();
                    $this->_dump("submit.redirect.html");
                }
                $this->user_id =
                    $details['user_id'] . '@' . ltrim($details['domain'], '@');
                return $details;
            }
            if (false !== strpos($this->_response, 'span id="iMemExists"')) {
                $this->log("{$details['user_id']} is already registered",
                           Log_Abstract::LEVEL_ERROR);
            }
            if (false !== strpos(
                $this->_response,
                'id="idff00000000010005_Error9"'
            )) {
                $this->log('Invalid region code',
                           Log_Abstract::LEVEL_ERROR);
                $details['region'] = '';
            }
            if (false !== strpos(
                $this->_response,
                'id="idff00000000010006_Error9"'
            )) {
                $this->log('Invalid postal code',
                           Log_Abstract::LEVEL_ERROR);
                $details['postal_code'] = '';
            }
            if ($captcha && (
                preg_match('#id="iHIPErr">[^<]#', $this->_response) ||
                (false !== strpos($this->_response, 'id10000002_Error9'))
            )) {
                $this->log('Invalid CAPTCHA',
                           Log_Abstract::LEVEL_ERROR);
                $this->_get_captcha_decoder()->report($captcha[0], false);
            }
        }

        return false;
    }


    /**
     * @see Actor_Live::init()
     */
    public function init()
    {
        $this->_reg_cookies = array(
            'BrowserTest' => (string)rand(100000000, 999999999),
            's_cc'        => 'true',
            's_sq'        => '[[B]]',
        );
        return parent::init();
    }

    /**
     * Checks if Hotmail.com email is available
     *
     * @param string $email
     * @return bool
     */
    public function is_email_available($email)
    {
        $this->log("Checking {$email} availability");

        $k = 'MSPMemberExists';
        $this->_connection->set_cookie($k, $email, str_replace(
            'https://',
            '',
            self::PASSPORT_HOST
        ));
        $this->_connection->get(
            self::PASSPORT_HOST . self::REG_CHECK_EMAIL_URL,
            array('x' => $this->_get_timestamp())
        );
        if ($email == $this->_connection->get_cookie($k)) {
            return true;
        }
        $this->_connection->remove_cookie($k);
        return false;
    }

    public function register($email, $pass)
    {
        $this->log("Registering {$email}:{$pass}");

        $details = $this->call_process_method('get_profile_details');
        if (!$details) {
            $this->log('Profile details not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } {
            $details['country'] = $details['region'] = $details['postal_code'] = '';
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
            throw $e;
        } else {
            if (!$result) {
                $this->log('Registration failed',
                           Log_Abstract::LEVEL_ERROR);
            }
            return $result;
        }
    }

    public function __call($method, $args)
    {
        if ($this->_connection) {
            $this->_connection->set_cookie($this->_reg_cookies, null, str_replace(
                'https://',
                '',
                self::PASSPORT_HOST
            ));
        }
        return parent::__call($method, $args);
    }
}
