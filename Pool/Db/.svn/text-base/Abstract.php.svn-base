<?php
/**
 * @package Pool
 */

/**
 * Base class for pools with DB sources
 *
 * @package Pool
 * @subpackage Db
 */
abstract class Pool_Db_Abstract extends Pool_Abstract
{
    /**
     * Default database connection
     */
    const DEFAULT_DB = 'default';


    /**
     * SQL to PHP data types mapping.
     * BIT & TINYINT fields are treated as booleans!
     *
     * @var array
     */
    static protected $_sql_types_map = array(
        'bool' => array(
            MYSQLI_TYPE_BIT,
            MYSQLI_TYPE_TINY,
        ),
        'int' => array(
            MYSQLI_TYPE_SHORT,
            MYSQLI_TYPE_INT24,
            MYSQLI_TYPE_LONG,
        ),
        'float' => array(
            MYSQLI_TYPE_DECIMAL,
            MYSQLI_TYPE_NEWDECIMAL,
            MYSQLI_TYPE_FLOAT,
            MYSQLI_TYPE_DOUBLE,
        ),
    );

    /**
     * List of MySQL client/server errors which should silently skip
     *
     * @var array
     */
    static protected $_warnings = array(
        1062,  // ER_DUP_ENTRY
    );

    /**
     * List of MySQL client/server errors of temporary nature
     *
     * @var array
     */
    static protected $_recoverable_errors = array(
        1020,  // ER_CHECKREAD
        1040,  // ER_CON_COUNT_ERRORj
        1042,  // ER_BAD_HOST_ERROR
        1053,  // ER_SERVER_SHUTDOWN
        1077,  // ER_NORMAL_SHUTDOWN
        1079,  // ER_SHUTDOWN_COMPLETE
        1152,  // ER_ABORTING_CONNECTION
        1184,  // ER_NEW_ABORTING_CONNECTION
        1203,  // ER_TOO_MANY_USER_CONNECTIONS
        1205,  // ER_LOCK_WAIT_TIMEOUT
        1213,  // ER_LOCK_DEADLOCK
        1243,  // ER_UNKNOWN_STMT_HANDLER
        2006,  // CR_SERVER_GONE_ERROR
        2013,  // CR_SERVER_LOST
        2014,  // CR_COMMANDS_OUT_OF_SYNC
    );


    /**
     * Prepared statements to use
     *
     * @var array
     */
    protected $_stmts = array();

    /**
     * Prepared statements metadata cache
     *
     * @var array
     */
    protected $_stmts_meta = array();


    /**
     * Tweaks SQL types map for 32-bit systems to treat long ints as floats
     *
     * @see Pool_Abstract::__construct()
     */
    public function __construct(array $config=array())
    {
        $i = count(self::$_sql_types_map['int']) - 1;
        if (
            (4 >= PHP_INT_SIZE) &&
            (MYSQLI_TYPE_LONG == self::$_sql_types_map['int'][$i])
        ) {
            array_push(
                self::$_sql_types_map['float'],
                array_pop(self::$_sql_types_map['int'])
            );
        }
        parent::__construct($config);
    }

    /**
     * Closes prepared statements
     *
     * @see Pool_Abstract::close()
     */
    public function close()
    {
        foreach (array_filter($this->_stmts, 'is_array') as $stmt) {
            unset($stmt['stmt']);
        }

        return parent::close();
    }

    /**
     * Fetches and prepares a named statement
     *
     * @param string $name
     * @return MySQLi_STMT
     */
    protected function _get_prepared_stmt($name)
    {
        if (!$stmt = &$this->_stmts[$name]) {
            throw new Pool_Db_Exception(
                "Query {$name} not found",
                Pool_Db_Exception::INVALID_ARGUMENT
            );
        }

        if (!is_array($stmt)) {
            $s = strtolower($this->_config['project']['name']);
            $stmt = array(
                'db' => isset($this->_config['db'][$s])
                    ? $s
                    : self::DEFAULT_DB,
                'query' => (string)$stmt,
                'stmt'  => null,
            );
        }

        while (empty($stmt['stmt']) || !is_object($stmt['stmt'])) {
            $db = Db_Factory::factory($stmt['db'], @$this->_config);
            $stmt['stmt'] = @$db->prepare($stmt['query']);
            if (!$stmt['stmt'] || @$db->error) {
                if (
                    !@$db ||
                    !@$db->errno ||
                    in_array(@$db->errno, self::$_recoverable_errors)
                ) {
                    unset($stmt['stmt']);
                    Db_Factory::close($stmt['db']);
                    sleep(self::RETRY_INTERVAL_LONG);
                } else {
                    throw new Pool_Db_Exception(
                        "Failed preparing {$name}: {$db->errno} {$db->error}",
                        Pool_Db_Exception::DB_ERROR
                    );
                }
            }
        }

        if (1 < func_num_args()) {
            $args = func_get_args();
            $args[0] = &$stmt['stmt'];
            call_user_func_array(array($this, '_bind_stmt_params'), $args);
        }

        return $stmt['stmt'];
    }

    /**
     * Closes and removes prepared statement
     *
     * @param string $name Statement name
     * @throws Pool_Db_Exception When statement not found
     */
    protected function _close_prepared_stmt($name)
    {
        if (!$stmt = &$this->_stmts[$name]) {
            throw new Pool_Db_Exception(
                "Query {$name} not found",
                Pool_Db_Exception::INVALID_ARGUMENT
            );
        }

        if (isset($stmt['stmt']) && is_object($stmt['stmt'])) {
            try {
                $stmt['stmt']->close();
            } catch (Exception $e) {
                Db_Factory::close($stmt['db']);
            }
            sleep(self::RETRY_INTERVAL_LONG);
        }

        $stmt['stmt'] = null;
    }

