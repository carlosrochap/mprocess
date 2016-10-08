<?php
/**
 * @package Connection
 */

/**
 * Base class for connections
 *
 * @package Connection
 */
abstract class Connection_Abstract
    extends Loggable
    implements Connection_Interface
{
    /**
     * Try not to use the constructor for connection-specific initialization,
     * use {@link ::init()} instead.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Try not to use the destructor for connection-specific clean up
     * routines, use {@link ::close()} instead.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Extend to your needs, return parent::init() or $this for chaining
     */
    public function init()
    {
        return $this;
    }

    /**
     * Extend to your needs, return parent::close() or $this for chaining
     */
    public function close()
    {
        return $this;
    }
}
