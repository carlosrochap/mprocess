<?php
#2009-03-13
require_once 'common-util.inc.php';

class YMail {
    const INBOX = 'Inbox';
    const SPAM = '@B@Bulk';
}

class YmailEmail {
    function __construct(YmailAccount $acc, $from, $subj, $url) {
        $this->acc = $acc;
        $this->subject = $subj;
        $this->from = $from;
        $this->url = $url;
    }

    function __toString() {
        return sprintf('%s -> %s (%s)', $this->from, $this->subject, $this->url);
    }

    function delete() {
        list($ok, $doc) = $this->view();
        if (!$ok) {
            return array(false, 'view-failed: ' . $doc);
        }
        if (!$form = Html_Form::get($doc, $this->acc->c, 'name', 'showMessageForm')) {
            return array(false, 'no-form');
        }
        $form['top_bpress_delete'] = 'Delete';
        #no confirmation if it was deleted; we either end up in box or the next message
        $form->submit($this->acc->c);
        return array(true, null);
    }

    function view() {
        #if we append a head=f to the url query, we can get a list of headers
        #(but embedded in html)
        #appending pView=1 gives us a printable view
        $url = $this->acc->mailhost . '/mc/' . $this->url;
        $doc = $this->acc->c->get($url);
        return array(true, $doc);
    }

}

class YmailAccount {
    const TMP_PATH = './tmp';


    function __construct($login, $password, $proxy=null) {
        $this->l = $login;
        $this->p = $password;
        $this->captcha_form = null;
        $this->captcha_url = null;

        $this->is_verbose = false;
        $this->mailhost = null;

        $this->c = new Connection_Curl();
        if ($proxy) {
            $this->c->proxy = $proxy;
        }
    }

    function _dump($doc)
    {
        if ($this->is_verbose) {
            file_put_contents(self::TMP_PATH . '/ymail.' . time() . '.html', $doc);
        }
    }

    function setMailhost($url) {
        $this->mailhost = 'http://' . parse_url($url, PHP_URL_HOST);
    }

    function getCaptcha() {
        if (!$this->captcha_url)
            return false;
        $img = $this->c->get($this->captcha_url);
        return $img;
    }
    
    function _handleResponses($doc)
    {
        ### login errors ###
        if (stripos($doc, 'id="secword"') !== false) {
            $forms = Html_Form::get($doc, $this->c);
            if (!count($forms)) {
                return array(false, 'invalid-captcha-form');
            }
            $this->captcha_form = $forms[0];
            #find image url
            if (!preg_match('@"(https://ab\.login\.yahoo\.com/img/[^"]+)@', $doc, $m))
                return array(false, 'cant-find-captcha');
            $this->captcha_url = $m[1];
            return array(false, 'captcha');
        }

        if (strpos($doc, 'Invalid ID or password.'))
            return array(false, 'dead');
        else if (stripos($doc, 'not yet taken') !== false)
            return array(false, 'dead');

        ### password confirmation ###
        if (stripos($doc, 'Why am I being asked') !== false) {
            if ($this->is_verbose)
                print "Password confirmation\n";
            $forms = Html_Form::get($doc, $this->c);
            if (!$f = $forms[0]) {
                print "No password confirmation forms found\n";
                return array(false, 'no-pwd-confirm-form');
            }
            $f['passwd'] = $this->p;
            $doc = $f->submit($this->c);
            $this->_dump($doc);
        }

        if (stripos($doc, 'Improve performance') !== false) {
            if ($this->is_verbose)
                print "Improve performance page\n";
            $f = null;
            foreach (Html_Form::get($doc, $this->c) as $f) {
                if (isset($f['.done'])) {
                    break;
                } else {
                    $f = null;
                }
            }
            if (!$f) {
                return array(false, 'Improve perf page without form');
            }

            $f['.norepl'] = ' No ';
            $f->submit($this->c);
            $this->_dump($doc);
        }

        if (stripos($doc, 'id="supRegForm"') !== false) {
            if ($this->is_verbose) {
                print "Secret questions page\n";
            }

            if (!$f = Html_Form::get($doc, $this->c, 'id', 'supRegForm')) {
                return array(false, 'Secret questions page without form');
            }

            $f['spwq1'] = 'Where did you spend your honeymoon?';
            $f['pwq1'] = '';
            $f['pwa1'] = 'Right there';

            $f['spwq2'] = 'Who is your favorite author?';
            $f['pwq2'] = '';
            $f['pwa2'] = 'Thomas Pynchon';

            $f['save'] = 'Save and Continue';

            $doc = $f->submit($this->c);
            $this->_dump($doc);
        }

        return $doc;
    }