    /**
     * Binds arbitrary params to arbitrary SQL prepared statement
     *
     * @param object $stmt      MySQLi_STMT instance
     * @param mixed  $param,... Any number of statement params
     */
    protected function _bind_stmt_params(MySQLi_STMT &$stmt)
    {
        $params_count = func_num_args() - 1;
        if ($params_count) {
            $params = func_get_args();

            // Populate first params element with params types
            $params[0] = '';
            for ($i = 1; $i <= $params_count; $i++) {
                // MySQLi apparently can't handle integers of 32+ bits
                if (is_int($params[$i]) && ((1 << 31) < $params[$i])) {
                    $params[$i] = (string)$params[$i];
                } else if (is_object($params[$i])) {
                    $method = '__toString';
                    $params[$i] = method_exists($params[$i], $method)
                        ? $params[$i]->{$method}()
                        : (string)$params[$i];
                } else if (is_array($params[$i])) {
                    $params[$i] = serialize($params[$i]);
                }
                if (!is_string($params[$i])) {
                    if (is_bool($params[$i])) {
                        $params[$i] = (int)$params[$i];
                    }
                    $params[0] .= is_float($params[$i]) ? 'd' : 'i';
                } else {
                    $params[0] .= 's';
                }
            }

            @call_user_func_array(array($stmt, 'bind_param'), $params);
        }

        return $this;
    }

    /**
     * Fetches a named statement (cached) metadata
     *
     * @param MySQLi_STMT $stmt Statement in question
     * @param string      $name Optional statement name, will cache metadata
     *                          in {@link ::$_stmts_meta} if set
     * @return MySQLi_Result
     */
    protected function _get_stmt_metadata(MySQLi_STMT &$stmt, $name=null)
    {
        if ($name) {
            $meta = &$this->_stmts_meta[$name];
        } else {
            $meta = null;
        }

        if (empty($meta)) {
            $meta = array();
            $result = $stmt->result_metadata();
            while ($field = $result->fetch_field()) {
                $meta[$field->name] = false;
                foreach (self::$_sql_types_map as $t => &$types) {
                    if (in_array($field->type, $types)) {
                        $meta[$field->name] = $t;
                        break;
                    }
                }
            }
            $result->free();
        }

        return $meta;
    }

    /**
     * Execute a prepared statement (see {@link ::$_stmts})
     *
     * @param string $name Statement name
     * @param mixed  ...   Optional statement params
     * @return mixed List of results for SELECT statements,
     *               bool for INSERT/UPDATE/DELETE
     */
    protected function _execute($name)
    {
        $args = func_get_args();
        $stmt = call_user_func_array(
            array($this, '_get_prepared_stmt'),
            $args
        );

        if (!$stmt->execute()) {
            if (@$stmt->errno) {
                if (in_array($stmt->errno, self::$_warnings)) {
                    return false;
                } else if (in_array($stmt->errno, self::$_recoverable_errors)) {
                    $this->_close_prepared_stmt($name);
                    return call_user_func_array(array($this, '_execute'), $args);
                }
            }
            throw new Pool_Db_Exception(
                "Query {$name} failed: " . @$stmt->errno . ' ' . @$stmt->error,
                Pool_Db_Exception::DB_ERROR
            );
        }

        $result = false;

        if ($stmt->result_metadata()) {
            $stmt->store_result();
            if ($stmt->num_rows) {
                $meta = $this->_get_stmt_metadata($stmt, $name);

                $buf = $row = array();
                foreach (array_keys($meta) as $k) {
                    $buf[] = &$row[$k];
                }
                call_user_func_array(array($stmt, 'bind_result'), $buf);

                $items = array();
                $i = 0;
                while ($stmt->fetch()) {
                    // Dereference results, change type if need
                    foreach ($row as $k => $v) {
                        if ($meta[$k]) {
                            settype($v, $meta[$k]);
                        }
                        $items[$i][$k] = $v;
                    }
                    // If there's only one field, use it
                    if (1 == count($items[$i])) {
                        $items[$i] = array_pop($items[$i]);
                    }
                    $i++;
                }
                if ($i) {
                    $result = &$items;
                }
            }
            $stmt->free_result();
        } else {
            $result = $stmt->affected_rows;
        }

        $stmt->reset();
        return $result;
    }

    /**
     * Tries to fill a pool using prepared statements if no custom
     * filling methods defined
     *
     * @see Pool_Abstract::_prepare()
     */
    protected function _prepare($name, $size)
    {
        if (parent::_prepare($name, $size)) {
            return true;
        }

        $pool = &$this->_pools[$name];
        $stmt = "fill_{$name}";
        if (isset($this->_stmts[$stmt])) {
            $pool = $this->_execute($stmt, $size);
            if (is_array($pool)) {
                shuffle($pool);
            }
        }
        return (is_array($pool) && count($pool));
    }

    public function add_statement($name, $query, $db=null)
    {
        $this->_stmts[strtolower($name)] = (null === $db)
            ? $query
            : array(
                'db'    => $db,
                'query' => $query,
              );
        return $this;
    }

    /**
     * Allows to call prepared statements as pool methods
     *
     * @param string $method
     * @param mixed  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = strtolower($method);
        if (isset($this->_stmts[$method])) {
            array_unshift($args, $method);
            return call_user_func_array(array($this, '_execute'), $args);
        }
        throw new Pool_Db_Exception(
            "Query {$method} not found",
            Pool_Db_Exception::INVALID_ARGUMENT
        );
    }
}
