<?php
/**
 * @package Captcha
 */

/**
 * GOCR decoder
 *
 * @package Captcha
 * @subpackage Decoder
 */
class Captcha_Decoder_Gocr extends Captcha_Decoder_Abstract
{
    const GOCR_BIN = '/usr/bin/gocr';


    static public function read($fn, $chars='0-9A-Za-z')
    {
        $cmd = array(self::GOCR_BIN, '-a 50');
        if ($chars) {
            $cmd[] = '-C ' . escapeshellarg($chars);
        }
        $cmd[] = '-i ' . escapeshellarg($fn);
        $cmd[] = '2>/dev/null';
        return trim(exec(implode(' ', $cmd)));
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
