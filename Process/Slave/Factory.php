<?php
/**
 * @package Process
 */

/**
 * Slave processes factory
 *
 * @package Process
 * @subpackage Slave
 */
abstract class Process_Slave_Factory
{
    /**
     * Creates a slave process
     *
     * @param array           $config Project-specific configuration
     * @param Queue_Interface $queue  Message queue to use
     * @return Process_Slave_Abstract
     * @throws Process_Exception When slave class not found
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
        $class = str_replace('_Factory', "_{$name}", __CLASS__);
        return new $class($config, $queue, $log);
    }
}
