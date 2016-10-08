<?php
/**
 * @package String
 */

/**
 * US zip codes
 *
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_US extends String_PostalCode_Abstract
{
    static protected $_invalid_scf = array(
        192,
        201, 202, 204, 205, 213, 269,
        311, 332, 340, 341, 343, 344, 345, 348, 353, 375, 398, 399,
        419, 428, 429, 459,
        509, 517, 518, 519, 529, 533, 536, 552, 568, 569, 578, 579, 589,
        607, 608, 621, 632, 642, 643, 649, 659, 663, 682, 694, 695, 696, 697, 698, 699,
        702, 709, 715, 732, 733, 742, 753, 771, 772,
        817, 819, 839, 842, 848, 849, 851, 854, 858
    );


    /**
     * @see String_PostalCode_Abstract::generate()
     */
    static public function generate()
    {
        return self::generate_scf() . self::generate_locality();
    }

    /**
     * Generates SCF code
     *
     * @return string
     */
    static public function generate_scf()
    {
        do {
            $i = rand(100, 860);
        } while (in_array($i, self::$_invalid_scf));
        return (string)$i;
    }

    /**
     * Generates locality code
     *
     * @return string
     */
    static public function generate_locality()
    {
        return str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
    }
}
