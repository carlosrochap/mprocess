<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_NL extends String_PostalCode_Abstract
{
    const ALLOWED_CHARS = 'ABCDEGHJKLMNPRSTVWXZ';


    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return
            self::generate_digits() . ' ' .
            self::generate_letters();
    }

    /**
     * Generates first--numeric--part of NL postal codes
     *
     * @return string
     */
    static public function generate_digits()
    {
        return (string)rand(1000, 9999);
    }

    /**
     * Generates second--literal--part of NL postal codes
     *
     * @return string
     */
    static public function generate_letters()
    {
        $l = strlen(self::ALLOWED_CHARS);
        return
            substr(self::ALLOWED_CHARS, rand(0, $l - 1), 1) . 
            substr(self::ALLOWED_CHARS, rand(0, $l - 1), 1);
    }
}
