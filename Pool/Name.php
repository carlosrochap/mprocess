<?php
/**
 * @package Pool
 */

/**
 * Common names pool
 *
 * @package Pool
 * @subpackage Db
 */
abstract class Pool_Name extends Pool_Db_Abstract
{
    const GENDER_FEMALE = 'F';
    const GENDER_MALE   = 'M';

    const DEFAULT_GENDER      = 'F';
    const DEFAULT_NATIONALITY = 'EN';


    /**
     * @see Pool_Abstract::get()
     */
    public function get($gender=self::DEFAULT_GENDER, $nationality=self::DEFAULT_NATIONALITY)
    {
        $gender = strtoupper($gender);
        if ((self::GENDER_FEMALE != $gender) && (self::GENDER_MALE != $gender)) {
            $gender = self::DEFAULT_GENDER;
        }
        $nationality = strtoupper($nationality);
        if (2 != strlen($nationality)) {
            $nationality = self::DEFAULT_NATIONALITY;
        }

        $pool = &$this->_pools["{$nationality}.{$gender}"];
        if (empty($pool)) {
            $pool = $this->get_names($nationality, $gender, $this->_size);
        }
        return !empty($pool)
            ? array_pop($pool)
            : null;
    }
}
