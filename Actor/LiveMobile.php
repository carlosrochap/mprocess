<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Live
 */
class Actor_LiveMobile extends Actor_Live
{
    const MOBILE_HOST        = 'http://mobile.live.com';
    const MOBILE_LOG_IN_HOST = 'https://mid.live.com';
    const MOBILE_PEOPLE_HOST = 'http://mpeople.live.com';
    const MOBILE_SPACES_HOST = 'http://mobile.spaces.live.com';

    const MOBILE_LOG_IN_URL  = '/si/login.aspx';
    const MOBILE_LOG_OUT_URL = '/si/logout.aspx';

    const MOBILE_PROFILE_URL = '/profile/default.aspx';

    const MOBILE_FRIENDS_URL = '/PSearch.mvc';


    protected $_friend_lists = array();


    protected function _get_mobile_spaces_host($cid=null, $home=false)
    {
        return str_replace(
            '://',
            '://cid-' . ((null === $cid) ? $this->_cid : $cid) . ($home ? '.home.' : '') . '.',
            self::MOBILE_SPACES_HOST
        );
    }


    public function init()
    {
        $this->_friend_lists = array();
        return parent::init();
    }

    public function login($user_id, $pass)
    {
        $this->log("Logging in as {$user_id}:{$pass}");

        $this->get(self::MOBILE_LOG_IN_HOST . self::MOBILE_LOG_IN_URL);
        $this->_dump('login.form.html');
        for ($i = self::RETRY_COUNT; $i; $i--) {
            if (!$this->get_form('id', 'EmailPasswordForm')) {
                throw new Actor_LiveMobile_Exception(
                    'Login form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            unset($this->_form['SavePasswordCheckBox']);
            $this->_form['LoginTextBox'] = $user_id;
            $this->_form['PasswordTextBox'] = $pass;
            $this->_dump("login.submit.{$i}.txt", $this->_form->to_array());
            $this->submit();
            $this->_dump("login.submit.{$i}.html");
            if (
                (false !== strpos($this->_response, 'Please try again')) &&
                (1 == $i)
            ) {
                throw new Actor_LiveMobile_Exception(
                    'Invalid credentials',
                    Actor_Exception::INVALID_CREDENTIALS
                );
            }

            $this->get(self::MOBILE_HOST);
            $this->_dump('homepage.html');
            if (false !== strpos($this->_response, self::MOBILE_LOG_OUT_URL)) {
                if (!preg_match(
                    '#<a id="_psm_view_name" href="[^"]+cid=([A-Fa-f\d]+)#',
                    $this->_response,
                    $cid
                )) {
                    throw new Actor_LiveMobile_Exception(
                        'User CID not found',
                        Actor_Exception::PROXY_BANNED
                    );
                }
                $this->cid = $cid[1];
                $this->user_id = $user_id;
                return true;
            }
        }

        $this->log('Login failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function logout()
    {
        if ($this->_connection) {
            $this->get(self::MOBILE_LOG_IN_HOST . self::MOBILE_LOG_OUT_URL);
        }
        return parent::logout();
    }

    public function get_friends($cid=null, $page=null, $filter='')
    {
        if (null === $cid) {
            $cid = $this->cid;
        }
        if ((null !== $page) || !isset($this->_friend_lists[$cid])) {
            $this->_friend_lists[$cid] = array(
                'page' => max(0, (int)$page),
                'last' => 0,
            );
        }

        $this->log("Fetching {$cid} friends, page {$this->_friend_lists[$cid]['page']}");

        $this->get(self::MOBILE_PEOPLE_HOST, array(
            'mkt' => 'en-US',
            'pg'  => $this->_friend_lists[$cid]['page'],
        ));
        $this->_dump("{$cid}.friends.{$this->_friend_lists[$cid]['page']}.html");
        if (preg_match_all(
            '#id="prlk(?P<idx>\d+)" href="[^"]+cid=(?P<cid>[A-Fa-f\d]+)#',
            $this->_response,
            $m
        )) {
            $last = max(array_map('intval', $m['idx']));
            if ($this->_friend_lists[$cid]['last'] != $last) {
                $this->_friend_lists[$cid]['last'] = $last;
                $this->_friend_lists[$cid]['page']++;
                return array_unique(array_map('strtolower', $m['cid']));
            }
        }

        $this->log('No friends found',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function __call($method, $args)
    {
        if (!$this->_connection->get_cookie('MobileMkt')) {
            $this->_connection->set_cookie(array(
                'mkt1'      => 'mobile=en-us',
                'MobileMkt' => 'en-us',
                'MobileCr'  => '1',
            ), null, '.live.com');
        }
        $response = parent::__call($method, $args);
        if ($this->_connection->errno) {
            throw new Actor_LiveMobile_Exception(
                'Connection failed',
                Actor_Exception::NETWORK_ERROR
            );
        } else if (
            (false !== strpos($response, 'Sorry, Spaces is down')) ||
            (false !== strpos($response, "service isn't available")) ||
            (false !== strpos($response, 'problem with Windows Live ID'))
        ) {
            $this->_dump('service-down.html', $response);
            throw new Actor_LiveMobile_Exception(
                'Service is down',
                Actor_Exception::SERVICE_ERROR
            );
        }
        return $response;
    }
}
