<?php
/**
 * @package Captcha
 */

/**
 * Yahoo! OCR
 *
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_OcrYahoo extends Captcha_Decoder_Abstract
{
    const OCR_URL = 'http://72.10.166.28/index.php';


    protected $_client = null;


    /**
     * @see Captcha_Decoder_Abstract::init()
     */
    public function init()
    {
        if (!$this->_client) {
            $this->_client = new Connection_Curl();
        }
        $this->_client->is_verbose = $this->is_verbose;
        $this->_client->init();
        return parent::init();
    }

    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function decode($fn, $timeout=0, $match='')
    {
        parent::decode($fn);
        $s = trim($this->_client->post(self::OCR_URL, array(
            'captcha' => "@{$fn}",
            'secret'  => 'a9g8xfdf8bp7',
        )));
        return ($s && ('0000' != $s))
            ? array(0, $s)
            : false;
    }

    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function remove($id)
    {
        return false;
    }

    /**
     * @see Captcha_Decoder_Interface::report()
     */
    public function report($id, $is_correct=true)
    {
        return false;
    }
}
