<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_AU extends String_PostalCode_Abstract
{
    static protected $_ranges = array(
        array( 800,  899),
        array(2000, 2599),
        array(2600, 2618),
        array(2619, 2898),
        array(2900, 2920),
        array(2921, 2999),
        array(3000, 3999),
        array(4000, 4999),
        array(5000, 5799),
        array(6000, 6797),
        array(7000, 7799),
    );


    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        $range = self::$_ranges[array_rand(self::$_ranges)];
        return str_pad(rand($range[0], $range[1]), 4, '0', STR_PAD_LEFT);
    }
}
