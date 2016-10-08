<?php
#2009-04-27
require_once 'Curl.inc.php';
require_once 'common-util.inc.php';
require_once 'htmlform.inc.php';

function follow_redirect(Curl $c, $doc) {/*{{{*/
	if (preg_match('@location\.replace\(["\']([^\"]+)@i', $doc, $m));
	else if (preg_match('@content="[^"]*url=([^;"]+)@i', $doc, $m));
	else if (preg_match('@[^"]window\.location\s*=\s*["\']([^"\']+)@i', $doc, $m));
	else
		return array(false, $doc);

	$cur = $c->last_url;
	$url = $m[1];

	#abs url
	if (stripos($url, 'http') === 0);
	#relative-to-root url
	else if ($url[0] == '/') {
		$p = parse_url($cur);
		$url = sprintf('%s://%s%s%s', $p['scheme'],
			$p['host'], $p['port'] ? ':' . $p['port'] : '',
			$url);
	}
	#relative-cur-subdir url
	else {
		$base = substr($cur, 0, strrpos($cur, '/'));
		$url = $base . $url;
	}
	$doc = $c->get($url);
	return array(true, $doc);
}/*}}}*/

//is:sent
//in:inbox
//in:spam
//in:anywhere
//is:unread
//to:me
//from:me
class Gmail {
	const INBOX = '';
	const SPAM = 'm';
	const SENT = 's';

	static function get_message($doc) {
		if (!preg_match('@<td bgcolor=#FAD163>\n&nbsp;&nbsp;&nbsp;<b>([^<]+)@', $doc, $m))
			return null;
		return $m[1];
	}

	static function feed_to_emails($feed) {/*{{{*/
		$dom = new DOMDocument;
		$dom->loadXML($feed);
		$xp = new DOMXPath($dom);
		$xp->registerNamespace('n', 'http://purl.org/atom/ns#');
		$emails = array();
		foreach ($dom->getElementsByTagName('entry') as $node) {
			$email = array();
			$l = $xp->query('n:title', $node);
			$title = $l->item(0)->nodeValue;
			$l = $xp->query('n:summary', $node);
			$summary = $l->item(0)->nodeValue;

			$l = $xp->query('n:link', $node);
			$link = $l->item(0)->getAttribute('href');

			$l = $xp->query('n:author/n:name', $node);
			if ($l->length)
				$from = $l->item(0)->nodeValue;
			else
				$from = null;

			$l = $xp->query('n:author/n:email', $node);
			$email = $l->item(0)->nodeValue;

			if ($from)
				$from = sprintf('%s <%s>', $from, $email);
			else
				$from = $email;

			$emails[] = array(
				'title' => $title,
				'desc' => $summary,
				'link' => $link,
				'from' => $from
			);
		}
		return $emails;
	}/*}}}*/

}

class GmailEmail {
	function __construct(GmailAccount $acc, $from, $subj, $msgid) {
		$this->acc = $acc;
		$this->subject = $subj;
		$this->from = $from;
		$this->msgid = $msgid;
		$this->headers = array(); //filled after calling view()
	}

	function __toString() {
		return sprintf('%s -> %s (%s)', $this->from, $this->subject, $this->msgid);
	}

	/**
	 * Delete email
	 */
	function delete() {
		$url = $this->acc->action_url;
		$v = array();
		$v['redir'] = '?';
		$v['nvp_a_tr'] = 'Delete';
		$v['tact'] = '';
		$v['bact'] = '';
		$v['t'] = $this->msgid;
		$doc = $this->acc->c->post($url, $v);
		if (stripos($doc, 'moved to the Trash') !== false)
			return array(true, null);
		else if (($err = Gmail::get_message($doc)))
			return array(false, $err);
		return array(false, 'unknown');
	}

	#also updates headers
	function view() {
		#need s=<section_char> for things in sent, spam, etc
		#v=c (normal), om (raw email)
		$url = "{$this->acc->base_url}/?v=om&th={$this->msgid}";
		#need to remove the \n at the top
		$doc = trim($this->acc->c->get($url));

		#imap_mime_header_decode();
		$this->headers = imap_rfc822_parse_headers($doc);
		if (!preg_match('@\n\n(.+)@s', $doc, $m))
			return array(false, 'match-error');

		#from normal page
		#if (!preg_match('@<div class=msg>\s*(.+?)\s*</div>@s', $doc, $m))
		#	return array(false, 'match-error');
		return array(true, $m[1]);
	}
}

class GmailAccount {

	/**
	 * @param string Login
	 * @param string Password
	 */
	function __construct($login, $password, $proxy=null) {
		$this->l = $login;
		$this->p = $password;
		$this->c = new Curl;
		$this->c->cookie_jar = '/tmp/cj';
		$this->captcha_url = null;
		if ($proxy) {
			$this->proxy = $proxy;
			$this->c->setProxy($proxy['host'], $proxy['user'], $proxy['passwd']);
		}
		else
			$this->proxy = null;
	}

