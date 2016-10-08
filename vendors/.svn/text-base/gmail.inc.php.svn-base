<?php
require_once 'common-util.inc.php';
require_once 'Curl.inc.php';
require_once 'google/gmail.inc.php';
require_once 'vs/Proxies.class.php';

/**
 * Returns first email matching the given filter
 * @return tuple (false, error) if error, (true, email-doc) if no matching,
 * (true, message) if found
 */
function gmail_find_matching($user, $proxy, $filter) {
	$gm = new GMailAccount($user['username'], $user['password'], $proxy);
	list($ok, $err) = $gm->login();

	if (!$ok)
		return array(false, "login-failed: $err", null);

	list($ok, $emails) = $gm->getMail($filter);
	if (!$ok)
		return array(false, "unable-to-get-mail: $emails", null);

	$e = array_shift($emails);
	list($ok, $doc) = $e->view();
	if (!$ok)
		return array(false, "unable-to-get-email: $doc", null);
	return array(true, $doc, $e);
}

?>
