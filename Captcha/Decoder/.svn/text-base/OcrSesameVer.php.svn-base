<?php
/**
 * @package Captcha
 */

/**
 * Sesame/Ver OCR
 *
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_OcrSesameVer extends Captcha_Decoder_Abstract
{
    const OCR_BIN = '/home/ksa242/bin/ocr-sesame-ver';


    static public function read($fn, $chars='0-9A-Za-z')
    {
        return trim(exec(implode(' ', array(self::OCR_BIN, escapeshellarg($fn)))));
    }


    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function decode($fn, $timeout=0, $match='')
    {
        if (!$match && $this->_match) {
            $match = $this->_match;
        }
        parent::decode($fn);
        $s = self::read($fn, $match);
        return $s
            ? array(time(), $s)
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