    /**
     * when submitting a captcha response, receiving another captcha error
     * indicates an invalid response/timeout
     */
    function login($captcha_resp=null, $doc=null) {
        $url = 'http://mail.yahoo.com/';

        if (!$doc) {
            if (!$captcha_resp) {
                $doc = $this->c->get($url);
                $this->_dump($doc);

                #list($ok, $err) = is_proxy_error($doc);
                #if ($ok)
                #    return array(false, 'proxy-error: ' . $err);

                $login_form = null;
                foreach (Html_Form::get($doc, $this->c) as $login_form) {
                    if (isset($login_form['passwd'])) {
                        break;
                    } else {
                        $login_form = null;
                    }
                }
                if (!$login_form) {
                    return array(false, 'no-login-form');
                }
            }
            else {
                list($login_form, $this->captcha_form) = array($this->captcha_form, null);
                $login_form['.secword'] = $captcha_resp;
            }

            $login_form['login'] = $this->l;
            $login_form['passwd'] = $this->p;
            $login_form['.save'] = 'Sign In';

            $doc = $login_form->submit($this->c);
            $this->_dump($doc);

            $doc = $this->_handleResponses($doc);
            if (is_array($doc)) {
                return $doc;
            }
        }

        ### redirect ###
        while (1) {
            if (preg_match('@location\.replace\(["\']([^\"]+)["\']\s*\)@i', $doc, $m)
                || preg_match("@CONTENT=\"0;\s*URL='([^']+)@i", $doc, $m)) {
                $url = html_entity_decode($m[1], ENT_QUOTES);
                if ($this->is_verbose)
                    print "following redirect #1: $url\n";
                $doc = $this->c->get($url);
                $this->_dump($doc);
            }
            /*else if (preg_match('@CONTENT="0;\s*URL=([^"]+)@i', $doc, $m)) {
                $url = html_entity_decode($m[1], ENT_QUOTES);
                if ($this->is_verbose)
                    print "following redirect #2: $url\n";
                $doc = $this->c->get($url);
            }*/
            else if (stripos($doc, 'not been tested with your operating system') !== false) {
                if ($this->is_verbose)
                    print "not tested with os\n";

                if (!preg_match("@doOptout\('([^']{2,})'\)@", $doc, $m))
                    return array(false, 'no optout crumb found');

                $crumb = $m[1];
                if ($this->is_verbose)
                    print "crumb: $crumb\n";

                if (!$f = Html_Form::get($doc, $this->c, 'id', 'doOptoutForm')) {
                    return array(false, 'no optout form');
                }

                $f['crumb'] = $crumb;

                $doc = $f->submit($this->c);
                $this->_dump($doc);
            }
            else if (stripos($doc, 'Choose a new Yahoo! ID') !== false) {
                if (!$f = Html_Form::get($doc, $this->c, 'id', 'upgrade-form')) {
                    return array(false, 'no-upgrade-form');
                }
                $f['tos-agree'] = 'y';
                $f['IAgreeBtn'] = 'Continue';
                $f['radio_id'] = strtolower($this->l);
                if (stripos($this->l, '@yahoo.com') === false)
                    $f['radio_id'] .= '@yahoo.com';
                $doc = $f->submit($this->c);
                if (stripos($doc, 'Below are your account') === false)
                    return array(false, 'choose-id-failed');
                $f = null;
                foreach (Html_Form::get($doc, $this->c) as $f) {
                    if (isset($f['done'])) {
                        break;
                    } else {
                        $f = null;
                    }
                }
                if (!$f) {
                    return array(false, 'missing-continue-form');
                }
                $f['ContinueBtn'] = 'Continue';
                $doc = $f->submit($this->c);
                $this->_dump($doc);
            }
            else if (preg_match(
                '#href=\'([^\']+)\'>proceed directly to Yahoo! Mail Classic#',
                $doc,
                $m
            )) {
                $doc = $this->c->get(html_entity_decode($m[1], ENT_QUOTES));
                $this->_dump($doc);
            }
            else
                break;
        }

        if (
            (false !== stripos($doc, 'I prefer the US')) ||
            (false !== stripos($doc, 'Keep My Account in the U.S.'))
        ) {
            if ($this->is_verbose) {
                print "Diff country mail page\n";
            }
            if (!$f = Html_Form::get($doc, $this->c, 'id', 'form2')) {
                return array(false, 'diff mail page without forms');
            }
            $doc = $f->submit($this->c);
            $this->_dump($doc);
        }

        #ok, we might already be logged in
        #or we might be given the choice to go to mail classic if we're set to beta
        if (preg_match("@<a href='([^']+)'[^>]+>proceed directly to Yahoo! Mail Classic@i", $doc, $m) ||
            preg_match("@<a href='([^']+)'[^>]+>Switch back to Yahoo! Mail Classic@i", $doc, $m)) {
            $url = $m[1];
            if ($url[0] == '/') {
                $pieces = parse_url($this->c->last_url);
                $url = sprintf('%s://%s%s', $pieces['scheme'], $pieces['host'], $url);
            } else if (0 != strpos($url, 'http')) {
                // Resolve relative URL
                $pieces = parse_url($this->c->last_url);
                if (empty($pieces['path'])) {
                    $pieces['path'] = '/';
                }
                $pieces['path'] = explode('/', $pieces['path']);
                $pieces['path'][count($pieces['path']) - 1] = $url;
                $url = sprintf('%s://%s%s', $pieces['scheme'], $pieces['host'], implode('/', $pieces['path']));
            }

            if ($this->is_verbose) {
                print "going to mail classic: $url\n";
            }

            $doc = $this->c->get($url);
            $this->_dump($doc);

            if (preg_match('@document.location.href\s*=\s\'([^\']+)@', $doc, $m)) {
                $url = $m[1];
                if ($this->is_verbose)
                    print "mail classic redirect: $url\n";
                $doc = $this->c->get($url);
                $this->_dump($doc);
            }
        }

        ### reactivation ###
        if (stripos($doc, 'Reactivate my account') !== false) {
            if ($this->is_verbose)
                print "reactivating\n";
            if (!preg_match('#name="protect" checked value="([^"]+)#', $doc, $m)) {
                print "bad regex\n";
                return array(false, 'no-YY');
            }
            $doc = $this->c->get(str_replace(
                'y5beta=yes',
                'y5beta=no',
                html_entity_decode($m[1])
            ), null, $this->c->last_url);
            $this->_dump($doc);

            $q = array();
            $a = parse_url($this->c->last_url);
            parse_str($a['query'], $q);
            if (empty($q['_done'])) {
                print "redirect URL not found\n";
                return array(false, 'no-reactivation-done-redirect');
            }
            $doc = $this->c->get($q['_done']);
            $this->_dump($doc);
            return $this->login(null, $doc);
        }

        $doc = $this->_handleResponses($doc);
        if (is_array($doc)) {
            return $doc;
        }

        #need a better check than this
        if (stripos($doc, 'Sign Out') !== false && stripos($doc, 'My Attachments') !== false) {
            $this->setMailhost($this->c->last_url);
            return array(true, null);
        }

        return array(false, 'unknown');
    }

