<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Twitter
 */
class Actor_Twitter_Confirmer
    extends Actor_Twitter
    implements Actor_Interface_Confirmer
{
    const CONFIRM_URL = '/account/confirm_email';

    const RESEND_CONFIRMATION_URL = '/account/resend_confirmation_email';


    /**
     * @see Actor_Interface_Confirmer::confirm()
     */
    public function confirm($email, $pass)
    {
        $this->log("Confirming {$email}:{$pass}");

        try {
            $mailer = Email_Factory::factory($email, $this->_log);
            if (!$mailer->login($email, $pass)) {
                $this->log('Failed logging in to e-mail service',
                           Log_Abstract::LEVEL_ERROR);
                return false;
            }
        } catch (Email_Exception $e) {
            throw new Actor_Twitter_Exception(
                $e->getMessage(),
                Actor_Exception::INVALID_CREDENTIALS
            );
        }

        $url = $mailer->get_message(
            'twitter',
            'confirm',
            '#' . preg_quote(self::CONFIRM_URL) . '(/[^/]+/[A-Z\d-]+)#'
        );
        if (!$url) {
            $this->log('Confirmation message not found',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->get(self::HOST . self::CONFIRM_URL . $url);
        $this->_dump('submit.html');

        if (false !== strpos($this->_response, 'has been confirmed')) {
            return true;
        }

        $this->log('Failed confirming e-mail',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }

    public function resend_confirmation()
    {
        $this->log('Resending confirmation link');

        $token = $this->_extract_auth_token();
        if (!$token) {
            $token = $this->_extract_auth_token($this->get(self::HOST . '/'));
        }
        if (!$token) {
            return false;
        }

        $this->ajax(self::HOST . self::RESEND_CONFIRMATION_URL, array(
            'authenticity_token' => $token,
        ));
        $this->_dump('resend.submit.js');

        if (false !== strpos($this->_response, 'email has been sent')) {
            return true;
        }

        $this->log('Failed resending confirmation link',
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
