<?php
/**
 * @package Captcha
 */

/**
 * Base class for CAPTCHA decoders
 *
 * @package Captcha
 * @subpackage Decoder
 */
abstract class Captcha_Decoder_Abstract
    extends Captcha_Abstract
    implements Captcha_Decoder_Interface
{
    const DEFAULT_TIMEOUT = 90;


    /**
     * Decoder service' account credentials
     *
     * @var Credentials
     */
    protected $_credentials = null;

    protected $_timeout = 0;
    protected $_match = '';


    /**
     * Extend to your needs, return parent::init() for chaining.
     * May be called more than once.
     */
    public function init()
    {
        $this->_credentials = new Credentials();
        $this->_timeout = self::DEFAULT_TIMEOUT;
        $this->_match = '';
        return parent::init();
    }

    /**
     * Sets decoder service's credentials
     *
     * @param string|Credentials $username Username or credentials container
     * @param string             $pass     Optional password
     */
    public function set_credentials($username, $pass=null)
    {
        if ($username instanceof Credentials) {
            $this->_credentials = $username;
        } else {
            list(
                $this->_credentials->user,
                $this->_credentials->pass
            ) = (null === $pass)
                ? explode(':', $username, 2)
                : array($username, $pass);
        }
        return $this;
    }

    /**
     * Returns credentials container
     *
     * @return Credentials
     */
    public function get_credentials()
    {
        return $this->_credentials;
    }

    public function set_timeout($timeout)
    {
        $this->_timeout = max(0, (int)$timeout);
        if (!$this->_timeout) {
            $this->_timeout = self::DEFAULT_TIMEOUT;
        }
        return $this;
    }

    public function get_timeout($timeout)
    {
        return $this->_timeout;
    }

    public function set_match($match)
    {
        $this->_match = (string)$match;
        return $this;
    }

    public function get_match($match)
    {
        return $this->_match;
    }

    /**
     * @see Captcha_Decoder_Interface::decode()
     *
     * Extend to your needs, call parent::decode() to check if the file
     * exists and is readable
     *
     * @throws Captcha_Decoder_Exception When CAPTCHA image file is unreadable
     */
    public function decode($fn, $timeout=0, $match='')
    {
        if (!is_file($fn) || !is_readable($fn)) {
            throw new Captcha_Decoder_Exception(
                "{$fn} not found or unreadable",
                Captcha_Decoder_Exception::IO_ERROR
            );
        }
        return $this;
    }

    /**
     * Allows setting credentials properties
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        if (isset($this->_credentials[$key])) {
            $this->_credentials[$key] = $value;
        } else {
            parent::__set($key, $value);
        }
    }

    /**
     * Allows fetching credentials properties
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->_credentials[$key])
            ? $this->_credentials[$key]
            : parent::__get($key);
    }
}
