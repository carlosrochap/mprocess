<?php
/**
 * @package Base
 */

/**
 * Primitive abstract class defining logging abilities
 *
 * @property Log_Interface $log Logger to use
 *
 * @package Base
 */
abstract class Loggable extends Base_Abstract
{
    /**
     * Verbosity flag
     *
     * @var bool
     */
    public $is_verbose = false;

    /**
     * Logger instance
     *
     * @var Log_Interface
     */
    protected $_log = null;


    /**
     * Sets a logger to use
     *
     * @param Log_Interface $log
     */
    public function set_log(Log_Interface $log)
    {
        $this->_log = $log;
        $this->is_verbose = &$log->is_verbose;
        return $this;
    }

    /**
     * Returns the logger currently in use
     *
     * @return Log_Interface
     */
    public function get_log()
    {
        return $this->_log;
    }

    /**
     * Logs a message
     *
     * @param mixed $msg
     * @param int   $lvl
     */
    public function log($msg, $lvl=Log_Abstract::LEVEL_INFO)
    {
        if ($this->_log) {
            $this->_log->write($msg, $lvl);
        }
        return $this;
    }
}
