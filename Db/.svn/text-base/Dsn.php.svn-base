<?php
/**
 * @package Db
 */

/**
 * Pear::DB-like DSN implementation
 *
 * @package Db
 * @subpackage Dsn
 */
class Db_Dsn extends Url
{
    /**
     * Parses DSN components out of arbitrary string
     *
     * @param string $dsn
     * @return array
     */
    static public function parse($dsn)
    {
        $components = parent::parse($dsn);
        if (!empty($components['path'])) {
            $a = explode('/', trim($components['path'], '/'), 2);
            $components['dbname'] = $a[0];
            $components['tblname'] = (string)@$a[1];
        }
        foreach (array('path', 'query', 'fragment') as $k) {
            unset($components[$k]);
        }
        return $components;
    }

    /**
     * Composes Pear::DB-like DSN string out of arbitrary components
     *
     * @param array $components
     * @return string
     */
    static public function compose(array $components)
    {
        $components['path'] =
            "/{$components['dbname']}" .
            ($components['tblname']
                ? "/{$components['tblname']}"
                : '');
        foreach (array('query', 'fragment') as $k) {
            unset($components[$k]);
        }
        return parent::compose($components);
    }


    /**
     * Instantiates a DSN parser
     *
     * @param string $dsn Optional DSN
     */
    public function __construct($dsn=null)
    {
        foreach (array('scheme', 'host', 'port') as $k) {
            $const = 'Db_Abstract::DEFAULT_' . strtoupper($k);
            if (defined($const)) {
                $this->_data[$k] = constant($const);
            }
        }
        parent::__construct($dsn);
    }
}
