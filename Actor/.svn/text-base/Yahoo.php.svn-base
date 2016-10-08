<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Yahoo
 */
class Actor_Yahoo extends Actor_Http_Abstract
{
    const HOST          = 'http://mail.yahoo.com';
    const LOG_IN_HOST   = 'https://login.yahoo.com';
    const PROFILE_HOST  = 'http://profiles.yahoo.com';
    const PULSE_HOST    = 'http://pulse.yahoo.com';
    const EDIT_HOST     = 'https://na.edit.yahoo.com';
    const CAPTCHA_HOST  = 'https://ab.login.yahoo.com';
    const CONTACTS_HOST = 'http://address.mail.yahoo.com';

    const LOG_OUT_URL = '/config/login?.src=my&logout=1&.direct=1&.intl=us&.done=http%3A%2F%2Fmy.yahoo.com';

    const USERPIC_UPLOAD_URL = '/upload/ajax/profile/uploadPhoto';
    const USERPIC_CROP_URL   = '/upload/ajax/profile/cropPhoto';


    protected $_mail_host = null;


    protected function _upload_userpic($fn)
    {
        $this->get(self::PULSE_HOST);
        $this->_dump('profile.html');
        if (!preg_match('#,data:({.+?})}#', $this->_response, $options)) {
            $this->log('Ajax options not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else {
            $options = json_decode($options[1], true);
        }

        $this->get(self::PULSE_HOST . "/{$options['ownerScreenname']}");
        $this->_dump('userpic.form.html');
        $this->post(
            self::PULSE_HOST . "/@us1" . self::USERPIC_UPLOAD_URL,
            array(
                'type'     => '',
                '_crumb'   => $options['crumb'],
                'upfile'   => basename($fn),
                'uploader' => "@{$fn}",
            )
        );
        $this->_dump('userpic.upload.html');
        $this->post(
            self::PULSE_HOST . "/@us1" . self::USERPIC_CROP_URL,
            array(
                'type'     => '',
                '_crumb'   => $options['crumb'],
                'width'    => 600,
                'height'   => 600,
                'left'     => 0,
                'top'      => 0,
                'imgUrl'   => $url,
            )
        );
        $this->_dump('userpic.crop.html');
        return true;
    }

    protected function _login_redirect($suffix='submit')
    {
        $i = 0;
        while (preg_match(
            '@location\.replace\(["\']([^\"]+)["\']\s*\)@i',
            $this->_response,
            $m
        ) || preg_match(
            "@CONTENT=\"0;\s*URL='([^']+)@i",
            $this->_response,
            $m
        )) {
            $i++;

            $url = html_entity_decode($m[1], ENT_QUOTES);
            if ($url == $this->_connection->last_url) {
                break;
            }

            $this->log("Redirect to: {$url}",
                       Log_Abstract::LEVEL_DEBUG);
            do {
                $this->get($url);
                $this->_dump("login.{$suffix}.{$i}.html");
            } while (404 == $this->_connection->status_code);
        }

        return $this->_response;
    }

    protected function _login($user_id, $pass)
    {
        $this->get(self::HOST);
        $this->_dump('login.html');
        if (!$this->get_form('name', 'login_form')) {
            throw new Actor_Yahoo_Exception(
                'Login form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['login'] = $user_id;
        $this->_form['passwd'] = $pass;
        $this->submit();
        $this->_dump('login.submit.html');
        $this->_login_redirect('login.submit');
        if (
            (false !== stripos($this->_response, 'Invalid ID or Password')) ||
            (false !== stripos($this->_response, 'not yet taken'))
        ) {
            throw new Actor_Yahoo_Exception(
                'Invalid username or password',
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        if (preg_match(
            '#id="dontprotectaccount".+?value="([^"]+)#',
            $this->_response,
            $m
        )) {
            $this->log('Reactivating account',
                       Log_Abstract::LEVEL_ERROR);

            $u = new Url($this->_connection->last_url);
            $this->get(
                "{$u->scheme}://{$u->host}" .
                html_entity_decode($m[1], ENT_QUOTES)
            );
            $this->_dump('login.reactivate.html');
            $this->_login_redirect('reactivate');
        }

        if ($this->get_form('id', 'supRegForm')) {
            $this->log('Secret questions page',
                       Log_Abstract::LEVEL_DEBUG);
            $this->_form['spwq1'] = 'Where did you spend your honeymoon?';
            $this->_form['pwq1'] = '';
            $this->_form['pwa1'] = 'Right there';
            $this->_form['spwq2'] = 'Who is your favorite author?';
            $this->_form['pwq2'] = '';
            $this->_form['pwa2'] = 'Thomas Pynchon';
            $this->_form['save'] = 'Save and Continue';
            $this->submit();
            $this->_dump('login.secret-questions.html');
            $this->_login_redirect('secret-questions');
        }

        if (preg_match(
            '#href="([^"]+)">No thanks, go to my inbox#',
            $this->_response,
            $m
        )) {
            $this->log('Ignoring browser upgrade',
                       Log_Abstract::LEVEL_DEBUG);

            $u = new Url($this->_connection->last_url);
            $m = explode('?', html_entity_decode($m[1], ENT_QUOTES), 2);
            $u->query = @$m[1];
            $a = array_filter(explode('/', $u->path));
            $u->path = '/' . (count($a)
                ? implode('/', array_slice($a, 0, count($a) - 1)) . '/'
                : '') . $m[0];
            $this->get($u);
            $this->_dump('login.browser-upgrade.html');
            $this->_login_redirect('login.browser-upgrade');
        }

        if (false !== strpos($this->_response, 'Why am I being asked')) {
            $this->log('Password confirmation required',
                       Log_Abstract::LEVEL_DEBUG);
            if (!$this->get_form(0)) {
                throw new Actor_Yahoo_Exception(
                    'Password confirmation form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $this->_form['passwd'] = $pass;
            $this->submit();
            $this->_dump('login.password-confirm.html');
        }

        if (false !== strpos($this->_response, 'Improve performance')) {
            $this->log('Improve performance page',
                       Log_Abstract::LEVEL_DEBUG);

            $this->_form = null;
            if ($forms = Html_Form::get($this->_response, $this->_connection)) {
                for ($i = 0, $l = count($forms); $i < $l && !$this->_form; $i++) {
                    if (isset($forms[$i]['.done'])) {
                        $this->_form = $forms[$i];
                    }
                }
            }
            if (!$this->_form) {
                throw new Actor_Yahoo_Exception(
                    'Improve performance form not found',
                    Actor_Exception::PROXY_BANNED
                );
            }

            $this->_form['.norepl'] = ' No ';
            $this->submit();
            $this->_dump('login.performance-improve.html');
        }

        while (true) {
            if (false !== strpos($this->_response, ' to Yahoo! Mail Classic')) {
                $this->log('Switching to Y! Mail Classic');
                $u = new Url($this->_connection->last_url);
                $u->path = '/dc/optout';
                $u->query = array('script' => 'no');
                $this->get($u);
                $this->_dump('login.classic-switch.html');
                if ($this->get_form('action', 'optout')) {
                    $this->submit();
                    $this->_dump('login.classic-switch.submit.html');
                }
            }
            if ($this->get_form('action', '/intl_migrate')) {
                $this->log('Swithing back to US',
                           Log_Abstract::LEVEL_DEBUG);
                $this->submit();
                $this->_dump('login.international-migrate.html');
                $this->_login_redirect('international-migrate');
            } else {
                break;
            }
        }

        if (false !== strpos($this->_response, '>Sign Out<')) {
            $this->user_id = $user_id;
            return true;
        }
    }


    /**
     * @see Actor_Http_Abstract::login()
     */
    public function login($user_id, $pass)
    {
        $this->log("Logging in as {$user_id}:{$pass}");

        $this->_mail_host = null;
        $old_follow_refresh = $this->_connection->follow_refresh;
        $this->_connection->follow_refresh = false;
        try {
            $result = $this->_login($user_id, $pass);
        } catch (Exception $e) {
            $result = $e;
        }
        $this->_connection->follow_refresh = $old_follow_refresh;
        if ($result instanceof Exception) {
            throw $result;
        } else {
            if ($result) {
                $url = new Url($this->_connection->last_url);
                $this->_mail_host =
                    "{$url->scheme}://us" .
                    substr($url->host, strpos($url->host, '.'));
                if ($this->is_default_userpic()) {
                    $details = $this->call_process_method(
                        'get_profile_details',
                        $user_id
                    );
                    if ($details && !empty($details['userpic'])) {
                        $this->upload_userpic($details['userpic']);
                    }
                }
            } else {
                $this->log('Login failed',
                           Log_Abstract::LEVEL_ERROR);
            }
            return $result;
        }
    }

    /**
     * @see Actor_Http_Abstract::logout()
     */
    public function logout()
    {
        if ($this->_user_id) {
            $this->get(self::LOG_IN_HOST . self::LOG_OUT_URL);
        }
        return parent::logout();
    }

    public function is_default_userpic($response=null)
    {
        return preg_match('# src="[^"]+/nopic_\d+#', ((null === $response)
            ? $this->_response
            : $response));
    }

    public function upload_userpic($userpic)
    {
        return false;
    }

    public function __call($method, $args)
    {
        $response = parent::__call($method, $args);
        if (999 == $this->_connection->status_code) {
            $this->_dump('service-down.html', $response);
            throw new Actor_Yahoo_Exception(
                'Service temporarily offline',
                Actor_Exception::SERVICE_ERROR
            );
        }
        return $response;
    }
}
