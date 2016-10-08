<?php
/**
 * @package Captcha
 */

/**
 * Death by Captcha API client
 */
require_once 'dbc_client.3.php';


/**
 * Death by Captcha decoder
 *
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_DeathByCaptcha3 extends Captcha_Decoder_Abstract
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
            $this->_client = new DeathByCaptcha_Client(
                $this->_credentials->user,
                $this->_credentials->pass
            );
            $this->_client->is_verbose = $this->is_verbose;
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
        if ($match) {
            $match = "/[{$match}]+/";
        }
        parent::decode($fn);
        try {
            return $this->_get_client()->decode($fn, $timeout, $match);
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
        return true;
    }

    /**
     * @see Captcha_Decoder_Interface::report()
     */
    public function report($id, $is_correct=true)
    {
        try {
            return $is_correct || $this->_get_client()->report($id);
        } catch (Exception $e) {
            $this->log($e, Log_Abstract::LEVEL_ERROR);
            return false;
        }
    }
}
