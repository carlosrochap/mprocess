<?php
/**
 * @package Process
 */

/**
 * Master bots factory/cache
 *
 * @package Process
 * @subpackage Master
 */
abstract class Process_Master_Factory
{
    /**
     * Master bots cache
     *
     * @var array
     */
    static protected $_masters = array();


    /**
     * Creates a new one or returns an existing master process
     *
     * @param array           $config Project-specific configuration
     * @param Queue_Interface $queue  Message queue to use
     * @return Process_Master_Abstract
     * @throws Process_Exception When project name not found, when master's
     *                           or its pool's class not found
     */
    static public function factory(array $config, Queue_Interface $queue, Log_Interface $log=null)
    {
        $name = ucfirst(@$config['project']['name']);
        if (!$name) {
            throw new Process_Exception(
                'Project name not found, check configuration',
                Process_Exception::INVALID_ARGUMENT
            );
        }
        if (!$master = &self::$_masters[$name]) {
            $class = str_replace('_Factory', "_{$name}", __CLASS__);
            $master = new $class($config, $queue, $log);
        }
        return $master;
    }
}
