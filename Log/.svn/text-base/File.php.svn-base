<?php
/**
 * @package Log
 */

/**
 * Generic file logger, uses stdout by default
 *
 * @package Log
 */
class Log_File extends Log_Abstract
{
    /**
     * File to log to
     *
     * @var resource
     */
    protected $_file = STDOUT;


    /**
     * Constructs a logger
     *
     * @param string|resource $file File to log to
     */
    public function __construct($file=null)
    {
        if (null !== $file) {
            $this->file = $file;
        }
    }

    /**
     * Destructs the logger, closing opened log file
     */
    public function __destruct()
    {
        if ($this->_file) {
            fclose($this->_file);
        }
        parent::__destruct();
    }

    /**
     * Sets a file to log to
     *
     * @param string|resource $file File name or resource
     * @throws Log_Exception When file not found or not writable
     */
    public function set_file($file)
    {
        if (is_resource($file)) {
            $this->_file = $file;
        } else if (!$this->_file = fopen($file, 'a')) {
            throw new Log_Exception(
                "Error opening log file {$file}",
                Log_Exception::IO_ERROR
            );
        }
        return $this;
    }

    /**
     * Gets the current file it logs to
     *
     * @return mixed
     */
    public function get_file()
    {
        return $this->_file;
    }

    /**
     * @see Log_Interface::write()
     */
    public function write($msg, $lvl=self::LEVEL_INFO)
    {
        if ($this->_file && ((int)$this->is_verbose >= $lvl)) {
            if (flock($this->_file, LOCK_EX)) {
                fputs(
                    $this->_file,
                    gmdate('c') . ' ' .
                        Environment::get_pid() . ' ' .
                        $lvl . ' ' .
                        $this->prepare($msg) . "\n"
                );
                flock($this->_file, LOCK_UN);
            }
        }
        return $this;
    }
}
