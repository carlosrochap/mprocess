<?php
/**
 * @package Log
 */

/**
 * Generic strout/stderr logger
 *
 * @package Log
 */
class Log_Console extends Log_File
{
    /**
     * @see Log_File::__construct()
     */
    public function __construct()
    {}

    /**
     * @see Log_File::write()
     */
    public function write($msg, $lvl=self::LEVEL_INFO)
    {
        $this->_file = (self::LEVEL_ERROR == $lvl)
            ? STDERR
            : STDOUT;
        return parent::write($msg, $lvl);
    }
}
