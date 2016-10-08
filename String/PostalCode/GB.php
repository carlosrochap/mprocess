<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
class String_PostalCode_GB extends String_PostalCode_Abstract
{
    const ALLOWED_CHARS = 'ABDEFGHJLNPQRSTUWXYZ';


    /**
     * Valid area codes' hash with first letter as key and valid second
     * letters as value
     *
     * @var array
     */
    static protected $_areas = array(
        'A' => 'BL',
        'B' => ' ABDHLNRST',
        'C' => 'ABFHMORTVW',
        'D' => 'ADEGHLNTY',
        'E' => ' CHNX',
        'F' => 'KY',
        'G' => ' LU',
        'H' => 'ADGPRSUX',
        'I' => 'GPV',
        'K' => 'ATWY',
        'L' => ' ADELNSU',
        'M' => ' EKL',
        'N' => ' EGNPRW',
        'O' => 'LX',
        'P' => 'AEHLOR',
        'R' => 'GHM',
        'S' => ' AEGKLMNOPRSTWY',
        'T' => 'ADFNQRSW',
        'U' => 'B',
        'W' => ' ACDFNRSV',
        'Y' => 'O',
        'Z' => 'E'
    );


    /**
     * @see String_PostalCode_Interface::generate()
     */
    static public function generate()
    {
        return
            self::generate_outward_code() . ' ' .
            self::generate_inward_code();
    }

    /**
     * Generates 'outward' code--first part of zip code
     *
     * @return string
     */
    static public function generate_outward_code()
    {
        $area = array_rand(self::$_areas);
        $subareas = self::$_areas[$area];
        return
            $area .
            trim($subareas[rand(0, strlen($subareas) - 1)]) .
            rand(1, 9) .
            (rand(0, 1)
                ? rand(0, 9)
                : '');
    }

    /**
     * Generates 'inward' code--second part of UK zip code
     *
     * @return string
     */
    static public function generate_inward_code()
    {
        $l = strlen(self::ALLOWED_CHARS);
        return
            rand(0, 9) .
            substr(self::ALLOWED_CHARS, rand(0, $l - 1), 1) .
            substr(self::ALLOWED_CHARS, rand(0, $l - 1), 1);
    }
}
