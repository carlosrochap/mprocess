<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_DE extends String_PostalCode_Abstract
{
    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return
            self::generate_area_code() .
            self::generate_district_code();
    }

    /**
     * Generates area code--first part of DE zip codes
     *
     * @return string
     */
    static public function generate_area_code()
    {
        return str_pad(rand(0, 97), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generates district code--second part of DE zip codes
     *
     * @return string
     */
    static public function generate_district_code()
    {
        return str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    }
}
