<?php
#2009-03-06
require_once 'common-util.inc.php';
#TODO write test cases for form parsing

function formutil_unwrap_fields(&$dict) {
	$r = array();
	foreach ($dict as $k => $v) {
		if (is_array($v)) {
			foreach ($v as $sv)
				$r[] = array($k, $sv);
		}
		else
			$r[] = array($k, $v);
	}
	return $r;
}

function formutil_input_get_value($node, $attrs) {
	#if not type attr, default is text
	if (($type = $attrs->getNamedItem('type')) === null)
		$type = 'text';
	else
		$type = strtolower($type->nodeValue);

	switch ($type) {
		case 'hidden':
		case 'password':
		case 'text':
			if (($v = $attrs->getNamedItem('value')) === null)
				$v = '';
			else
				$v = $v->nodeValue;
			return $v;
			break;

		case 'radio':
			$checked = $attrs->getNamedItem('checked');
			if ($checked === null)
				return null;
			$v = $attrs->getNamedItem('value');
			if ($v)
				return $v->nodeValue;
			break;

		case 'checkbox':
			$checked = $attrs->getNamedItem('checked');
			if ($checked === null)
				return null;
			$v = $attrs->getNamedItem('value');
			if ($v === null)
				$v = 'on';
			else
				$v = $v->nodeValue;
			return $v;

		case 'button':
		case 'image':
		case 'submit':
		case 'reset':
			return null;
			break;

		#should never get here
		case 'file':
			trigger_error("Shouldn't be called with a file type input", E_USER_ERROR);
			break;

		default:
			print "$type not handled\n";
			return null;
	}
}

function formutil_add_value(&$arr, $k, $v) {
	if (array_key_exists($k, $arr)) {
		if (!is_array($arr[$k]))
			$arr[$k] = array($arr[$k]);
		$arr[$k][] = $v;
	}
	else
		$arr[$k] = $v;
}

function formutil_get_file_fields($top_node, $xp) {
	$files = array();
	$lst = $xp->query('descendant::input[@name][@type = "file"]', $top_node);
	$l = $lst->length;
	for ($i=0; $i < $l; ++$i) {
		$node = $lst->item($i);
		$attrs = $node->attributes;
		#no name
		$name = $attrs->getNamedItem('name')->nodeValue;
		$files[$name] = '';
	}
	return $files;
}

function formutil_get_input_fields($top_node, $xp) {
	$inputs = array();
	#we also want to get inputs without a type attr
	$lst = $xp->query('descendant::input[@name][not(@type) or @type != "file"]', $top_node);
	$l = $lst->length;
	for ($i=0; $i < $l; ++$i) {
		$node = $lst->item($i);
		$attrs = $node->attributes;
		#no name
		$name = $attrs->getNamedItem('name')->nodeValue;
		$value = formutil_input_get_value($node, $attrs);
		if ($value === null)
			continue;
		formutil_add_value($inputs, $name, $value);
	}
	return $inputs;
}

function formutil_get_selected_opt($node, $xp) {
	$multiple_opts = $node->attributes->getNamedItem('multiple') !== null;

	$opts = array();
	$lst = $xp->query('descendant::option[@selected]', $node);
	$l = $lst->length;

	for ($i=0; $i < $l; ++$i) {
		$opt = $lst->item($i);

		#if not value attrib, use text field
		if (!($n = $opt->attributes->getNamedItem('value')))
			$v = $opt->textContent;
		else
			$v = $n->nodeValue;

		$opts[] = $v;
		if (!$multiple_opts)
			break;
	}

	switch (count($opts)) {
		case 0:
			return '';
		case 1:
			return $opts[0];
			break;
		default:
			return $opts;
	}
}

function formutil_get_select_fields($top_node, $xp) {
	$selects = array();
	$lst = $xp->query('descendant::select[@name]', $top_node);
	$l = $lst->length;
	for ($i=0; $i < $l; ++$i) {
		$node = $lst->item($i);
		$attrs = $node->attributes;
		$name = $attrs->getNamedItem('name')->nodeValue;

		$selected = formutil_get_selected_opt($node, $xp);
		$selects[$name] = $selected;
	}
	return $selects;
}

function formutil_get_textarea_fields($top_node, $xp) {
	$tas = array();
	$lst = $xp->query('descendant::textarea[@name]', $top_node);
	$l = $lst->length;

	for ($i=0; $i < $l; ++$i) {
		$node = $lst->item($i);
		$attrs = $node->attributes;
		$name = $attrs->getNamedItem('name')->nodeValue;
		$value = $node->textContent;
		$tas[$name] = $value;
	}
	return $tas;
}

