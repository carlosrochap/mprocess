<?php
/**
 * Common utility functions
 * @version 1.15b (2009-03-24)
 * @package common-utils
 */

ini_set('error_reporting', E_STRICT | E_ALL);

class FetchMagicFailure extends Exception {}

/**
 * Opens a file, creating the directory tree to do so if required
 * @param string Path to file
 * @param string File open mode
 * @param octal Dir perms
 * @return resource The opened file resource, or null on error
 */
function open_with_path($path, $file_mode, $dir_mode=0744) {
	@mkdir($path, $dir_mode, true);
	return fopen($path, $file_mode);
}

/**
 * Parses addr[:port]/db into a 3 item tuple
 * @param string String of format 'addr[:port]/db' (port defaults to 3306 if not given)
 * @return tuple (addr, port, db) on success, null on error (invalid format)
 */
function split_db_loc($str) {
	if (!preg_match('@(?P<addr>[\w.]+?)(?::(?P<port>[\d]+))?/(?P<db>\w+)@', $str, $match))
		return null;
	if (empty($match['port']))
		$port = 3306;
	else
		$port = intval($match['port']);

	return array($match['addr'], $port, $match['db']);
}

/**
 * Converts a conf file of format v = k into a dict
 * @param string Path to file
 * @return dict dict containing all value k,v pairs
 */
function conf_to_dict($path) {
	$lines = explode("\n", file_get_contents($path));
	$dict = array();
	foreach ($lines as $l) {
		if (!preg_match('@^(?P<option>[^#].*?)(?:[\s]*)=(?:[\s]*)(?P<value>.*)@', $l, $match))
			continue;
		$dict[$match['option']] = $match['value'];
	}
	return $dict;
}

/**
 * Write a conf file of format k = v from a dict
 * @param dict Dict to use
 * @param string Path to conf
 * @return bool True if successful or false if unable to open file for writing
 */
function dict_to_conf($dict, $path) {
	if (!($f = fopen($path, 'wb')))
		return false;
	foreach($dict as $k => &$v) {
		fwrite($f, sprintf("%s = %s", $k, $v));
	}
	fclose($f);
	return true;
}

/**
 * Calculates time difference (taken from microtime(true)) and returns results in (m, s, ms)
 * @param float Start timestamp
 * @param float Stop timestamp
 * @return tuple (mins, secs, msecs)
 */
function calc_runtime($start, $stop) {
	$time_taken = round($stop-$start, 3);
	### break into needed units ###
	$mins = floor($time_taken/60);
	$secs = $time_taken % 60;
	$msecs = ($time_taken - floor($time_taken))*1000;
	return array($mins, $secs, $msecs);
}

/**
 * Calculates the runtime of a funciton
 * @param function Function to call
 * @param mixed $args,... Arguments to function
 * @return tuple (mins, secs, msecs, fn_result)
 */
function time_fn($fn) {
	$args = func_get_args();
	array_shift($args);
	$start = microtime(true);
	$r = call_user_func_array($fn, $args);
	$stop = microtime(true);
	list($m, $s, $ms) = calc_runtime($start, $stop);
	return array($m, $s, $ms, $r);
}

/**
 * Prints a time 3-tuple
 * @param tuple (mins, secs, msecs)
 * @return string String repr
 */
function minitime_to_str($t) {
	return sprintf("%dm%ds%dms", $t[0], $t[1], $t[2]);
}

/**
 * Returns true if given doc is a proxy-returned error page
 * @param string The page
 * @return tuple (true, error), or (false, null)
 */
function is_proxy_error($doc) {
	if (strpos($doc, '<TITLE>ERROR: The requested URL could not be retrieved</TITLE>') !== false
		|| strpos($doc, '<TITLE>ERROR: Cache Access Denied</TITLE>') !== false) {
		preg_match('@The following error was encountered:.*<STRONG>\s*(?P<error>.+?)\s*</STRONG>(?:.*<I>\s*(?P<extended>.+?)\s*</I>)?@si', $doc, $m);
		return array(true, $m['error'] .
			(!empty($m['extended']) ? ': ' . $m['extended'] : '')
		);
	}
	else
		return array(false, null);
}

/* wish you could nest functions... */
function __empty_fn__() { ; }

/**
 * Calls a function, ignoring any (non-fatal) errors and exceptions thrown
 * @param function Function to call
 * @param list List of arguments to function
 * @param mixed Value to return on error
 * @return mixed Return value of function, or default on error
 */
function ignore_errors($fn, $args, $default=null) {
	$r = $default;
	$old_handler = set_error_handler('__empty_fn__');
	try { $r = call_user_func_array($fn, $args); }
	catch (Exception $e) { ; }
	set_error_handler($old_handler);
	return $r;
}

