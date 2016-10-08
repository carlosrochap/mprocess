<?php
/**
 * @author Kevin Tardif <kevin@vstadi.com>
 * @version 2009-02-09
 * @package Curl
 */
class CurlException extends Exception {}
class ConnectionTimeoutException extends CurlException {}

/*
 TODO

	- __get for curl_getinfo fields
	- fix getCookies
	- look into improving find* functions
*/

/* Utility class for GET/POST'ing data */
class Curl {
	private $parse_header = false;
	var $data = null; //any userdata you want to associate with this object
	var $retries = 2;
	var $proxy_info = array(
		'user' => null,
		'passwd' => null,
		'host' => null
	);

	### private variables ###

	static $__string_to_option = array(
		/* format: 'key' => ('type_predicate', CURLOPT_*) */
		'auto_referer' => array('is_bool', CURLOPT_AUTOREFERER), //auto-sets Referer: when following a Location: header
		'binary_transfer' => array('is_bool', CURLOPT_BINARYTRANSFER),
		'follow_location' => array('is_bool', CURLOPT_FOLLOWLOCATION), //follow Location:'s
		'cookie_session' => array('is_bool', CURLOPT_COOKIESESSION), //ignore all loaded cookies
		'no_body' => array('is_bool', CURLOPT_NOBODY), //exclude body from output
		'verify_peer_cert' => array('is_bool', CURLOPT_SSL_VERIFYPEER), //verify peer's ssl cert
		'post_request' => array('is_bool', CURLOPT_POST), //do a post request
		'get_request' => array('is_bool', CURLOPT_HTTPGET), //do a post request
		'max_redirects' => array('is_int', CURLOPT_MAXREDIRS), //maximum number of redirects
		'proxy_type' => array('is_int', CURLOPT_PROXYTYPE), //type of proxy
		'proxy_auth_type' => array('is_int', CURLOPT_PROXYAUTH), //type of proxy auth
		'timeout' => array('is_int', CURLOPT_TIMEOUT), //curl_exec timeout
		'connect_timeout' => array('is_int', CURLOPT_CONNECTTIMEOUT),
		'http_version' => array('is_int', CURLOPT_HTTP_VERSION), //CURL_HTTP_VERSION_NONE, CURL_HTTP_VERSION_1_0, CURL_HTTP_VERSION_1_1
		'port' => array('is_int', CURLOPT_PORT),
		'postfields' => array('is_string', CURLOPT_POSTFIELDS),
		'set_cookie' => array('is_string', CURLOPT_COOKIE), //set Set-Cookie field
		'cookie_jar' => array('is_string', CURLOPT_COOKIEJAR), //filename to save cookie on session end
		'cookie_file' => array('is_string', CURLOPT_COOKIEFILE), //filename to store cookies during session
		'proxy' => array('is_string', CURLOPT_PROXY), //proxy host
		'proxy_creds' => array('is_string', CURLOPT_PROXYUSERPWD), //proxy username:password
		'referer' => array('is_string', CURLOPT_REFERER), //Referer: field
		'user_agent' => array('is_string', CURLOPT_USERAGENT), //User-Agent: field
		'encoding' => array('is_string', CURLOPT_ENCODING), //Accept-Encoding: field
		'header' => array('is_array', CURLOPT_HTTPHEADER), //Array of header fields to append to header
		/* don't set these manually, it'll interfer with Curl's internals */
		#'post' => array('is_bool', CURLOPT_POST),
		'return_header' => array('is_bool', CURLOPT_HEADER), //return header in output
		'return_transfer' => array('is_bool', CURLOPT_RETURNTRANSFER), //return output via curl_exec()
		'url' => array('is_string', CURLOPT_URL) //target url
	);
	static $string_to_getinfo = array(
		'last_url' => CURLINFO_EFFECTIVE_URL,
		'request_sent' => CURLINFO_HEADER_OUT,
		'http_code' => CURLINFO_HTTP_CODE
	);