class FormEnctype {
	const URLENCODED = 'application/x-www-form-urlencoded';
	const MULTIPART = 'multipart/form-data';
	const PLAIN = 'text/plain';

	static function is_valid($t) {
		return array_search($t, array(self::URLENCODED, self::MULTIPART, self::PLAIN)) !== false;
	}
}

class HTMLForm implements ArrayAccess {
	#TODO can also be text/plain
	public $enctype = FormEnctype::URLENCODED;
	public $name = '';
	public $id = '';
	public $action = '';
	public $method = 'get';
	public $fields = array();
	public $files = array();

	/**
	 * @param DOMNodeList List of <form> attributes
	 * @param string URL of the form
	 * @param dict Form field elements
	 * @param dict Form file inputs
	 */
	function __construct($attrs, $url, $fields, $files) {
		foreach (array('name', 'action', 'method', 'enctype', 'id') as $f) {
			if (($v = $attrs->getNamedItem($f)) === null)
				continue;
			$this->$f = $v->nodeValue;
		}

		$this->method = strtolower($this->method);
		$this->fields = $fields;
		$this->files = $files;

		$this->enctype = strtolower($this->enctype);
		if (!FormEnctype::is_valid($this->enctype))
			trigger_error("Invalid form enctype attribute: {$this->enctype}", E_USER_ERROR);

		if ($url)
			$this->setLocation($url);
	}

	/**
	 * Finds all forms in the given document
	 * @param string The document to parse
	 * @param string The url of the document; used to handle relative/absolute
	 * form action attribute
	 * @param dict If defined, returns the first form matching the given constraints, or null
	 * if not found. Allowed constraints: name => '', id => '', fields => array(), fields => ''
	 * @return list List of forms found
	 */
	static function find($doc, $url, array $q_params=null) {
		$dom = new DOMDocument;
		#throws warnings on malformed html
		if (!@$dom->loadHTML($doc))
			return false;

		$xp = new DOMXPath($dom);
		$all_forms = array();
		foreach ($dom->getElementsByTagName('form') as $top_node) {
			$fields = array();
			$fields = array_merge($fields, formutil_get_input_fields($top_node, $xp));
			$fields = array_merge($fields, formutil_get_select_fields($top_node, $xp));
			$fields = array_merge($fields, formutil_get_textarea_fields($top_node, $xp));
			$files = formutil_get_file_fields($top_node, $xp);
			$form = new HTMLForm(
				$top_node->attributes,
				$url,
				$fields,
				$files);
			$all_forms[] = $form;
		}
		#requesting a specified form; try and find it
		if ($q_params)
			return HTMLForm::searchForms($all_forms, $q_params);

		return $all_forms;
	}

	/**
	 * Helper fn to search forms list (called by find)
	 * @param list List of forms
	 * @param
	 * @note we implement this by filtering the forms for each param
	 */
	static function searchForms($forms, array $q_params) {
		foreach ($q_params as $k => &$v) {
			$fs = array();
			switch ($k) {
			case 'id':
			case 'name':
				$fs = array();
				foreach ($forms as $f) {
					if ($f->$k == $v)
						$fs[] = $f;
				}
				break;
			case 'fields':

				if (!is_array($v))
					$fields = array(&$v);
				else
					$fields = &$v;

				foreach ($forms as $f) {
					$ok = true;
					foreach ($fields as $field) {
						if (!$f->hasField($field)) {
							$ok = false;
							break;
						}
					}
					if ($ok)
						$fs[] = $f;
				}
				break;
			default:
				trigger_error("Invalid query param: $k=$v", E_USER_ERROR);
				return null;
				break;
			}
			$forms = $fs;
		}
		return array_shift($forms);
	}

	/**
	 * Predicate for checking if the form has any file fields
	 * @returns bool True if form has any file inputs, false otherwise
	 */
	function hasFiles() {
		return count($this->files) > 0;
	}

	/**
	 * Returns a multipart encoded version of the form fields
	 * @returns (string, string) Returns (boundary, multipart-data)
	 */
	function asMultipart() {
		$params = formutil_unwrap_fields($this->fields);
		$files = formutil_unwrap_fields($this->files);
		return build_multipart($params, $files);
	}

	/**
	 * Returns urlencoded form of form fields
	 * @returns string Urlencoded form data
	 * @note Will trigger a fatal error if this is called on a form with file input
	 * fields
	 */
	function asUrlEncoded() {
		#TODO maybe remove this?
		if ($this->hasFiles())
			trigger_error("Form has a file field; cannot submit in url-encoded format", E_USER_ERROR);

		$postfields = array();
		foreach ($this->fields as $k => &$v) {
			if (is_array($v)) {
				foreach ($v as &$sv)
					$postfields[] = urlencode($k) . '=' . urlencode($sv);
			}
			else
				$postfields[] = urlencode($k) . '=' . urlencode($v);
		}
		return implode('&', $postfields);
	}

