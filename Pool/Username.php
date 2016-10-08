<?php
/**
 * @package Pool
 */

/**
 * IT2 usernames pool
 *
 * @package Pool
 * @subpackage Db
 */
class Pool_Username extends Pool_Db_Abstract
{
    const GENDER_FEMALE = 'F';
    const GENDER_MALE   = 'M';


    protected $_stmts = array(
        'get_username' => array('db' => 'globo07', 'query' => '
            SELECT `username`
            FROM `username`
            WHERE `sex` = ?
            ORDER BY RAND()
            LIMIT ?
        '),
    );


    public function get_username($gender=self::GENDER_FEMALE)
    {
        $pool = &$this->_pools["username_{$gender}"];
        if (!$pool) {
            $pool = $this->_execute(
                'get_username',
                ((self::GENDER_MALE == $gender)
                    ? 'male'
                    : 'female'),
                $this->_size
            );
        }
        return !empty($pool)
            ? array_pop($pool)
            : false;
    }
}