    function getMail($folder) {
        $url = $this->mailhost . '/mc/showFolder?fid=' . $folder . '&ymv=0';
        if ($this->is_verbose)
            print "accessing mailbox at $url\n";
        $doc = $this->c->get($url);

        if (stripos($doc, 'Sorry for the inconvenience.') !== false)
            return array(false, 'cannot-access-mailbox');

        if (stripos($doc, 'There are no messages in your') !== false)
            return array(true, array());

        $this->_dump($doc);

        if (!preg_match_all('@<td title="(?:[^"]+)"><div>(?P<from>[^<]+)</div></td><td class="fixwidth">(?:<div class="icons attachicon" title="Attachments">&nbsp;<b>Attachments</b></div>)?</td><td><div><h2><a href="(?P<query>[^"]+)"\s*title="(?P<subject>[^"]+)">@i', $doc, $matches, PREG_SET_ORDER))
            return array(false, 'email-match-failure');
        $mail = array();
        foreach ($matches as $m) {
            $mail[] = new YmailEmail($this, $m['from'], $m['subject'], $m['query']);
        }
        return array(true, $mail);
    }

    function sendMail($to, $subj, $msg) {
        $url = $this->mailhost . '/mc/showFolder?fid=' . YMail::INBOX . '&ymv=0';
        $doc = $this->c->get($url);
        if (!preg_match('@action="(compose[^"]+)@', $doc, $m))
            return array(false, 'no-compose-link');
        $url = $this->mailhost . '/mc/' . $m[1];
        $doc = $this->c->get($url);

        $f = null;
        foreach (Html_Form::get($doc, $this->c) as $f) {
            if (isset($f['Subj'])) {
                break;
            } else {
                $f = null;
            }
        }
        if (!$f) {
            return array(false, 'no-form');
        }

        /* htmlize newlines */
        $msg = str_replace("\n", '<br />', $msg);
        $f['to'] = $to;
        $f['jsonEmails'] = '{"' . $to . '":false}';
        $f['Subj'] = $subj;
        $f['Content'] = $msg;
        $f['action_msg_send'] = 'Send';
        #$f->action .= '&clean&.jsrand=9&needG&mcrumb=' . $f['mcrumb'] . '&op=data';
        $doc = $f->submit($this->c);

        if (stripos($doc, '<h2>Message Sent</h2>') !== false
            || stripos($doc, 'has been sent') !== false)
            return array(true, null);
        return array(false, 'unknown');
    }
}

