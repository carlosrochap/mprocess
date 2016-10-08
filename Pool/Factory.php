<?php
/**
 * @package Pool
 */

/**
 * Pools factory & cache
 *
 * @package Pool
 */
abstract class Pool_Factory
{
    /**
     * Pools cache
     *
     * @var array
     */
    static protected $_pools = array();


    /**
     * Fetches a (cached) named pool instance
     *
     * @param string        $name
     * @param Log_Interface $log
     * @param array         $config Optional project configuration
     * @return Pool_Abstract
     */
    static public function factory($name, Log_Interface $log=null, array $config=array())
    {
        $name = ucfirst(strtr($name, '\/', '__'));
        if (!$pool = &self::$_pools[$name]) {
            $class = str_replace('_Factory', "_{$name}", __CLASS__);
            $pool = new $class($config);
        }
        if ($log) {
            $pool->log = $log;
        }
        return $pool;
    }
}
