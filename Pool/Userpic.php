<?php
/**
 * @package Pool
 */

/**
 * Userpics pool
 *
 * @package Pool
 * @subpackage Db
 */
class Pool_Userpic extends Pool_Db_Abstract
{
    const GENDER_FEMALE = 'F';
    const GENDER_MALE   = 'M';


    protected $_stmts = array(
        // Fill a pool of random userpics
        'get_userpics_by_gender' => array('db' => 'globo07', 'query' => '
            SELECT `picture_location` AS `userpic`
            FROM `username`
            WHERE `sex` = ?
            ORDER BY RAND()
            LIMIT ?
        '),
    );


    protected $_genders = array('f', 'm');


    /**
     * Fills a pool of female userpics
     *
     * @param int $size
     * @return array|false
     */
    protected function _fill_userpic_f($size)
    {
        return $this->get_userpics_by_gender('female', $size);
    }

    /**
     * Fills a pool of male userpics
     *
     * @param int $size
     * @return array|false
     */
    protected function _fill_userpic_m($size)
    {
        return $this->get_userpics_by_gender('male', $size);
    }


    /**
     * Fetches either specific or random gender' userpics
     *
     * @see Pool_Abstract::get()
     */
    public function get($key='userpic')
    {
        $key = strtolower(@$key[0]);
        return parent::get('userpic_' . (($key && in_array($key, $this->_genders))
            ? $key
            : $this->_genders[array_rand($this->_genders)]));
    }
}
