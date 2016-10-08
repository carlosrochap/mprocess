<?php
/**
 * @package Pool
 */

/**
 * Last names pool
 *
 * @package Pool
 * @subpackage Db
 */
class Pool_LastName extends Pool_Name
{
    protected $_stmts = array(
        'get_names' => array('db' => 'globo13', 'query' => '
            SELECT `name`
            FROM `gamma`.`last_names`
            WHERE `nationality` = ? AND
                  (`gender` = ? OR `gender` IS NULL)
            ORDER BY RAND()
            LIMIT ?
        '),
    );
}
