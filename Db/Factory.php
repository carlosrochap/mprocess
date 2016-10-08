<?php
/**
 * @package Db
 */

/**
 * DB connections factory
 *
 * @package Db
 */
abstract class Db_Factory
{
    /**
     * Default DB connection
     */
    const DEFAULT_DB = 'default';


    /**
     * Connections pool
     *
     * @var array
     */
    static protected $_dbs = array();


    /**
     * Creates a connection object
     *
     * @param string $name   Unique connection name
     * @param mixed  $config Project configuration (for legacy behaviour)
     *                       or Pear::DB-like DSN string
     * @return object
     */
    static public function factory($name, $config)
    {
        $name = $name
            ? strtolower($name)
            : self::DEFAULT_DB;

        if (!$db = &self::$_dbs[$name]) {
            $dsn = null;

            if (is_array($config)) {
                if (isset($config['db'][$name])) {
                    $dsn = new Db_Dsn($config['db'][$name]);
                } else if (isset($config["db_{$name}"])) {
                    $dsn = new Db_Dsn();

                    $a = $config["db_{$name}"];

                    list($dsn->user, $dsn->pass) = isset($a['userpass'])
                        ? explode(':', $a['userpass'], 2)
                        : array($a['user'], $a['pass']);

                    $dsn->host = $a['host'];
                    if (isset($a['port'])) {
                        $dsn->port = $a['port'];
                    }

                    $dsn->dbname = $a['name'];
                }
            } else {
                $dsn = new Db_Dsn($config);
            }

            if (!$dsn) {
                throw new Db_Exception(
                    "Params for {$name} connection not found",
                    Db_Exception::INVALID_ARGUMENT
                );
            }

            while (true) {
                $db = @new MySQLi($dsn->host, $dsn->user, $dsn->pass, $dsn->dbname, $dsn->port);
                if (@$db->ping()) {
                    /**
                     * Due to unknown legacy reasons we use latin1
                     * (effectively --- raw strings) to hold localized
                     * textual data.
                     */
                    $db->query('SET NAMES latin1');
                    break;
                }
                sleep(60);
            }
        }

        return $db;
    }

    /**
     * Closes and removes caches DB connection
     *
     * @param string $name
     */
    static public function close($name)
    {
        if ($db = &self::$_dbs[$name]) {
            if (is_object($db) && method_exists($db, 'close')) {
                @$db->close();
            }
            unset($db, self::$_dbs[$name]);
        }
    }
}