	var $__curl_res = null;

	### public variables ###

	var $post_method = 'application/x-www-form-urlencoded'; //set to either multipart/form-data or application/x-www-form-urlencoded

	/*
	 result_header = [
	 { 'header_field' => val, ... }
	 { 'header_field' => val, ... }
	 ...
	 ]
	 Fields may also be an array (for example, Set-Cookie may have been seen multiple times)
	*/
	var $result_header = null; //set after successful get/post
	var $total_headers = 0;

	### public methods ###

	/*
	 desc: Initializes the object for use and sets some options to sensible values
	 params:
		string $user_agent : User agent to spoof
	 returns: none
	 throws: none
	*/
	function __construct($user_agent='Mozilla/5.0 (X11; ; Linux i686; en-US; rv:1.8.1.19) Gecko/20081216 Firefox/2.0.0.19') {
		$this->__curl_res = curl_init();
		$this->auto_referer = True;
		$this->return_transfer = True;
		$this->follow_location = True;
		$this->user_agent = $user_agent;
		$this->timeout = 35;
		$this->connect_timeout = 35;
		$this->verify_peer_cert = false;
		$this->parseHeader(false);
		#for ->request_sent
        curl_setopt_array($this->__curl_res, array(
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FRESH_CONNECT  => true,
            CURLINFO_HEADER_OUT    => true
        ));
		#$this->encoding = 'gzip,deflate';
	}

	/*
	 desc: Closes the current session
	 params: none
	 returns: none
	 throws: none
	*/
	function __destruct() {
		if ($this->__curl_res)
			curl_close($this->__curl_res);
	}

	function close() {
		if ($this->__curl_res)
			curl_close($this->__curl_res);
		$this->__curl_res = null;
	}

	/*
	 desc: Sets the proxy to use
	 params:
		string $host : Proxy hostname
		string $user : Username, or null for none
		string $passwd : Password, or null for none
		int $type : CURLPROXY_HTTP or CURLPROXY_SOCKS5
		int $auth_type : CURLAUTH_BASIC or CURLAUTH_NTLM
	 returns: none
	 throws: none
	*/
	function setProxy($host, $user=null, $passwd=null, $type=CURLPROXY_HTTP, $auth_type=CURLAUTH_BASIC) {
		if ($user && $passwd)
			$this->proxy_creds = $user . ':' . $passwd;
		$this->proxy = $host;
		$this->proxy_type = $type;
		$this->proxy_auth_type = $auth_type;
		/* store proxy info */
		$this->proxy_info['host'] = $host;
		$this->proxy_info['user'] = $user;
		$this->proxy_info['passwd'] = $passwd;
	}

	/*
	 desc: Unsets current proxy
	 params: none
	 returns: Old proxy settings
	 throws: none
	*/
	function unsetProxy() {
		$r = $this->proxy_info;
		$this->proxy_info = array(
			'user' => '',
			'passwd' => '',
			'host' => ''
		);
		$this->setProxy('', '', '');
		return $r;
	}

	/*
	 desc: Returns error message from last transaction
	 params: none
	 returns: Error message string
	 throws: none
	*/
	function error() {
		return curl_error($this->__curl_res);
	}

	/*
	 desc: Returns error code from last transaction
	 params: none
	 returns: Error code
	 throws: none
	*/
	function errno() {
		return curl_errno($this->__curl_res);
	}

