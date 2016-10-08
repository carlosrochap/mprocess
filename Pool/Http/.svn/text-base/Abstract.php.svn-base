<?php
/**
 * @package Pool
 */

/**
 * Base class for pools with HTTP sources
 *
 * @property Connection_Interface $connection
 *
 * @package Pool
 * @subpackage Http
 */
abstract class Pool_Http_Abstract extends Pool_Abstract
{
    const RETRY_COUNT = 3;


    /**
     * Connection to use
     *
     * @var Connection_Interface
     */
    protected $_connection = null;

    /**
     * Sources hash table
     *
     * @var array
     */
    protected $_sources = array();


    /**
     * Tries to fill a pool by making HTTP request and parsing response body
     * if no custom filling methods defined.
     *
     * @see Pool_Abstract::_prepare()
     */
    protected function _prepare($name, $size)
    {
        if (parent::_prepare($name, $size)) {
            return true;
        }

        if (!$this->_connection) {
            return false;
        }

        $pool = &$this->_pools[$name];
        $src = &$this->_sources[$name];
        if ($src && !empty($src['url']) && !empty($src['regex'])) {
            if (preg_match_all(
                $src['regex'],
                $this->_connection->get($src['url']),
                $pool,
                PREG_SET_ORDER
            )) {
                foreach ($pool as &$item) {
                    $item = array_values(array_map(
                        'html_entity_decode',
                        array_slice($item, 1)
                    ));
                }
                shuffle($pool);
            } else {
                $pool = null;
            }
        }
        return (is_array($pool) && count($pool));
    }


    /**
     * Sets a connection to use
     *
     * @param Connection_Interface $connection
     */
    public function set_connection(Connection_Interface $connection)
    {
        $this->_connection = $connection;
        return $this;
    }

    /**
     * Returns the connection currently in use
     *
     * @return Connection_Interface
     */
    public function get_connection()
    {
        return $this->_connection;
    }
}
