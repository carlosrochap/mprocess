<?php
/**
 * @package Log
 */

/**
 * Database logger
 *
 * @package Log
 */
class Log_Db extends Log_Abstract
{
    /**
     * Default DB table to use
     */
    const DEFAULT_TABLE = 'log';


    /**
     * Prepared statement to add log messages
     *
     * @var MySQLi_STMT
     */
    protected $_stmt = null;


    /**
     * Creates a DB logger
     *
     * @param MySQLi $db    Database connection to use
     * @param string $table Table to write messages into
     */
    public function __construct(MySQLi $db=null, $table=self::DEFAULT_TABLE)
    {
        if ($db) {
            $this->set_db($db, $table);
        }
    }

    /**
     * Closes used prepared statement
     */
    public function __destruct()
    {
        if ($this->_stmt) {
            $this->_stmt->close();
        }
        parent::__destruct();
    }

    /**
     * Sets DB connection and table to use, prepares a statement
     *
     * @param MySQLi $db
     * @param string $table
     * @throws Log_Exception When failed preparing an SQL statement
     */
    public function set_db(MySQLi $db, $table=self::DEFAULT_TABLE)
    {
        $this->_stmt = $db->prepare('
            INSERT INTO `' . $db->real_escape_string($table) . '`
            SET `pid` = ?,
                `msg` = ?,
                `lvl` = ?,
                `added_at` = NOW()
        ');
        if (!$this->_stmt) {
            throw new Log_Exception(
                "Failed preparing SQL statement: {$db->error}",
                Log_Exception::DB_ERROR
            );
        }
        return $this;
    }

    /**
     * Gets the DB connection it uses
     *
     * @return MySQLi $db
     */
    public function get_db()
    {
        return $this->_db;
    }

    /**
     * @see Log_Interface::write()
     * @throws Log_Exception When failed adding a message to log table
     */
    public function write($msg, $lvl=self::LEVEL_INFO)
    {
        if ($this->_stmt) {
            $this->_stmt->bind_param(
                'isi',
                Environment::get_pid(),
                $this->prepare($msg),
                $lvl
            );
            if (!$this->_stmt->execute()) {
                throw new Log_Exception(
                    "Failed updating log: {$this->_stmt->error}",
                    Log_Exception::IO_ERROR
                );
            }
            $this->_stmt->reset();
        }
        return $this;
    }
}
