<?php
/**
 * @package Connection
 */

/**
 * Dummy connection for test purposes
 *
 * @package Connection
 */
class Connection_Dummy extends Connection_Abstract
{
    protected $_data = array();


    /**
     * Allows any calls filling faux pau request/response properties
     *
     * @param string $method
     * @param array  $args
     * @return array
     */
    public function __call($method, $args)
    {
        $this->_data['last_url'] =
            count($args)
                ? $args[0]
                : '';

        $this->_data['effective_url'] = &$this->_data['last_url'];

        $this->_data['method'] = $method;
        $this->_data['args'] = $args;

        $this->_data['response'] = array(
            'method' => &$this->_data['method'],
            'url'    => &$this->_data['last_url'],
            'args'   => &$this->_data['args']
        );

        return $this->_data['response'];
    }

    /**
     * Allows fetching any set properties
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return array_key_exists($key, $this->_data)
            ? $this->_data[$key]
            : null;
    }

    /**
     * Allows setting any properties
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }
}
