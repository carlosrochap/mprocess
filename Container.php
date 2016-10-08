<?php
/**
 * @package Base
 */

/**
 * Base class for container-like classes
 *
 * @package Base
 */
class Container implements ArrayAccess
{
    /**
     * Data container
     *
     * @var array
     */
    protected $_data = array();


    /**
     * Checks if a property exists
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_data);
    }

    /**
     * Removes a property
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $method = "remove_{$offset}";
        if (method_exists($this, $method)) {
            $this->{$method}();
        } else {
            unset($this->_data[$offset]);
        }
    }

    /**
     * Fetches a property
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $method = "get_{$offset}";
        return method_exists($this, $method)
            ? $this->$method()
            : ($this->offsetExists($offset)
                ? $this->_data[$offset]
                : null);
    }

    /**
     * Sets a property
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $method = "set_{$offset}";
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->_data[$offset] = $value;
        }
    }

    /**
     * Alias for {@link ::offsetGet()}
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Alias for {@link ::offsetSet()}
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * @ignore
     */
    public function __sleep()
    {
        return array('_data');
    }
}