	/*
	 desc: Retrieve a page
	 params:
		string $url : URL to fetch
		string/array $fields : If non-null, builds as a query to append to the URL
	 returns: The page
	 throws: CurlException
	*/
	function get($url, $fields=null) {
		if (!$fields)
			$query = '';
		else if (is_array($fields))
			$query = '?' . self::postifyFields($fields);
		else
			$query = '?' . $fields;

		curl_setopt($this->__curl_res, CURLOPT_HTTPGET, True);
		$this->url = $url . $query;

		for($i=0; $i < $this->retries; ++$i) {
			$document = curl_exec($this->__curl_res);
			if ($document !== false) break;
		}

		if ($document === false)
			throw $this->__getProperException('Curl::get: Unable to GET ' . $url . ': ' . $this->error(), $this->errno());

		/* grab header */
		if ($this->parse_header) {
			$header_size = curl_getinfo($this->__curl_res, CURLINFO_HEADER_SIZE);
			$this->result_header = self::getHeader($document, $header_size);
			$document = substr($document, $header_size);
			$this->total_headers = sizeof($this->result_header);
		}

		return $document;
	}

	function parseHeader($setting) {
		$this->return_header = $setting;
		$this->parse_header = $setting;
	}


	/*
	 desc: Retrieve all form elements from a page
	 params:
		string $url : URL to use
		string/array $fields : If non-null, builds as a query to append to the URL
	 returns: Array of form elements or null if none
	 throws: CurlException
	*/
	function getForms($url, $fields=null) {
		$document = $this->get($url, $fields);
		return self::findFormElements($document);
	}

	/*
	 desc: Retrieve all links from a page
	 params:
		string $url : URL to use
		string/array $fields : If non-null, builds as a query to append to the URL
	 returns: Array of link elements of form (text, url), or null if none
	 throws: CurlException
	*/
	/* TODO:
	  deal with relative links better, handle parsing text out of tags inside <a>
	*/
	function getLinks($url, $fields=null) {
		$document = $this->get($url, $fields);
		return self::findLinks($document, $url);
	}

	/*
	 desc: Return hidden form values as a hash
	 params:
		string $document : Document to parse
		string $page_url : Originating page URL to resolve relative links
	 returns: Array of form { name => value }
	 throws: CurlException
	*/
	function getHiddenFormValues($url, $fields=null) {
		$document = $this->get($url, $fields);
		return self::findHiddenFormValues($document);
	}

	/*
	 desc: Post a form to a URL
	 params:
		string $url : URL
		array $vars : POST values
		array $files : List of files to upload
	 returns: Page returned from POST
	 throws: CurlException
	*/
	function post($url, $vars=null, $files=null) {
		if ($files) #force multipart if we need to send files
			$this->post_method = 'multipart/form-data';
		$postfields = $this->__getPostFields($vars);

		/* only do this for multipart/form-data */
		if ($files && is_array($postfields))
			foreach ($files as $form_var => $filepath)
				$postfields[$form_var] = '@' . $filepath;

		$this->url = $url;
		curl_setopt($this->__curl_res, CURLOPT_POST, True);
		curl_setopt($this->__curl_res, CURLOPT_POSTFIELDS, $postfields);

		for($i=0; $i < $this->retries; ++$i) {
			$document = curl_exec($this->__curl_res);
			if ($document !== false) break;
		}

		if ($document === false)
			throw $this->__getProperException('Curl::post: POST unsuccessful: ' . $this->error(), $this->errno());

		/* grab header */
		if ($this->parse_header) {
			$header_size = curl_getinfo($this->__curl_res, CURLINFO_HEADER_SIZE);
			$this->result_header = self::getHeader($document, $header_size);
			$this->total_headers = sizeof($this->result_header);
			$document = substr($document, $header_size);
		}

		$this->post_method = 'application/x-www-form-urlencoded'; #always reset post method
		return $document;
	}

	/*
	 desc: Set curl options
	 params:
		string $what : Option to set (see $__string_to_option above)
		mixed $value : Option value
	 returns: none
	 throws: CurlException
	*/
	function __set($what, $value) {
		if (!array_key_exists($what, self::$__string_to_option))
			throw new CurlException('Curl::__set: Invalid option: ' . $what);

		list($typechecker, $opt) = self::$__string_to_option[$what];

		if (!$typechecker($value))
			throw new CurlException('Curl::__set: Invalid argument type for option ' . $what);
		curl_setopt($this->__curl_res, $opt, $value);
	}