/**
 * Splits a sequence into (true, false) given a predicate
 * @param sequence Anything supporting foreach
 * @param function Predicate
 * @return tuple (true_items, false_items)
 */
function seq_split($seq, $predicate) {
	$t = array();
	$f = array();
	foreach($seq as $i)
		call_user_func($predicate, $i) ? array_push($t, $i) : array_push($f, $i);
	return array($t, $f);
}

/**
 * Generate a random string of the given length
 * @param int Size of string
 * @return string A random string
 */
function rand_str($n=15) {
	$cur = '';
	for ($i=0; $i < $n; ++$i)
		$cur .= rand_chr();
	return $cur;
}

/**
 * Return a random char in [a-zA-Z0-9]
 */
function rand_chr() {
	switch (mt_rand(0,2)) {
		case 0: //lowercase
			return chr(mt_rand(97, 122));
			break;
		case 1: //uppercase
			return chr(mt_rand(0x41, 0x5A));
			break;
		case 2: //numbers
			return chr(mt_rand(48, 57));
			break;
	}
}

/**
 * Checks if a user constant is already defined
 * @param string Const to check for
 * @return bool True if defined, false otherwise
 */
function isdef($const) {
	$consts = get_defined_constants(true);
	return array_key_exists('user', $consts) ? array_key_exists($const, $consts['user']) : false;
}

/**
 * Returns an item from an array if it exists, else returns a default value
 * @param array Array to search
 * @param string Key to look for
 * @param mixed Default value to use if k not in a
 * @return mixed a[k] or default
 */
function getitem(array $a, $k, $default=null) { return array_key_exists($k, $a) ? $a[$k] : $default; }

/**
 * Returns the string with replaced newlines and tabs/spaces to be rendered properly as html
 * @param string String to convert
 * @param int Tab width; defaults to 4
 * @return string Converted string
 */
function htmlify_ws($string, $tw=4) {
	return str_replace(
			array("\n", ' ', "\t"),
			array('<br/>', '&nbsp;', str_repeat('&nbsp;', $tw)),
			$string);
}

/**
 * Returns a range of numbers split into X pieces
 * @param int Range start
 * @param int Range stop
 * @param int Number of pieces
 * @return list Numbers split into equal pieces; last piece contains any overflow
 */
function get_ranges($start, $stop, $instances) {
	$total = $stop - $start + 1;
	$rem = $total % $instances;
	$per_instance = floor($total / $instances);
	$ranges = array();

	$cur_start = $start;
	for ($i=0; $i < $instances; ++$i) {
		$cur_end = $cur_start+$per_instance-1;
		array_push($ranges, array($cur_start, $cur_end));
		$cur_start += $per_instance;
	}
	if ($rem)
		$ranges[count($ranges)-1][1] += $rem;
	return $ranges;
}

/**
 * Returns a random value from the sequence
 * @param list Sequence to use
 * @return mixed Random value from the given sequence
 */
function choice($seq) {
	return $seq[array_rand($seq)];
}

/**
 * Returns k unique random elements from a sequence
 * @param list Sequence to use
 * @param int How many elements
 * @return list Random elements from the list
 */
function sample($population, $k) {
	$copy = $population;
	shuffle($copy);
	return array_slice($copy, 0, $k);
}

/**
 * Mimics Python's xrange iterator
 */
class xrange implements Iterator {
	function __construct() {
		$args = func_get_args();
		switch (count($args)) {
			case 1:
				$this->stop = $args[0]-1;
				$this->start = 0;
				$this->step = 1;
				break;
			case 2:
				list($this->start,$this->stop) = $args;
				$this->stop -= 1;
				$this->step = 1;
				break;
			case 3:
				list($this->start, $this->stop, $this->step) = $args;
				$this->stop -= 1;
				break;
			default:
				break;
		}
		$this->rewind();
	}
	function rewind() {
		$this->cur = $this->start;
		$this->cur_k = 0;
	}
	function next() {
		$this->cur += $this->step;
		if ($this->cur > $this->stop)
			return false;
		++$this->cur_k;
		return true;
	}
	function valid() {
		if ($this->cur > $this->stop)
			return false;
		return true;
	}
	function key() {
		return $this->cur_k;
	}
	function current() {
		return $this->cur;
	}

}

//files = ((field_name, path), ...)
//string = blah=blah&...
/**
 * Builds a multipart string with the given fields
 * @param list of 2-tuples (k, v)
 * @param list
 * @return tuple (boundary, multipart_data)
 * @note Must add the proper headers: Content-Type: multipart/form-data; boundary=$boundary
 * @note Must have the 'file' command  in PATH
 */
