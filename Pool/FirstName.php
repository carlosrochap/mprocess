<?php
/**
 * @package Pool
 */

/**
 * Common first names pool
 *
 * @package Pool
 * @subpackage Db
 */
class Pool_FirstName extends Pool_Name
{
    protected $_stmts = array(
        'get_names' => array('db' => 'globo13', 'query' => '
            SELECT `name`
            FROM `gamma`.`first_names`
            WHERE `nationality` = ? AND
                  (`gender` = ? OR `gender` IS NULL)
            ORDER BY RAND()
            LIMIT ?
        '),
    );
}
