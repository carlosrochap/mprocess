<?php

class Actor_Drogi_Registrator
    extends Actor_Drogi
    implements Actor_Interface_Registrator
{
    const REG_CAPTCHA_URL  = '/cs/anti_robot';
    const REG_SUBMIT_URL   = '/email/scripts/collectRegistrationInfo.pl';
    const REG_CONTINUE_URL = '/email/scripts/coReg1.pl';


    static protected $_countries = array(
        'US' => 'United States',
        'CA' => 'Canada',
    );
    static protected $_street_suffix = array('Str', 'Av', 'Dr', 'Pwy', 'Cir');


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

        $this->get(self::HOST . self::SVC_MENU_URL, 'user=new');
        $this->_dump('svc.menu.html');
        if (!$this->get_form('name', 'myForm')) {
            throw new Actor_Drogi_Exception(
                'Service menu form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['item'] = 'free';
        $this->_dump('svc.menu.txt', $this->_form->to_array());
        $this->submit();
        $this->_dump('form.html');
        if (!$this->get_form('action', basename(self::REG_SUBMIT_URL))) {
            throw new Actor_Drogi_Exception(
                'Registration form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['loginName'] = $details['user_id'] =
            Pool_Generator::generate_username($details['username']);
        $this->_form['password'] = $this->_form['passwordConfirm'] = $pass;

        $k = 'about_me';
        $i = strpos($details[$k], ' ', 16);
        $this->_form['passwordQuestion'] = substr($details[$k], 0, ((false !== $i)
            ? $i
            : strlen($details[$k])));
        $this->_form['passwordAnswer'] = substr(
            $details[$k],
            strrpos($details[$k], ' ') + 1
        );

        foreach (array('first', 'last') as $k) {
            $this->_form["{$k}Name"] = $details["{$k}_name"];
        }

        $details['country'] = array_rand(self::$_countries);
        $a = $this->call_process_method('get_location', $details['country']);
        if (!$a) {
            $this->log("No {$details['country']} locations found",
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            foreach (array('region', 'locality', 'postal_code') as $k) {
                $details[$k] = $a[$k];
            }
        }

        $a = array(
            (string)rand(1, 999),
            $this->call_process_method('get_last_name') . ' ' .
                self::$_street_suffix[array_rand(self::$_street_suffix)]
        );
        shuffle($a);
        $this->_form['streetAddress'] = implode(', ', $a);
        $this->_form['city'] = $details['locality'];
        $this->_form['region'] = $details['region'];
        $this->_form['country'] = self::$_countries[$details['country']];
        $this->_form['postalCode'] = $details['postal_code'];
        $this->_form['altEmailAddress'] = $email;
        list(
            $this->_form['birthYear'],
            $this->_form['birthMonth'],
            $this->_form['birthDay']
        ) = $details['birthday'];
        $this->_form['gender'] = strtolower($details['gender']);
        $this->_form['primaryLang'] = 1;  // EN
        $this->_form['householdIncome'] = rand(3, 6);  // $25k-99.9k
        $this->_form['occupation'] = rand(1, 33);
        $this->_form['industry'] = rand(1, 23);
        $this->_form['interests'] = rand(1, 15);

        for ($i = self::RETRY_COUNT; $i; $i--) {
            if (preg_match('#anti_robot/(\d+)\.jpg#', $this->_response, $m)) {
                $captcha = $this->_decode_captcha(
                    self::HOST . self::REG_CAPTCHA_URL . "/{$m[1]}.jpg"
                );
                if (!$captcha) {
                    continue;
                } else {
                    $this->_form['humanTest'] = $captcha[1];
                }
            } else {
                $captcha = false;
            }

            $this->_dump("submit.{$i}.txt", $this->_form->to_array());
            $this->submit();
            $this->_dump("submit.{$i}.html");
            if (false !== strpos(
                $this->_response,
                '"' . basename(self::REG_CONTINUE_URL) . '"'
            )) {
                $this->get_form('action', basename(self::REG_CONTINUE_URL));
                $this->submit();
                $this->_dump('continue.submit.html');
                if (false !== strpos($this->_response, 'Created Successfully')) {
                    $this->user_id = $details['user_id'];
                    return $details;
                }
            } else if ($captcha && (false !== strpos(
                $this->_response,
                'Text does not match the numbers in image'
            ))) {
                $this->log('Invalid CAPTCHA',
                           Log_Abstract::LEVEL_ERROR);
                $this->_get_captcha_decoder()->report($captcha[0], false);
            }
        }

        $this->log('Registration failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
