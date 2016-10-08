<?php
/**
 * @package Pool
 */

/**
 * Common domains pool, uses 'domain.txt' from current directory
 *
 * @package Pool
 * @subpackage File
 */
class Pool_Domain extends Pool_File_Abstract
{
    /**
     * @see Pool_File_Abstract::get()
     */
    public function get($key='domain')
    {
        return parent::get($key);
    }
}