#define('YM_TEST', 1);
if (defined('YM_TEST')) {/*{{{*/
    $login = 'blah@yahoo.com'; $passwd = 'password';

    #$proxy = array(
    #    'host' => '',
    #    'user' => '',
    #    'passwd' => '',
    #);
    $proxy = null;

    $ym = new YmailAccount($login, $passwd, $proxy);
    $ym->is_verbose = true;
    list($ok, $err) = $ym->login();
    if (!$ok) {
        print "unable to login: $err\n";
        if ($err == 'captcha') {
            require_once 'gtk/showimg.inc.php';
            $img = $ym->getCaptcha();
            $resp = get_response($img);
            list($ok, $err) = $ym->login($resp);
            if (!$ok) {
                print "login failed: $err\n";
                exit(-1);
            }
        }
        else
            exit(-1);
    }
    print "login successful; mailhost: {$ym->mailhost}\n";

    #list($ok, $err) = $ym->sendMail('blah@blah.com', 'hello', 'world');
    #if (!$ok) {
    #    print "send failed: $err\n";
    #    exit(-1);
    #}
    #print "send successful\n";

    list($ok, $mail) = $ym->getMail(YMail::INBOX);
    if (!$ok) {
        print "fetching mail failed: $mail\n";
        exit(-1);
    }
    print "mail fetch successful\n";
    foreach ($mail as $m) {
        print "$m\n";
        #list($ok, $msg) = $m->delete();
        #list($ok, $msg) = $m->view();
        #file_put_contents('/tmp/ym.html', $msg);
        #break;
    }
}/*}}}*/
?>
