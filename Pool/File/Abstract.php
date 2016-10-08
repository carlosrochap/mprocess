<?php
/**
 * @package Pool
 */

/**
 * Base class for pools with text files as sources
 *
 * @package Pool
 * @subpackage File
 */
abstract class Pool_File_Abstract extends Pool_Abstract
{
    /**
     * Pool sources' files handlers hash
     *
     * @var array
     */
    protected $_pool_sources = array();

    /**
     * Tries to fill a pool by loading a corresponding text file using each
     * line as a pool item, if no custom filling methods defined.
     *
     * @see Pool_Abstract::_prepare()
     */
    protected function _prepare($name, $size)
    {
        if (parent::_prepare($name, $size)) {
            return true;
        }

        $pool = &$this->_pools[$name];
        if (empty($pool)) {
            $pool = array();
            $fn = realpath('.' . DIRECTORY_SEPARATOR . basename($name) . '.txt');
            $fd = &$this->_pool_sources[$fn];
            if (!is_resource($fd) && is_file($fn) && is_readable($fn)) {
                $fd = fopen($fn, 'r');
            }
            if ($fd) {
                $a = fstat($fd);
                if ($a['size']) {
                    $cnt = $size;
                    while ($cnt) {
                        while (!feof($fd) && $cnt) {
                            $s = trim(fgets($fd));
                            if ($s) {
                                $pool[] = $s;
                                $cnt--;
                            }
                        }
                        if (feof($fd)) {
                            fseek($fd, 0);
                        }
                    }
                }
            }
            shuffle($pool);
        }
        return (is_array($pool) && count($pool));
    }


    /**
     * @see Pool_Abstract::close()
     */
    public function close()
    {
        foreach (array_filter($this->_pool_sources, 'is_resource') as $fd) {
            fclose($fd);
        }

        $this->_pool_sources = array();

        return parent::close();
    }
}
