<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_CA extends String_PostalCode_Abstract
{
    static protected $_allowed_letters = 'ABCEGHJKLMNPRSTVXY';


    /**
     * @see String_PostalCode_Abstract::generate()
     */
    static public function generate()
    {
        return self::generate_fsa() . ' ' . self::generate_ldu();
    }

    /**
     * Generates FSA code
     *
     * @return string
     */
    static public function generate_fsa()
    {
        return
            self::$_allowed_letters[array_rand(self::$_allowed_letters)] .
            rand(0, 9) .
            self::$_allowed_letters[array_rand(self::$_allowed_letters)];
    }

    /**
     * Generates LDU code
     *
     * @return string
     */
    static public function generate_ldu()
    {
        return
            rand(0, 9) .
            self::$_allowed_letters[array_rand(self::$_allowed_letters)] .
            rand(0, 9);
    }
}
