<?php
/**
 * @package Base
 */

/**
 * Base class for most of the framework classes, defines
 * generic properties accessors that use custom setters/getters
 *
 * @package Base
 */
abstract class Base_Abstract
{
    /**
     * Maximum number of arbitrary action retries and short/long
     * intervals (in seconds) between retries
     */
    const RETRY_COUNT          = 3;
    const RETRY_INTERVAL_SHORT = 5;
    const RETRY_INTERVAL_LONG  = 60;


    /**
     * Generic setter
     *
     * @param string $key
     * @param mixed  $value
     * @throw Base_Exception When custom setter not found
     */
    public function __set($key, $value)
    {
        $m = "set_{$key}";
        if (!method_exists($this, $m)) {
            throw new Base_Exception(
                get_class($this) . "::{$key} attribute not found or read only",
                Base_Exception::INVALID_ARGUMENT
            );
        }
        $this->{$m}($value);
    }

    /**
     * Generic getter
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $m = "get_{$key}";
        return method_exists($this, $m)
            ? $this->{$m}($key)
            : null;
    }
}
