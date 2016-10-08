<?php
/**
 * @package Captcha
 */

/**
 * Death by Captcha API client
 */
require_once 'dbc_client.php';


/**
 * Death by Captcha decoder
 *
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_DeathByCaptcha extends Captcha_Decoder_Abstract
{
    /**
     * API client
     *
     * @var Captcha_Client_DeathByCaptcha
     */
    protected $_client = null;


    /**
     * Returns cached Death by Captcha client instance
     *
     * @return DeathByCaptcha_Client
     */
    protected function _get_client()
    {
        if (!$this->_client) {
            $this->_client = new DeathByCaptcha_Client();
            $this->_client->is_verbose = $this->is_verbose;
        }

        if (!$this->_client->is_logged_in) {
            try {
                $this->_client->login(
                    $this->_credentials->user,
                    $this->_credentials->pass
                );
            } catch (Exception $e) {
                $this->log($e, Log_Abstract::LEVEL_ERROR);
            }
        }

        return $this->_client;
    }

    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function decode($fn, $timeout=0, $match='')
    {
        if (!$timeout) {
            $timeout = $this->_timeout;
        }
        parent::decode($fn, $timeout, $match);
        try {
            return $this->_get_client()->decode($fn, $timeout);
        } catch (Exception $e) {
            $this->log($e, Log_Abstract::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function remove($id)
    {
        try {
            return $this->_get_client()->remove($id);
        } catch (Exception $e) {
            $this->log($e, Log_Abstract::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * @see Captcha_Decoder_Interface::report()
     */
    public function report($id, $is_correct=true)
    {
        try {
            return $this->_get_client()->report($id, $is_correct);
        } catch (Exception $e) {
            $this->log($e, Log_Abstract::LEVEL_ERROR);
            return false;
        }
    }
}
