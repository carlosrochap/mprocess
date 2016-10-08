<?php
/**
 * Utility wrapper object around mysqli
 * @version 1.02a (2009-02-27)
 * @package mysqlk
 */
require_once 'common-util.inc.php';

/* module init */
ini_set('mysqli.reconnect', '1');
mysqli_report(MYSQLI_REPORT_OFF); //this doesn't even work...

class mysqlkException extends Exception {}
class mysqlkConnectFailed extends mysqlkException {}
class mysqlkQueryFailed extends mysqlkException {}

/**
 * Utility wrapper object around mysqli
 */
class mysqlk extends mysqli {
	/**
	 * @var tuple
	 * Time tuple of form (mins, secs, msecs); updated after each connect/query function with function's approx runtime
	 */
	var $time_taken = array(-1, -1, -1);
	/**
	 * @var int
	 * Reconnect retry attempts
	 */
	var $reconnect_retries = 2;
	/**
	 * @var int
	 * Seconds to sleep between reconnect retries
	 */
	var $reconnect_sleep = 5;

	/**
	 * @var bool
	 * On query failure, throw an mysqlkQueryFailed exception instead of returning false
	 */
	var $exception_on_failure = false;

	/**
	 * Utility constructor for mysqlk objects; see mysqlk->connect for params. Equivilant to calling new mysqlk, then mysqlk->connect.
	 * @param string Username
	 * @param string Password
	 * @param string DB location of form addr[:port]/db_name
	 * @param bool Whether or not to set session.sql_mode to traditional (default: true)
	 * @return tuple (true, mysqlk) on success, (false, reason) on failure
	 */
	static function make($user, $login, $db_loc, $trad=true) {
		$r = new mysqlk($trad);
		if (!$r->connect($user, $login, $db_loc))
			return array(false, $r->error);
		return array(true, $r);
	}

	/**
	 * Create a new mysqlk object
	 * @param bool Whether or not to set session.sql_mode to traditional
	 */
	function __construct($trad=true) {
		parent::__construct();
		$this->init();
		$this->options(MYSQLI_OPT_CONNECT_TIMEOUT, 90);
		#turn off stupid mode
		if ($trad)
			$this->options(MYSQLI_INIT_COMMAND, "set session sql_mode = 'TRADITIONAL'");
	}

	/**
	 * Connects a db
	 * @param string Username
	 * @param string Password
	 * @param string DB location of form addr[:port]/db_name
	 * @param bool Use global cache (default: true)
	 * @return bool true on success, false otherwise
	 */
	function connect($user, $passwd, $db_loc, $use_cache=true) {
		$r = split_db_loc($db_loc);
		if (!$r)
			return false;
		list($host, $port, $db) = $r;
		/* mysqli_report doesn't actually work, so we need to silence any stupid connection failed warnings */
		list($m, $s, $ms, $r) = @time_fn(array($this, 'real_connect'), $host, $user, $passwd, $db, $port);
		$this->time_taken = array($m, $s, $ms);
		return $r;
	}

	/**
	 * Attempt to reconnect to the server if disconnected
	 */
	function reconnect() {
		for ($tries=0; !$this->ping() && $tries < $this->reconnect_retries; ++$tries)
			sleep($this->reconnect_sleep);
		if ($tries == $this->reconnect_retries) //tries exhausted
			return false;
		return true;
	}

	/**
	 * Wrapper around mysqli->query; parameters and return are the same as for mysqli->query
	 */
	function query($query, $result_mode=MYSQLI_STORE_RESULT) {
		if (!$this->reconnect()) {
			if ($this->exception_on_failure)
				throw new mysqlkConnectFailed($this->error);
			else
				return false;
		}
		/*
		 array($this, 'parent::query') callback format only works if we use call_user_func
		 within the class; since time_fn is external, we need to specify the parent manually
		 else it will try and call base::parent::fn as static
		*/
		list($m, $s, $ms, $r) = time_fn(array($this, 'mysqli::query'), $query, $result_mode);
		$this->time_taken = array($m, $s, $ms);
		if ($this->exception_on_failure && $r === false)
			throw new mysqlkQueryFailed($this->error);

		return $r;
	}