	function __destruct() {
		#sometimes on exit, the curl resource is closed
		#before this is called; so just swallow any exceptions
		if ($this->c) {
			try {
				$this->c->cookie_jar = '';
				$this->c->close();
			}
			catch (Exception $e) {;}
		}
	}

	function _fillLoginForm($doc) {
		if (!($f = HTMLForm::find($doc, $this->c->last_url, array('fields' => 'Email'))))
			return array(false, null);
		$f['Email'] = $this->l;
		$f['Passwd'] = $this->p;
		$f['signIn'] = 'Sign in';
		return array(true, $f);
	}

	/**
	 * Login
	 * @note kinda hacky; if sending a captcha resp, ->captcha_form must be set
	 */
	function login($captcha_resp=null) {
		$url = 'http://mail.google.com/mail/h';
		$post_url = 'https://www.google.com/accounts/ServiceLoginAuth?service=mail';

		$c = $this->c;
		if (!$captcha_resp) {
			$doc = $c->get($url);

			$redir = true;
			while ($redir) {
				list($redir, $doc) = follow_redirect($c, $doc);
			}
			list($ok, $v) = $this->_fillLoginForm($doc);
			if (!$ok)
				return array(false, 'no-form');
			$this->captcha_submitted = false;
		}
		else {
			$v = $this->captcha_form;
			$v['logincaptcha'] = $captcha_resp;
			$this->captcha_url = null;
			$this->captcha_form = null;
			$this->captcha_submitted = true;
		}

		$c->referer = $c->last_url;
		$doc = $v->submit($c);
		#need to decode hex escapes
		if (preg_match('@location\.replace\(["\']([^\"]+)@i', $doc, $m)) {
			$url = $m[1];
			$url = preg_replace('@\\\\x([0-9a-fA-F]{2})@e', 'chr(hexdec("\\1"))', $url);
			$doc = $c->get($url);
		}

		#first time login
		if (stripos($doc, 'show me my acc') !== false) {
			#follow link on page to continue, then follow html ui link
			$c->get('http://mail.google.com/gmail');
			$doc = $c->get('http://mail.google.com/mail/?ui=html&zy=d');
		}

		if (stripos($doc, 'Sign out') !== false) {
			#for deleting emails, etc
			if (!preg_match('@action="(\?[^"]+)@i', $doc, $m))
				return array(false, 'no-act-url');
			$this->base_url = substr($c->last_url, 0, strrpos($c->last_url, '/'));
			$this->action_url = "{$this->base_url}/{$m[1]}";
			return array(true, null);
		}

		#this is weird; if we've answered a captcha correctly, we'll get this
		#but, if we try to login again, the acc works
		#so, if we haven't done a captcha, we assume the acc is actually dead
		#if we get the main page again, we just get a captcha
		else if (stripos($doc, 'your account has been disabled'))
			return array(
				false,
				($this->captcha_submitted ? 'captcha-ok' : 'dead')
			);

		else if (stripos($doc, 'captchahtml') !== false) {
			#save state for submitting captcha
			if (!preg_match('@(https://www\.google\.com/accounts/Captcha\?[^"]+)@i', $doc, $m))
				return array(false, 'captcha-missing-image');

			$this->captcha_url = $m[1];
			list($ok, $f) = $this->_fillLoginForm($doc);
			if (!$ok)
				return array(false, 'captcha-no-form');
			$this->captcha_form = $f;
			return array(false, 'captcha-required');
		}

		#file_put_contents('/tmp/g.html', $doc);
		return array(false, 'unknown');
	}

	#retrieves captcha (if one is required; returns false if not set)
	function getCaptcha() {
		if (!$this->captcha_url)
			return false;
		$img = $this->c->get($this->captcha_url);
		return $img;
	}

	/**
	 * Logout
	 */
	function logout() {
		;
	}

	/**
	 * Send an email
	 */
	function send($to, $subj, $message) {
		$c = $this->c;
		$doc = $c->get("{$this->base_url}/?v=b&pv=tl&cs=b");
		if (!preg_match('@action="(\?[^"]+)@i', $doc, $m))
			return array(false, 'no-post-url');

		$url = "{$this->base_url}/{$m[1]}";

		$v = Curl::findFormValues($doc);
		$v = $v[1];
		$v['nvp_bu_send'] = 'Send';
		$v['to'] = $to;
		$v['subject'] = $subj;
		$v['body'] = $message;
		unset($v['f']);
		unset($v['file0']);

		$vv = dict_to_tuples($v);
		$files = array('file0' => null);
		list($boundary, $mp) = build_multipart($vv, $files);

		$c->header = array('Expect:', 'Content-Type: multipart/form-data; boundary=' . $boundary);
		$c->referer = $c->last_url;
		$doc = $c->post($url, $mp);
		$c->header = array();
		if (stripos($doc, 'message has been sent') !== false)
			return array(true, null);
		else if (($msg = Gmail::get_message($doc)))
			return array(false, $msg);
		return array(false, 'unknown');
	}

