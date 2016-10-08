<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Google
 */
class Actor_Google_Confirmer
    extends Actor_Google
    implements Actor_Interface_Confirmer
{
    const CONFIRM_URL = '/accounts/VE';


    /**
     * @see Actor_Interface_Confirmer::confirm()
     */
    public function confirm($email, $pass)
    {
        $this->log("Confirming account {$email}:{$pass}");

        $mailer = $this->_get_email_client($email, $pass);
        if (!$mailer) {
            return false;
        }

        $query = $mailer->get_message(
            'accounts-noreply@google.com',
            'Google Email Verification',
            '#accounts/VE(\?[^"]+)#'
        );
        if ($query) {
            $this->get(self::HOST . self::CONFIRM_URL . $query);
            $this->_dump('confirm.html');
            if (false !== strpos($this->_response, 'Thank you')) {
                return true;
            }
        }

        $this->log("Failed confirming {$email}",
                   Log_Abstract::LEVEL_ERROR);
        return false;
    }
}
