<?php

class Actor_LiveMobile_Registrator
    extends Actor_LiveMobile
    implements Actor_Interface_Registrator
{
    const REG_CAPTCHA_URL = '/HIPImage.ashx';


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
            $details['user_id'] = Pool_Generator::generate_username($details);
            $details['secret_question'] =
                $this->call_process_method('get_secret_question');
        }

        $location = $this->call_process_method('get_location', 'US');
        if (!$location) {
            $this->log('No US locations to use',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $details = array_merge($details, $location);
        }

        $this->get(self::MOBILE_LOG_IN_HOST);
        $this->_dump('login.html');
        if (!preg_match(
            '#<a href="([^"]+)"[^>]*>Sign up<#',
            $this->_response,
            $m
        )) {
            throw new Actor_LiveMobile_Exception(
                'Registration form URL not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $this->get(
                self::MOBILE_LOG_IN_HOST .
                html_entity_decode($m[1], ENT_QUOTES)
            );
        }

        for ($i = self::RETRY_COUNT; $i; $i--) {
            $this->_dump("form.{$i}.html");
            if (!$this->get_form('id', 'SignupForm')) {
                throw new Actor_LiveMobile_Exception(
                    'Registration form not found',
                    Actor_Exception::PROXY_BANNED
                );
            } else if (!preg_match(
                '#name="DomainList">([\s\S]+)</select>#',
                $this->_response,
                $m
            ) || !preg_match_all(
                '#value="(?P<idx>\d+)".*?>(?P<domain>[^<]+)#',
                $m[1],
                $m,
                PREG_SET_ORDER
            )) {
                throw new Actor_LiveMobile_Exception(
                    'Domains list not found',
                    Actor_Exception::PROXY_BANNED
                );
            } else {
                $domains = array();
                foreach ($m as $v) {
                    $domains[$v['idx']] = $v['domain'];
                }
                $this->log('Domains: ' . serialize($domains),
                           Log_Abstract::LEVEL_DEBUG);
                $this->_form['DomainList'] = array_rand($domains);
                $details['domain'] = $domains[$this->_form['DomainList']];
            }

            $captcha = false;
            if (preg_match(
                '#' . preg_quote(self::REG_CAPTCHA_URL) . '[^"]+#',
                $this->_response,
                $m
            )) {
                $captcha = $this->_decode_captcha(
                    self::MOBILE_LOG_IN_HOST .
                    html_entity_decode($m[0], ENT_QUOTES)
                );
                if (!$captcha) {
                    continue;
                } else {
                    $this->_form['HIPControl$HIPSolutionTextBox'] = $captcha[1];
                }
            }

            unset($this->_form['CancelSignupCmd']);
            $k = 'SigninNameSelection';
            if (isset($this->_form[$k])) {
                $this->_form[$k] = 1;
            }
            $this->_form['SigninNameTextBox'] = $details['user_id'];
            $this->_form['PasswordControl$PasswordTextBox'] =
                $this->_form['PasswordControl$RetypePWDTextBox'] = $pass;
            $this->_form['AlternateEmailTextBox'] = $email;
            $this->_form['BirthYearTextBox'] = $details['birthday'][0];
            $this->_dump("submit.{$i}.txt", $this->_form->to_array());
            $this->submit();
            $this->_dump("submit.{$i}.html");
            if (false !== strpos($this->_response, 'Windows Live ID created')) {
                $this->user_id = $details['user_id'] . $details['domain'];
                if (preg_match(
                    '#cid=([A-Fa-f\d]+)#',
                    $this->get(self::MOBILE_HOST),
                    $m
                )) {
                    $this->cid = $m[1];
                }
                return $details;
            }
            if ($captcha && (false !== strpos(
                $this->_response,
                self::REG_CAPTCHA_URL
            ))) {
                $this->log('Invalid CAPTCHA',
                           Log_Abstract::LEVEL_ERROR);
                $this->_get_captcha_decoder()->report($captcha[0], false);
            }
            if (false !== strpos($this->_response, 'enter a different ID')) {
                $this->log("{$details['user_id']} is already registered",
                           Log_Abstract::LEVEL_ERROR);
            }
            if (false !== strpos($this->_response, "can't sign up right now")) {
                throw new Actor_LiveMobile_Exception(
                    'Service is down',
                    Actor_Exception::SERVICE_ERROR
                );
            }
        }

        $this->log('Registration failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
