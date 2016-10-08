<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_PT extends String_PostalCode_Abstract
{
    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return
            (string)rand(101, 999) .
            (rand(0, 1)
                ? '5'
                : '0') . '-' .
            str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