function build_multipart($params, $files=array()) {
	$nl = "\r\n";
	/* build our post */
	$bound = uniqid('----------------------------');
	$start = '--' . $bound;
	$end = $start . '--';


	#$body = $nl;
	$body = '';
	//build normal form data
	foreach ($params as $tuple) {
		list($k, $v) = $tuple;
		$body .= $start . $nl;
		$body .= "Content-Disposition: form-data; name=\"$k\"" . $nl . $nl;
		$body .= "$v$nl";
	}

	//for files
	foreach ($files as $pair) {
		list($field_name, $f) = $pair;

		if (!empty($f)) {
			$fname = basename($f);
			$type = trim(`file -i -b $f 2>/dev/null`);
			if (strpos($type, 'ERROR:') === 0)
				throw new FetchMagicFailure();
			$dat = file_get_contents($f);
			$len = strlen($dat);
		}
		else {
			$fname = '';
			$type = 'application/octet-stream';
			$dat = '';
			$len = 0;
		}

		$body .= $start . $nl;
		$body .= 'Content-Disposition: form-data; name="' . $field_name . '"; filename="' . $fname . "\"$nl";
		$body .= "Content-Type: $type" . $nl;
		$body .= $nl . $dat . $nl;
	}

	//and done
	$body .= $end . $nl;
	return array($bound, $body);
}

/* ala python's bisect module */
/**
 * Assuming lst is sorted, returns a position i to insert x in l such that l[:i] <= x < l[i:]
 * @param list Sorted list
 * @param mixed value
 * @param int Starting index (default: 0)
 * @param int Stop index (default: len(lst))
 * @return int Position to insert
 */
function bisect_right($lst, $x, $lo=0, $hi=null) {
	if ($hi === null)
		$hi = count($lst);
	foreach (new xrange($lo, $hi) as $k) {
		if ($lst[$k] > $x)
			return $k;
	}
	return $hi;
}

/**
 * Assuming lst is sorted, returns a position i to insert x in l such that l[:i] < x <= l[i:]
 * @param list Sorted list
 * @param mixed value
 * @param int Starting index (default: 0)
 * @param int Stop index (default: len(lst))
 * @return int Position to insert
 */
function bisect_left($lst, $x, $lo=0, $hi=null) {
	if ($hi === null)
		$hi = count($lst);
	foreach (new xrange($lo, $hi) as $k) {
		if ($lst[$k] >= $x)
			return $k;
	}
	return $hi;
}

/**
 * Insert x at position returned by bisect_right in lst
 * @note See bisect_right for more info
 */
function insort_right(&$lst, $x, $lo=0, $hi=null) {
	array_splice($lst,
		bisect_right($lst, $x, $lo, $hi),
		0,
		array($x));
}

/**
 * Insert x at position returned by bisect_left in lst
 * @note See bisect_left for more info
 */
function insort_left(&$lst, $x, $lo=0, $hi=null) {
	array_splice($lst,
		bisect_left($lst, $x, $lo, $hi),
		0,
		array($x));
}

/* end bisect module */

/**
 * Hashs an object if it has a __hash__ function, else calls spl_object_hash
 * @param mixed Any object implementing a __hash__ function
 * @return mixed String or int returned by object::__hash__
 * @note Raises an exception if object::hash doesn't return a string or int
 * @note Works only on php >= 5.2.0
 */
function object_hash($object) {
	if (!method_exists($object, '__hash__'))
		return spl_object_hash($object);

	$r = $object->__hash__();
	if (!is_string($r) && !is_int($r)) {
		$msg = get_class($object) . '::__hash__ must return a string or int';
		throw new Exception($msg);
	}
	return $r;
}

/**
 * Creates a class of a given type
 * @param string Name of class
 * @param ... Arguments for class constructor
 * @return mixed Instance of a given class on success, null if given class
 * is undefined
 */
function create_class_instance($class_type) {
	if (!class_exists($class_type))
		return null;

	$args = array_slice(func_get_args(), 1);
	$rc = new ReflectionClass($class_type);
	#newInstanceArgs is only available in php >= 5.1.3
	#$r = new $class_type(); works also, but no way to pass variable args
	if ($args)
		$r = $rc->newInstanceArgs($args);
	else
		$r = new $class_type;
	return $r;
}

/**
 * Formats a given traceback array
 * @param array Traceback
 * @param limit Depth limit for trace formatting
 * @return array Formated lines
 */