	function __get($what) {
		if (!array_key_exists($what, self::$string_to_getinfo))
			throw new CurlException('Curl::__get: Invalid info: ' . $what);
		return curl_getinfo($this->__curl_res, self::$string_to_getinfo[$what]);
	}

	function setOptions($opts) {
		foreach ($opts as $o) {
			list($k, $v) = $o;
			$this->$k = $v;
		}
	}

	/**
	 * Returns intval(res) (useful for hashing based on this object)
	 */
	function resval() {
		return intval($this->__curl_res);
	}

	/*
	 FIXME
	 desc: Returns cookies set by last header
	 params: none
	 returns: Cookie array
	 throws: none
	*/
	function getCookies() {
		$cookies = array();
		$cur = 0;
		foreach ($this->result_header[$this->total_headers-1]['Set-Cookie'] as $c) {
			array_push($cookies, array());
			list($k, $v) = explode('=', $c, 2);
			foreach (preg_split('@;[\s]*@', $c) as $k => $v)
				$cookies[$cur][$k] = $v;
			++$cur;
		}
		return $cookies;
	}

	### private methods ###

	/*
	 desc: Returns the proper exception depending on the value of $this->errno()
	 params:
		string $message : Exception error message
		int $code : Exception error code
	 returns: doesn't return
	 throws: *varies*
	*/
	function __getProperException($message, $code=0) {
		switch ($this->errno()) {
			case CURLE_OPERATION_TIMEOUTED:
				return new ConnectionTimeoutException($message, $code);
				break;
		}
		return new CurlException($message, $code);
	}

	/*
	 desc: Returns post fields to pass to libcurl corresponding to post_method
	 params:
		string/array $fields : Fields to process
	 returns: Either an array or string to pass to libcurl
	 throws: CurlException
	*/
	function __getPostfields($fields) {
		/* libcurl wants a string (values must be url-encoded) */
		if ($this->post_method == 'application/x-www-form-urlencoded') {
			if (is_string($fields))
				return $fields;
			return self::postifyFields($fields);
		}
		/* libcurl wants an array */
		else if ($this->post_method == 'multipart/form-data') {
			if (is_array($fields)) //no change
				return $fields;
			$all = explode('&', $fields);
			$result = array();
			foreach($all as $f) {
				$r = explode('=', $f, 2);
				if (sizeof($r) != 2)
					continue;
				list($k, $v) = $r;
				$result[$k] = $v;
			}
			return $result;
		}
		else
			throw new CurlException('Curl::__getPostFields: Invalid post_method: '
									. $this->post_method . '; valid values are application/x-www-form-urlencoded or multipart/form-data');
	}

	/**
	 * Returns retrieved document from request made with a CurlMulti
	 */
	function getContent() { return curl_multi_getcontent($this->__curl_res); }

	### static methods ###

	/*
	 desc: Return a valid forms string (field=blah&field2=blah)
	 params:
		array $fields : Fields to postify; if a value for a field is an array, it will be appended multiple times (good for checkboxes)
	 returns: String containing the postified fields
	 throws: none
	*/
	static function postifyFields($fields, $urlencode_values=true) {
		if (!$fields)
			return '';
		$data = '';
		foreach ($fields as $key => $val) {
			if (is_array($val)) { //for checkboxes
				foreach ($val as $part)
					$data .= sprintf('%s=%s&', urlencode($key), $urlencode_values ? urlencode($part) : $part);
			}
			else
				$data .= sprintf('%s=%s&', urlencode($key), $urlencode_values ? urlencode($val) : $val);
		}
		return rtrim($data, '&');
	}