	function getNewMail() {
		return $this->parseFeed();
	}

	#for pages, ?st=<n>, where n is a multiple of 50, 0 being p1, 50 being p2, etc
	function getMail($filter=null) {
		$c = $this->c;
		if ($filter) {
			$url = $this->base_url . '/?s=q&q=' . urlencode($filter) . '&nvp_site_mail=Search+Mail';
			$doc = $c->get($url);
		}
		else
			$doc = $c->get($this->base_url);

		$re = '@<td[^<]*>\n(?:<b>)?(?P<from>[^<]+)(?:</b>)?</td>\n<td[^<]*>\n<a href="[^"]+th=(?P<msgid>[^"&]+)">\n<span class=ts><font size=1><font color=#006633>\s*(?:.+\s*)?</font></font>\n(?:<b>)?(?P<subject>.+?)(?:</b>)?\n@';
		if (!preg_match_all($re, $doc, $matches, PREG_SET_ORDER))
			return array(true, array());
		$r = array();
		foreach ($matches as $m)
			$r[] = new GmailEmail($this, $m['from'], html_entity_decode($m['subject']), $m['msgid']);
		return array(true, $r);
	}

	function getFeed() {
		return $this->c->get('http://mail.google.com/mail/feed/atom');
	}

	function parseFeed() {
		$r = array();
		$emails = GMail::feed_to_emails($this->getFeed());
		foreach ($emails as $email) {
			preg_match('@message_id=([^&]+)@i', $email['link'], $m);
			$mid = $m[1];

			$link = "{$this->base_url}/?v=c&th=$mid";
			$r[] = new GmailEmail($this, $email['from'], $email['title'], $mid);
		}
		return $r;
	}
}

#define('GM_TEST', 1);
if (defined('GM_TEST')) {/*{{{*/
	require_once 'gtk/showimg.inc.php';
	$login = 'Caitlin84en@gmail.com';
	$passwd = 'thomas34hv';

	$proxy = array(
		'host' => '89.149.193.45:2642',
		'user' => 'daft',
		'passwd' => '2fmw5',
	);

	$gm = new GmailAccount($login, $passwd, $proxy);
	list($ok, $err) = $gm->login();
	if (!$ok && $err == 'captcha-required') {
		printf("captcha-url: %s\n", $gm->captcha_url);
		printf("captcha-form: %s\n", trim(print_r($gm->captcha_form, true)));
		$img = $gm->getCaptcha();
		$resp = get_response($img);
		print "resp=$resp\n";
		list($ok, $err) = $gm->login($resp);
		if ($err == 'captcha-ok') {
			#this sometimes doesn't work; we get a captcha page with an
			#image stating 'we couldn't handle your request at this time'
			$gm = new GMailAccount($login, $passwd, $proxy);
			list($ok, $err) = $gm->login();
		}
	}

	if (!$ok) {
		print "unable to login: $err\n";
		exit(-1);
	}
	print "login successful\n";
	exit(0);

	#list($ok, $err) = $gm->send('Sarahfv92@gmail.com', 'hello', 'world');
	#if (!$ok) {
	#	print "send failed: $err\n";
	#	exit(-1);
	#}
	#print "send successful\n";

	#$emails = $gm->getNewMail();
	#foreach ($emails as $email) {
	#	print "email: $email\n";
	#	list($ok, $msg) = $email->view();
	#	if (!$ok) {
	#		print "Unable to get message: $msg\n";
	#		exit(-1);
	#	}
	#	print "contents: $msg\n";
	#	list($ok, $err) = $email->delete();
	#	if (!$ok) {
	#		print "Unable to delete message: $err\n";
	#		exit(-1);
	#	}
	#	print "delete successful\n";
	#}

	#to:craigslist.org : replies to post
	list($ok, $emails) = $gm->getMail('in:inbox');
	if (!$ok) {
		print "unable to get emails: $emails\n";
	}
	#list($ok, $emails) = $gm->getMail('New Craigslist Account');
	#if (!$ok) {
	#	print "unable to get emails: $emails\n";
	#}
	print "email fetch successful\n";
	foreach ($emails as $email) {
		print "email: $email\n";
		list($ok, $msg) = $email->view();
		if (!$ok) {
			print "Unable to get message: $msg\n";
			exit(-1);
		}
		print "from: {$email->headers->fromaddress}; to {$email->headers->toaddress}\n";
		print "contents: $msg\n";
	}

	#$doc = $gm->getFeed();
	#file_put_contents('/tmp/a.xml', $doc);
}/*}}}*/
?>
