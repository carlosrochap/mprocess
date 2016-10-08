<?php
/**
 * @package Captcha
 */

/**
 * Decaptcher client
 */
require_once 'vendors/decaptcher.php';


/**
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_Decaptcher extends Captcha_Decoder_Abstract
{
    protected $_client = null;


    /**
     * @see Captcha_Decoder_Abstract::init()
     */
    public function init()
    {
        if (!$this->_client) {
            $this->_client = new ccproto();
        }
        $this->_client->init();

        return parent::init();
    }

    /**
     * @see Captcha_Decoder_Interface::remove()
     */
    public function remove($id)
    {
        return true;
    }

    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function decode($fn, $timeout=0, $match='')
    {
        parent::decode($fn, $timeout, $match);

        if (
            (sCCC_PICTURE != $this->_client->status) &&
            (ccERR_OK != $this->_client->login(
                '66.197.173.53',
                8123,
                'lewisamalor',
                'decaptch'
            ))
        ) {
            return false;
        }

        if (
            (ccERR_OK != $this->_client->system_load($system_load)) ||
            (ccERR_OK != $this->_client->balance($balance)) ||
            (0 >= $balance)
        ) {
            return false;
        }

        $pict = file_get_contents($fn);
        $pict_to = ptoDEFAULT;
        $pict_type = ptUNSPECIFIED;
        $major_id = $minor_id = 0;
        $text = '';
        return
            (ccERR_OK == $this->_client->picture2($pict, $pict_to, $pict_type, $text, $major_id, $minor_id)) && $text
                ? array(array($major_id, $minor_id), $text)
                : false;
    }

    /**
     * @see Captcha_Decoder_Interface::report()
     */
    public function report($id, $is_correct=true)
    {
        return
            is_array($id) &&
            !$is_correct &&
            (sCCC_PICTURE != $this->_client->status) &&
            (ccERR_OK == $this->_client->picture_bad2($id[0], $id[1]));
    }
}
