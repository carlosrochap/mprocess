<?php
/**
 * @package Captcha
 */

/**
 * CAPTCHA decoder interface
 *
 * @package Captcha
 * @subpackage Decoder
 */
interface Captcha_Decoder_Interface
{
    /**
     * Decodes a CAPTCHA image
     *
     * @param string $fn CAPTCHA image file name
     * @param int $timeout Optional timeout (in seconds)
     * @param string $match Optional regex to check the solved text
     * @return array|false (CAPTCHA ID, text) tuple on success
     */
    public function decode($fn, $timeout=0, $match='');

    /**
     * Removes undecoded CAPTCHA
     *
     * @param mixed $id
     * @return bool
     */
    public function remove($id);

    /**
     * Reports (in-)correct decoding results
     *
     * @param mixed $id
     * @param bool  $is_correct
     * @return bool
     */
    public function report($id, $is_correct=true);
}