	/**
	 * Wrapper around mysqli->multi_query
	 */
	function multi_query($query) {
		if (!$this->reconnect()) {
			if ($this->exception_on_failure && $r === false)
				throw new mysqlkQueryFailed($this->error);
			else
				return false;
		}
		/*
		 see above regarding this
		*/
		list($m, $s, $ms, $r) = time_fn(array($this, 'mysqli::multi_query'), $query);
		$this->time_taken = array($m, $s, $ms);
		if ($this->exception_on_failure && $r === false)
			throw new mysqlkQueryFailed($this->error);
		return $r;
	}

	/**
	 * Returns a result object containing the specified fields from a select statement, with an optional condition
	 * @param string Name of table
	 * @param list List of fields to retrieve
	 * @param string Optional condition for select statement
	 * @param string Optional stuff such as order/group by, limit
	 * @return mysqli_result The request of the query
	 */
	function selectDict($table, $fields, $cond=null, $extra=null) {
		$stmt = sprintf('select %s from %s%s%s',
			self::listToCols($fields), $table,
			(!empty($cond) ? ' where ' . $cond : ''),
			(!empty($extra) ? ' ' . $extra : ''));
		return $this->query($stmt);
	}

	/**
	 * Inserts a record into the specified table
	 * @param string Name of table
	 * @param dict Dict containing record to insert
	 * @param bool Use INSERT IGNORE ...
	 * @return bool true on success, false on failure
	 */
	function insertDict($table, $values, $ignore=false) {
		$stmt = sprintf('insert %sinto %s (%s) values (%s)', $ignore ? 'ignore ' : '', $table,
			self::listToCols(array_keys($values)),
			self::listToVals(array_values($values)));
		return $this->query($stmt);
	}

	/**
	 * Performs a batch insert of values; must all contain
	 * the same fields
	 * @param string Name of table
	 * @param [dict] List of dicts to insert (must all have the same fields)
	 * @param bool Use INSERT IGNORE ...
	 * @return bool true on success, false on failure
	 * @note Does -no- checking to see if cols match for all values; takes
	 * column list from the first row in the list and uses that to preserve order
	 */
	function batchInsert($table, $rows, $ignore=true) {
		$columns_lst = array_keys($rows[0]);
		$columns = self::listToCols($columns_lst);

		$values = array();
		foreach ($rows as $row) {
			$v = array();
			foreach ($columns_lst as $c)
				$v[] = $row[$c];
			$values[] = sprintf('(%s)', self::listToVals($v));
		}
		$values = implode(',', $values);

		$stmt = sprintf('insert %sinto %s (%s) values %s',
			$ignore ? 'ignore ' : '',
			$table, $columns, $values);
		return $this->query($stmt);
	}

	/**
	 * Takes keys from a dict and returns a proper comma-delimited string of fields
	 */
	static function listToCols($list) { return implode(',', self::add_backticks($list)); }

	/**
	 * Takes values from a dict and returns a proper comma-delimited string of values
	 */
	static function listToVals($list) { return implode(',', self::quote_values($list)); }

	/**#@+
	 * @internal
	 * @access private
	 */

	/**
	 * Given an array of strings, enclose in backticks
	 */
	private static function add_backticks($a) {
		$r = array();
		foreach ($a as $i)
			array_push($r, "`${i}`");
		return $r;
	}

	/**
	 * Given an array of strings, escapes quotes, etc and encloses all items in double quotes
	 */
	private static function quote_values($a) {
		$r = array();
		foreach ($a as $i)
			array_push($r, '"' . addslashes($i) . '"');
		return $r;
	}

	/**#@-*/
}
?>