	/*
	 desc: Retrieve all form elements from a document
	 params:
		string $document : Document to parse
	 returns: Array of form elements or null if none
	 throws: none
	 limitations: Only saves one checked checked box
	*/
	static function findFormElements($document) {
		#if (!preg_match_all('@(?P<tag><form (?:.+?)>)(?P<formdata>.*?)</form>@si', $document, $matches, PREG_SET_ORDER))
		#thanks nate!
		if (!preg_match_all('@(?P<tag><form\s+[^>]+>)(?P<formdata>(?:[^<]*<(?!/form)[^>]+>)*)@si', $document, $matches, PREG_SET_ORDER))
			return array();
		$forms = array();
		$cur_form = 0;
		foreach ($matches as $m) {
			/*if (!preg_match_all('@(?P<tag><(select|input|textarea) .+?(:?/)?>)(?:(?P<value>.*?)</\2>)?@si', $m['formdata'], $tagmatches, PREG_SET_ORDER))*/
			if (!preg_match_all('@(?P<tag><(select|input|textarea) [^>]+(:?/)?>)(?:(?P<value>.*?)</\2>)?@si', $m['formdata'], $tagmatches, PREG_SET_ORDER))
				continue;
			$tags = array(self::breakTag($m['tag']));
			foreach ($tagmatches as $t) {
				if (!($tag = self::breakTag($t['tag']))) {
					//print "offender: \n\n";
					//print_r($t);
					continue;
				}
				if (!array_key_exists('name', $tag))
					continue;

				if (!empty($t['value']))
					$tag['attrs']['value'] = $t['value'];

				/* special cases */
				if (array_key_exists('name', $tag)
					&& $tag['name'] == 'select') {
					if (!array_key_exists('value', $tag['attrs']))
						$tag['attrs']['value'] = '';
					if (preg_match('@(<option[^<option]*?selected.*?>)@si', $tag['attrs']['value'], $match)) {
						$opt = $match[1];
						if (preg_match('@<option.*?value="?(.+?)"?\s*?selected.*?>@si', $opt, $match)
							|| preg_match('@<option\s*selected.*?value="?(.+?)"?\s*.*?>@', $opt, $match))
								$tag['attrs']['value'] = $match[1];
						else
							$tag['attrs']['value'] = '';
					}
					else
						$tag['attrs']['value'] = '';
				}

				/* if not checked, don't add (we don't send them to the server */
				else if (array_key_exists('type', $tag['attrs'])
					&& $tag['attrs']['type'] == 'checkbox') {
						if (preg_match('@\s*checked\s*(?:=\s*["\']checked["\'])?@si', $t['tag'])) {
							#if no value attr is set, default to 'on'
							if (!array_key_exists('value', $tag['attrs']))
								$tag['attrs']['value'] = 'on';
						}
						#not checked, ignore
						else
							continue;
				}

				array_push($tags, $tag);
			}
			array_push($forms, $tags);
		}
		return $forms;
	}

	/*
	 desc: Takes an element tag (ie: <form action="..." ...> and return it as a hash
	 params:
		string $tag : Tag string to break
	 returns: Hash of form { 'name' => tag_name, 'attrs' => array('attr1' => val, ...) }
	 throws: None
	*/
	static function breakTag($tag) {
		if (!preg_match('@<(?P<tag_name>.+?)(?: (?P<attrs>.+?))??\s*/?>@s', $tag, $match))
			return null;
		$tag_info = array('name' => strtolower($match['tag_name']), 'attrs' => array());
		preg_match_all('@\s*(?P<k>.+?)=(?P<v>(?:("|\').*?\3)|(?:.*?(?:\s|$)))@s', $match['attrs'], $attrs, PREG_SET_ORDER);
		foreach ($attrs as $a) {
			$k = strtolower(trim($a['k']));
			$v = rtrim(trim($a['v'], "\"'"));
			$tag_info['attrs'][$k] = $v;
		}
		return $tag_info;
	}

