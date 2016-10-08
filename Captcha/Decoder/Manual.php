<?php
/**
 * @package Captcha
 */

/**
 * Manual CAPTCHA decoder
 *
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_Manual extends Captcha_Decoder_Abstract
{
    /**
     * @see Captcha_Decoder_Interface::decode()
     */
    public function decode($fn, $timeout=0, $match='')
    {
        parent::decode($fn, $timeout, $match);
        echo "Decode CAPTCHA {$fn}, please: ";
        $text = trim(fgets(STDIN));
        return ($text && (!$match || preg_match("/{$match}/", $text)))
            ? array(time(), $text)
            : false;
    }

    /**
     * @see Captcha_Decoder_Interface::remove()
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
        return true;
    }
}
