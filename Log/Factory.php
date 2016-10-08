<?php
/**
 * @package Log
 */

/**
 * Loggers' factory
 *
 * @package Log
 */
abstract class Log_Factory
{
    /**
     * Default logger to use
     */
    const DEFAULT_LOGGER = 'Console';


    /**
     * Loggers' pool
     *
     * @var array
     */
    static protected $_loggers = array();


    /**
     * Returns a (cached) logger instance
     *
     * @param string $name
     * @return Log_Abstract subclass instance
     */
    static public function factory($name=self::DEFAULT_LOGGER)
    {
        $name = ucfirst(
            $name
                ? $name
                : self::DEFAULT_LOGGER
        );

        if (!$logger = &self::$_loggers[$name]) {
            $class = str_replace('_Factory', "_{$name}", __CLASS__);
            $logger = new $class();
            unset($class);
        }

        unset($name);
        return $logger;
    }
}
