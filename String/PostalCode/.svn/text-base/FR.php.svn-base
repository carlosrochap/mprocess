<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_FR extends String_PostalCode_Abstract
{
    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return
            self::generate_dept_code() .
            self::generate_pref_code();
    }

    /**
     * Generates department code
     *
     * @return string
     */
    static public function generate_dept_code()
    {
        do {
            $n = rand(1, 95);
        } while (20 == $n);
        return str_pad($n, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generates prefecture code
     *
     * @return string
     */
    static public function generate_pref_code()
    {
        return str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    }
}
