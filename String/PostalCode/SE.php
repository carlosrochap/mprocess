<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_SE extends String_PostalCode_Abstract
{
    static protected $_cities = array(
        10, 11,
        20, 21, 22, 25,
        30, 35, 39,
        40, 41,
        50, 55, 58,
        60, 63, 65,
        70, 72, 75,
        80, 85,
        90, 97,
    );


    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return
            (string)self::$_cities[array_rand(self::$_cities)] .
            (string)rand(0, 8) . ' ' .
            str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
    }
}
