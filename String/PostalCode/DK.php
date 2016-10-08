<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_DK extends String_PostalCode_Abstract
{
    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return (string)rand(1000, 9999);
    }
}
