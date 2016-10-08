<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage YouTube
 */
class Actor_YouTube extends Actor_Http_Abstract
{
    const HOST        = 'http://www.youtube.com';
    const SECURE_HOST = 'https://www.google.com';

    const LOG_IN_URL = '/accounts/ServiceLogin?uilel=3&service=youtube&passive=true&continue=http%3A%2F%2Fwww.youtube.com%2Fsignin%3Faction_handle_signin%3Dtrue%26nomobiletemp%3D1%26hl%3Den_US%26next%3D%252Findex&hl=en_US&ltmpl=sso';

    const EMAIL_CONFIRM_URL = '/email_confirm';

    const ACCOUNT_URL          = '/account';
    const ACCOUNT_OVERVIEW_URL = '/account_overview';
    const ACCOUNT_USERPIC_URL  = '/account_dialog_profile_picture';


    protected function _upload_userpic($fn)
    {
        $this->get(
            self::HOST . self::ACCOUNT_USERPIC_URL,
            null,
            self::HOST . self::ACCOUNT_URL
        );
        $this->_dump('userpic.form.html');
        if (!$this->get_form('id', 'picture-upload-form')) {
            $this->log('Upload form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else if (!$save_changes_form = Html_Form::get(
            $this->_response,
            $this->_connection,
            'id',
            'picture-save-form'
        )) {
            $this->log('Save changes form not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->_form['imagefile'] = $fn;
        $this->submit();
        $this->_dump('userpic.form.submit.html');
        if (false !== strpos($this->_response, '"errors": []')) {
            $this->_dump('userpic.save.html', $save_changes_form->submit(
                $this->_connection,
                self::HOST . self::ACCOUNT_URL
            ));
            return true;
        } else {
            return false;
        }
    }


    /**
     * @see Actor_Interface::login()
     */
    public function login($user_id, $pass)
    {
        $this->log("Logging in as {$user_id} {$pass}");

        $this->get(self::HOST);
        $this->_dump('homepage.html');
        $this->get(self::SECURE_HOST . self::LOG_IN_URL);
        $this->_dump('login.html');
        if (!$this->get_form('id', 'gaia_loginform')) {
            throw new Actor_YouTube_Exception(
                'Login form not found',
                Actor_Exception::PROXY_BANNED
            );
        }

        $this->_form['Email'] = $user_id;
        $this->_form['Passwd'] = $pass;
        $this->submit();
        $this->_dump('login.submit.html');
        if (false !== strpos($this->_response, 'logoutForm')) {
            $this->user_id = $user_id;
            if ($this->is_default_userpic()) {
                $details =
                    $this->call_process_method('get_profile_details', $user_id);
                if ($details && !empty($details['userpic'])) {
                    $this->upload_userpic($details['userpic']);
                }
            }
            return true;
        } else if (false !== strpos($this->_response, 'errormsg_0_Passwd')) {
            throw new Actor_YouTube_Exception(
                'Invalid user ID or password',
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        $this->log('Login failed',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    /**
     * @see Actor_Interface::login()
     */
    public function logout()
    {
        if ($this->_user_id) {
            $this->post(self::HOST, 'action_logout=1');
        }
        return parent::logout();
    }

    /**
     * Checks if the user has default userpic
     *
     * @return bool
     */
    public function is_default_userpic()
    {
        $this->log('Checking for default userpic');
        return (false !== strpos($this->_connection->get(
            self::HOST . self::ACCOUNT_OVERVIEW_URL,
            null,
            self::HOST . self::ACCOUNT_URL
        ), 'no_videos_'));
    }
}