	### file upload stuff ###
	/**
	 * Adds an upload to a form file input
	 * @param string Field name
	 * @param string Filesystem path for file
	 * @return bool True if path exists, false otherwise
	 */
	function addFile($k, $path) {
		if (is_array($path)) {
			foreach ($path as $f)
				if ($f && !file_exists($f))
					return false;
		}
		else {
			if ($path && !file_exists($path))
				return false;
		}
		$this->files[$k] = $path;
		return true;
	}

	/**
	 * Removes an upload from the given field
	 * @param string File input name
	 */
	function removeFile($k) {
		unset($this->files[$k]);
	}

	/**
	 * Checks if form has a given non-file input
	 * @param str Field name
	 * @return bool True if field exists
	 */
	function hasField($k) {
		return array_key_exists($k, $this->fields);
	}

	/**
	 * Checks if form has a given file input
	 * @param str Field name
	 * @return bool True if field exists
	 */
	function hasFileField($k) {
		return array_key_exists($k, $this->files);
	}

	### ArrayAccess impl stuff for easy field access ###
	function offsetExists($k) {
		return array_key_exists($k, $this->fields);
	}

	function offsetSet($k, $v) {
		$this->fields[$k] = $v;
		return $this;
	}

	function offsetGet($k) {
		return $this->fields[$k];
	}

	function offsetUnset($k) {
		unset($this->fields[$k]);
	}

	### misc util ###
	#pass url of page where found is found to handle
	#relative urls and empty value in action attr
	function setLocation($form_url) {
		#if there is no scheme, parse_url will return
		#host as path
		if (stripos($form_url, 'http') !== 0)
			$form_url = 'http://' . $form_url;

		$url = $this->action;
		if (!$this->action)
			$url = $form_url;
		else if (stripos($url, 'http') === 0);
		#relative-to-root url
		else if ($url[0] == '/') {
			$p = parse_url($form_url);

			#some defaults
			if (!array_key_exists('scheme', $p))
				$p['scheme'] = 'http';

			if (!array_key_exists('port', $p))
				$p['port'] = null;

			$url = sprintf('%s://%s%s%s', $p['scheme'],
				$p['host'], $p['port'] ? ':' . $p['port'] : '',
				$url);
		}
		#relative-cur-subdir url
		else {
			$base = substr($form_url, 0, strrpos($form_url, '/')+1);
			$url = $base . $url;
		}
		$this->action = $url;
	}

	/**
	 * Submit this form using the proper enctype and to the proper url
	 * @param Curl Curl object to submit with (this just needs support post($url, $data)
	 * get($url) methods, and a header attrib setter)
	 * @param list optional kwargs:
	 * headers: list of extra http headers to be used for this request
	 * @return string Returned document from form submit
	 * @note Can throw any valid Curl Exception; plaintext enctype not supposed
	 * @note Will error if form has incorrect values set (ie: enctype=multipart and method=get)
	 */
	function submit($c, $kwargs=array()) {
		if ($this->enctype == FormEnctype::PLAIN)
			trigger_error("text/plain enctype currently unsupported", E_USER_ERROR);

		if ($this->enctype == FormEnctype::MULTIPART && $this->method == 'get')
			trigger_error("GET method cannot be used with multipart/form-data encoding", E_USER_ERROR);

		$url = $this->action;
		$extra_headers = array_get($kwargs, 'headers', array());

		switch ($this->method) {
			case 'get':

				$params = $this->asUrlEncoded();
				if ($url[strlen($url)-1] != '?')
					$url .= '?';
				$url .= $params;
				$c->header = $extra_headers;
				return $c->get($url);
				break;

			case 'post':

				#TODO can't use with multicurl
				if ($this->enctype == FormEnctype::MULTIPART) {
					list($boundary, $mp) = $this->asMultipart();
					try {
						$c->header = array_merge($extra_headers, array(
							'Content-Type: multipart/form-data; boundary=' . $boundary
						));
						$doc = $c->post($url, $mp);
						$c->header = array();
					}
					catch (Exception $e) {
						$c->header = array();
						throw $e;
					}
					return $doc;
				}

				else if ($this->enctype == FormEnctype::URLENCODED) {
					$params = $this->asUrlEncoded();
					$c->header = $extra_headers;
					return $c->post($url, $params);
				}

				break;

			#should never get here
			default:
				trigger_error("Invalid method: {$this->method}", E_USER_ERROR);
				break;
		}
	}
}

