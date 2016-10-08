<?php
/** * @package Service
 */

/**
 * Base class for URL shorteners
 *
 * @package Service
 * @subpackage UrlShortener
 */
abstract class Service_UrlShortener_Abstract
    extends Base_Abstract
    implements Service_UrlShortener_Interface
{
    /**
     * Connection to use
     *
     * @var Connection_Interface
     */
    protected $_connection = null;


    /**
     * Filters arbitrary HTML encoded content
     *
     * @param string $s
     * @return string
     */
    static public function filter($s)
    {
        return html_entity_decode($s, ENT_QUOTES);
    }


    /**
     * Inits the shortener
     */
    public function init()
    {
        if ($this->_connection) {
            $this->_connection->init();
        }
        return $this;
    }

    /**
     * Sets the connection to use
     *
     * @param Connection_Interface $connection
     */
    public function set_connection(Connection_Interface $connection)
    {
        $this->_connection = $connection;
        return $this;
    }

    /**
     * Returns the connection used
     *
     * @return Connection_Interface
     */
    public function get_connection()
    {
        return $this->_connection;
    }

    /**
     * Sets connection verbosity
     *
     * @param bool $is_verbose
     */
    public function set_is_verbose($is_verbose)
    {
        if ($this->_connection) {
            $this->_connection->is_verbose = (bool)$is_verbose;
        }
        return $this;
    }

    /**
     * Returns connection verbosity
     *
     * @return bool
     */
    public function get_is_verbose()
    {
        return $this->_connection && $this->_connection->is_verbose;
    }
}