function format_frames($tb, $limit=null) {
	if ($limit)
		$tb = array_slice($tb, 0, $limit);
	$out = array();
	foreach ($tb as $frame) {
		$fn = $frame['function'];
		if (!array_key_exists('file', $frame))
			$frame['file'] = '{main}';
		if (!array_key_exists('line', $frame))
			$frame['line'] = '?';
		if (array_key_exists('class', $frame))
			$fn = $frame['class'] . $frame['type'] . $fn;
		array_unshift($out, sprintf('%s:%s:%s',
			$frame['file'],
			$frame['line'],
			$fn));
	}
	return $out;
}

/**
 * Formats a given exception
 * @param Exception An Exception, or any subclass
 * @param int limit Traceback frame limit (min=1, default: None)
 * @return string Formated exception info
 * @note Similar in format to twisted's Failure.getBriefTraceback()
 */
function format_exc($e, $limit=null) {
	$tb = $e->getTrace();
	$hdr = sprintf("Traceback <type %s>: %s\n", get_class($e), $e->getMessage());
	$out = format_frames($tb, null);
	array_splice($out, -1, 0, '--- <exception caught here> ---');

	return $hdr . implode("\n", $out);
}

/**
 * Condition define()
 * @param string Constant name
 * @param mixed Any primitive value
 * @return none null
 */
function set_ifndef($k, $v) {
	if (!defined($k))
		define($k, $v);
}

/**
 * Converts a dict to a list of 2-tuples
 * @param dict Dict to conver
 * @return list List of (k,v) tuples
 */
function dict_to_tuples(array $d) {
	$r = array();
	foreach ($d as $k => $v)
		$r[] = array($k, $v);
	return $r;
}

/**
 * Returns length of sequence
 * @param mixed Sequence (string, array, object implementing Countable iface)
 * @return int Length of sequence
 */
function len($seq) {
	if (is_string($seq))
		return strlen($seq);
	else if (is_array($seq) || array_key_exists('Countable', class_implements($seq)))
		return count($seq);

	trigger_error("Given variable is not a valid sequence", E_USER_ERROR);
}

/**
 * require_once's a list of files
 * @param mixed $modules,... Files to import
 * @return none None
 * @note auto-appends .inc.php to each file
 */
function import() {
	$mods = func_get_args();
	foreach ($mods as $mod)
		require_once "$mod.inc.php";
}

/**
 * Returns true if a string has the given suffix
 * @param str String
 * @param str Suffix
 * @return bool True/False
 */
function str_endswith($s, $what) {
	$sl = strlen($s);
	$wl = strlen($what);
	if ($sl < $wl)
		return false;
	if ($sl == $wl)
		return $s == $what;
	for ($si=$sl-$wl, $wi=0; $wi < $wl; ++$si, ++$wi)
		if ($s[$si] != $what[$wi])
			return false;
	return true;
}

/**
 * Returns true if a string has the given prefix
 * @param str String
 * @param str Prefix
 * @return bool True/False
 */
function str_startswith($s, $what) {
	return substr($s, 0, strlen($what)) == $what;
}

/**
 * Returns key if in array, not_found otherwise
 * @param array Array to search
 * @param string Key
 * @param mixed 'null' value if not found
 * @returns mixed Value of key in array, not_found otherwise
 */
function array_get($a, $k, $not_found=null) {
	if (!array_key_exists($k, $a))
		return $not_found;
	return $a[$k];
}

/**
 * Given a current url and an arbitrary link, returns the full url
 * of the given link
 * @param string Current url
 * @param string Link
 * @returns string Full url of given link
 */
function abs_url($cur, $url) {
	#relative to root
	if ($url[0] == '/') {
		$p = parse_url($cur);
		if (!array_key_exists('host', $p))
			$url = $p['path'] . $url;
		else {
			if (!array_key_exists('scheme', $p))
				$p['scheme'] = 'http';
			if (!array_key_exists('port', $p))
				$p['port'] = '';
			$url = sprintf('%s://%s%s%s', $p['scheme'],
				$p['host'], $p['port'] ? ':' . $p['port'] : '',
				$url);
		}
	}
	#relative to cur path
	else if (stripos($url, 'http') !== 0)  {
		$base = substr($cur, 0, strrpos($cur, '/')+1);
		$url = $base . $url;
	}
	#already abs url
	return $url;
}

/**
 * Shortcut for array($this, $fn);
 * @param string Method to call
 * @returns callback Returns array($this, $fn)
 * @note must be called from within an object method
 */
function cb($fn) {
	$tb = debug_backtrace();
	#if we're called from the toplevel there is no prev scope
	if (count($tb) < 2)
		throw new Exception('cb() must be called from within an object');
	$tb = $tb[1];
	if (!($obj = array_get($tb, 'object')))
		throw new Exception('cb() must be called from within an object');
	return array($obj, $fn);
}

?>