/*
	file - store seperately

	checkbox - done
	radio - done (only one radio with the same name can be checked at once)
	hidden - done
	text - done
	password - done (treat like text)

	button - ignore; not used when sending post data
	reset - just clears all form fields; ignore it
	submit - done
	image - like submit button, but sends .x/.y click coordinates (ignore)
 */
#define('HF_TEST', 1);
if (defined('HF_TEST')) {/*{{{*/
	require_once 'Curl.inc.php';
	$doc =<<<EOD
	<html>
		<head><title>t</title></head>
	<body>
	</body>
	<form name="testform" method="post" action="http://someurl">
		<input type="hidden" value="not valid" />

		<input type="hidden" name="hid" value="hidden" />
		<input type="checkbox" name="cb" checked="checked" />
		<input type="checkbox" name="cb2" checked="checked" value="cbv" />
		<input type="checkbox" name="cb3" />

		<input type="checkbox" name="mcb" value="1" checked="checked" />
		<input type="checkbox" name="mcb" value="2" checked="checked" />
		<input type="checkbox" name="mcb" value="3" />
		<input type="checkbox" name="mcb" value="4" checked="checked" />

		<textarea name="ta">
			fdsfdsfsd
		</textarea>

		<select name="ops">
			<option value="nyan">nyan</option>
			<option value="nyau" selected="selected">nyau</option>
		</select>

		<select name="ops-multi" multiple="multiple">
			<option value="nyan2" selected="selected">nyan</option>
			<option value="nyau2" selected="selected">nyau</option>
			<option value="nyannyau">nyan nyau</option>
		</select>

		<select name="ops-no-val">
			<option>1</option>
			<option selected="selected">2</option>
		</select>

		<input type="radio" name="rd" value="1" />
		<input type="radio" name="rd" value="2" checked="checked" />

		<input type="submit" name="ok" value="Submit" />
		<input type="submit" name="cancel" value="Cancel" />
	</form>

	<form id="testform2">
		<input type="text" name="t" value="some text" />
		<input type="file" name="upload" />
	</form>
	</html>
EOD;
	print "running parse error test\n";
	if (HTMLForm::find('', '') !== false) {
		print "failed\n";
		exit(-1);
	}

	print "running form lookup test\n";
	$forms = HTMLForm::find($doc, '');
	$n_forms = len($forms);
	if ($n_forms != 2) {
		print "failed; expected 2 forms, got $n_forms\n";
		exit(-1);
	}
	list($form1, $form2) = $forms;

	### form lookup test ###
	print "running find form by name test\n";
	if (!($f = HTMLForm::find($doc, '', array('name' => 'testform')))
		|| $f->name != 'testform') {
			print "find form by name failed\n";
			exit(-1);
	}

	print "running find form by id test\n";
	if (!($f = HTMLForm::find($doc, '', array('id' => 'testform2')))
		|| $f->id != 'testform2') {
			print "find form by id failed\n";
			exit(-1);
	}

	print "running find form by fields test\n";
	if (!($f = HTMLForm::find($doc, '', array('fields' => 't')))
		|| $f->id != 'testform2') {
			print "find form by fields failed\n";
			exit(-1);
	}

	### multipart test ###
	print "running multipart test\n";
	list($boundary, $mp) = $form2->asMultipart();
	$expected_mp =
		"--$boundary\r\nContent-Disposition: form-data; name=\"t\"\r\n\r\nsome text\r\n--$boundary\r\nContent-Disposition: form-data; name=\"upload\"; filename=\"\"\r\nContent-Type: application/octet-stream\r\n\r\n--$boundary--\r\n";

	if ($mp != $expected_mp) {
		print "$mp";
		print "multipart test failed\n";
		exit(-1);
	}

	### array access test ###
	print "running array access test\n";
	if (!$form1->hasField('ops')) {
		print "failed\n";
		exit(-1);
	}

	#print_r($form1);
	#print_r($form1->fields);
	#print_r($form2->fields);
	#print_r($form2->files);
	#print $form1->asUrlEncoded() . "\n";
	#$form2->rmFile('upload');
	#print $form2->asUrlEncoded() . "\n";

	#$doc = $form2->submit($c);
	#$v = Curl::findFormValues($doc);
	#$v = $v[0];
	#print_r($v);

	### google test ###
	print "running google test\n";
	$c = new Curl;
	$doc = $c->get('http://www.google.com');
	$forms = HTMLForm::find($doc, $c->last_url);
	list($srch_form) = $forms;
	$srch_form['q'] = 'test';
	$doc = $srch_form->submit($c);
	if (!preg_match('@about <b>([\d,]+)</b>@', $doc, $m)) {
		print_r($srch_form);
		print "search failed\n";
		file_put_contents('/tmp/srch.html', $doc);
		exit(-1);
	}

	$n = $m[1];
	print "$n results\n";
}/*}}}*/
?>