	/*
	 desc: Returns an array of form values, 1 per form; excludes type="submit" items
	 params:
		string $document : Document to parse
	 returns: Array of forms
	 throws: none
	 limitation: With checks, only returns the last checked value
	*/
	static function findFormValues($document) {
		$forms = self::findFormElements($document);
		$cur = 0;
		$vals = array();
		foreach ($forms as &$f) {
			foreach ($f as &$tag) {
				$attrs = &$tag['attrs'];
				if (!is_array($attrs) || !array_key_exists('name', $attrs)) continue;
				if (array_key_exists('type', $attrs) && $attrs['type'] == 'submit') continue;
				$vals[$cur][$attrs['name']] = array_key_exists('value', $attrs) ? $attrs['value'] : '';
			}
			++$cur;
		}
		return $vals;
	}

	/*
	 desc: Returns an array containing hidden values for forms in document
	 params:
		string $document : Document to parse
	 returns: Array of form [ [ name : value, ... ], ... ]
	 throws: None
	 limitation: With checks, only returns the last checked value
	*/
	static function findHiddenFormValues($document) {
		$forms = self::findFormElements($document);
		$cur = 0;
		$vals = array();
		/* only keep hidden elements */
		foreach ($forms as &$f) {
			array_push($vals, array());
			foreach ($f as &$tag) {
				$attrs = &$tag['attrs'];
				if (array_key_exists('type', $attrs)
					&& $attrs['type'] == 'hidden'
					&& array_key_exists('name', $attrs)
					&& array_key_exists('value', $attrs))
					$vals[$cur][$attrs['name']] = $attrs['value'];
			}
			++$cur;
		}
		return $vals;
	}

	/*
	 desc: Given a document, finds links, optionally filtering out unwanted matches via filter_fn($link)
	 params:
		string $document : Document to parse
		string $page_url : Originating page URL to resolve relative links
		function $filter_fn : Function of form fn($associated text, $url_link) returning False to discard link
	 returns: Array of links matching $filter_fn
	 throws: none
	*/
	static function findLinks($document, $page_url, $filter_fn=null) {
		if (!preg_match_all('/<a.*href="(?P<url>.+?)".*?>(?P<text>.+?)<\/a>/', $document, $matches, PREG_SET_ORDER))
			return null;
		$links = array();
		foreach ($matches as $m) {
			//fix relative links
			$link = $m['url'];
			if ($link[0] == '/') { //convert root link
				$parts = parse_url($page_url);
				$host = $parts['host'];
				$scheme = $parts['scheme'];
				$link = sprintf("%s://%s%s", $scheme, $host, $link);
			}
			else if ($link[0] == '?') { //convert query link
				;
			}
			/* apply filter if desired */
			if ($filter_fn) {
				if ($filter_fn($m['text'], $link))
					array_push($links, array($m['text'], $link));
			}
			else
				array_push($links, array($m['text'], $link));
		}
		return $links;
	}

	/*
	 desc: Returns the header fields from the document
	 params:
		string $document : Document with prepended header
		int $header_size : Size of returned header
	 returns: array of headers (may be multiple headers due to redirects)
	 throws: none
	*/
	static function getHeader($document, $header_size) {
		$headers = explode("\r\n", substr($document, 0, $header_size - 4));
		$fields = array();
		$cur_header = -1;
		foreach ($headers as $h) {
			if (preg_match('@^HTTP/1\.@', $h)) { //start of new header
				++$cur_header;
				$fields[$cur_header] = array();
				continue;
			}
			if ($cur_header == -1)
				continue;
			$r = explode(': ', $h, 2);
			if (sizeof($r) != 2)
				continue;
			list($k, $v) = $r;

			/* handle multiple of the same header field */
			if (array_key_exists($k, $fields[$cur_header])) {
				if (!is_array($fields[$cur_header][$k])) {
					$tmp = $fields[$cur_header][$k];
					$fields[$cur_header][$k] = array();
					array_push($fields[$cur_header][$k], $tmp);
				}
				array_push($fields[$cur_header][$k], $v);
			}
			else
				$fields[$cur_header][$k] = $v;
		}
		return $fields;
	}
}

?>
