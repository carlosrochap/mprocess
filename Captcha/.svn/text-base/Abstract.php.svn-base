<?php
/**
 * @package Captcha
 */

/**
 * Base class for CAPTCHA-related classes
 *
 * @package Captcha
 */
abstract class Captcha_Abstract extends Loggable
{
    /**
     * Try not to extend the constructor for client-specific initialization,
     * use {@link ::init()} instead.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Try not to extend the destructor for client-specific clean up, use
     * {@link ::close()} instead.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Extend to your needs, return parent::init() for chaining.
     * May be called more than once.
     */
    public function init()
    {
        return $this;
    }

    /**
     * Extend to your needs, return parent::close() for chaining.
     */
    public function close()
    {
        return $this;
    }
}
