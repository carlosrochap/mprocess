<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage PocztaPl
 */
class Actor_PocztaPl_Registrator
    extends Actor_PocztaPl
    implements Actor_Interface_Registrator
{
    const REG_URL = '/site/s,rejestracja_free.html';

    const CAPTCHA_URL = '/code';


    static protected $_states = array(
        'kujawsko-pomorskie',
        'lubelskie',
        'lubuskie',
        'łódzkie',
        'małopolskie',
        'mazowieckie',
        'opolskie',
        'podkarpackie',
        'podlaskie',
        'pomorskie',
        'śląskie',
        'świętokrzyskie',
        'warmińsko-mazurskie',
        'wielkopolskie',
        'zachodniopomorskie'
    );
    static protected $_education = array(
        'zasadnicze zawodowe',
        'średnie',
        'policealne',
        'licencjat',
        'wyższe',
    );
    static protected $_field = array(
        'computer-related IS, MIS, DP, Internet',
        'computer-related hardware',
        'computer-related software',
        'education, research',
        'engineering/construction',
        'manufacturing/distribution',
        'business supplies or services',
        'medical/health services',
        'entertainment/media/publishing',
        'hospitality travel/accommodations',
        'consumer retail/wholesale',
        'onprofit/membership organizations',
        'government',
        'legal services',
        'other',
    );
    static protected $_occupation = array(
        'professional doctor, lawyer, etc.',
        'academic/educator',
        'academic/tech',
        'other technical/engineering',
        'service/customer support',
        'clerical/administrative',
        'sales/marketing',
        'tradesman/craftsman',
        'college/graduate student',
        'K-12 student',
        'homemaker',
        'self-employed/own company',
        'unemployed, looking for work',
        'retired',
        'other',
    );


    protected function _decode_captcha($sesid)
    {
        $url = new Url(self::SECURE_HOST . self::CAPTCHA_URL);
        $url->query = array('sesid' => &$sesid);
        return parent::_decode_captcha($url, 'png');
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

        $url = self::SECURE_HOST . self::REG_URL;
        $this->get($url, null, self::HOST . '/');
        $this->_dump('step.1.form.html');

        // Step #1
        $details['user_id'] = Pool_Generator::generate_username($details['username']);
        while (true) {
            $this->post($url, http_build_query(array(
                'step1' => 1,
                'login' => &$details['user_id'],
            )), null, $url);
            $this->_dump('step.1.submit.html');
            if (!preg_match(
                '#value="(' . preg_quote($details['user_id']) . '\d)"#',
                $this->_response,
                $m
            )) {
                break;
            } else {
                $details['user_id'] = html_entity_decode($m[1], ENT_QUOTES);
            }
        }
        if (!$this->get_form('id', 'regform')) {
            throw new Actor_PocztaPl_Exception(
                'Step #2 form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        // Step #2
        unset($this->_form['pyt']);
        $this->_form['haslo1'] = $this->_form['haslo2'] = $pass;
        $this->_form['pyt_preset'] = 'Drugie imię matki / ojca';
        $opposite_gender = ('F' == $details['gender']) ? 'M' : 'F';
        $this->_form['odp'] =
            $this->call_process_method('get_first_name', $opposite_gender) . ' ' .
            $this->call_process_method('get_last_name', $opposite_gender);
        $this->_dump('step.2.submit.txt', $this->_form->to_array());
        $this->submit($url);
        $this->_dump('step.2.submit.html');
        if (!$this->get_form('name', 'add')) {
            throw new Actor_PocztaPl_Exception(
                'Step #3 form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        // Step #3
        $this->_form['imie'] = $details['first_name'];
        $this->_form['nazwisko'] = $details['last_name'];
        $this->_form['innyemail'] = $email;
        list(
            $this->_form['birthyear'],
            $this->_form['birthmonth'],
            $this->_form['birthday']
        ) = $details['birthday'];
        $this->_form['plec'] = strtoupper($details['gender'][0]);
        if (!$details['postal_code']) {
            $a = $this->call_process_method('get_location', $details['country']);
            if ($a && $a['postal_code']) {
                foreach (array('region', 'locality', 'postal_code') as $k) {
                    $details[$k] = $a[$k];
                }
            }
        }
        $this->_form['miejscowosc'] = "{$details['locality']}, {$details['region']}";
        $this->_form['kodpocztowy'] = $details['postal_code'];
        $this->_form['plec'] = strtoupper($details['gender'][0]);
        $this->_form['country'] =
            strtolower(str_replace('GB', 'UK', $details['country']));
        foreach (array(
            'state'         => &self::$_states,
            'wyksztalcenie' => &self::$_education,
            'branza'        => &self::$_field,
            'zawod'         => &self::$_occupation,
        ) as $k => $v) {
            $this->_form[$k] = $v[array_rand($v)];
        }
        for ($i = rand(1, self::RETRY_COUNT); $i; $i--) {
            $v = rand(0, 20);
            $this->_form["zaint[{$v}]"] = $v + 1;
        }

        for ($i = self::RETRY_COUNT; $i; $i--) {
            $captcha = $this->_decode_captcha($this->_form['sesid']);
            if (!$captcha) {
                continue;
            } else {
                $this->_form['kod'] = $captcha[1];
            }

            unset($this->_form['pyt']);
            $this->_form['ver'] = 1;
            $this->_dump("step.3.submit.{$i}.txt", $this->_form->to_array());
            $this->submit($url);
            $this->_dump("step.3.submit.{$i}.html");
            if (false !== mb_strpos($this->_response, 'GRATULUJEMY!')) {
                $this->user_id = $details['user_id'];
                return true;
            } else if (false === mb_strpos($this->_response, 'kod był błędny')) {
                $this->_get_captcha_decoder()->report($captcha[0], false);
            } else if (!$this->get_form('name', 'add')) {
                throw new Actor_PocztaPl_Exception(
                    'Step #3 form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }
        }

        $this->log('Registration failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
