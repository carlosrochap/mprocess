<?php
/**
 * @package Connection
 */

/**
 * cURL wrapper
 *
 * @method string get()  Issues a GET request
 * @method string post() Issues a POST request
 * @method string ajax() Issues an AJAX GET/POST request
 * @property Connection_Proxy $proxy Proxy to use/in use
 * @property bool             $is_mobile   True if mobile UA is being used
 * @property string           $user_agent  User agent string
 * @property-read string $last_url    Last response's absolute URL
 * @property-read string $response    Last response body
 * @property-read int    $errno       Last cURL request's error number
 * @property-read string $error       Last cURL request's error string
 * @property-read int    $status_code Last cURL request's HTTP status code
 *
 * @package Connection
 * @subpackage Curl
 */
class Connection_Curl extends Connection_Abstract
{
    const DEFAULT_ACCEPT = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    const IMG_ACCEPT     = 'image/*,*/*';

    const AJAX_ACCEPT           = 'application/json,text/javascript,text/html,application/xml,text/xml,*/*';
    const AJAX_X_REQUESTED_WITH = 'XMLHttpRequest';

    const DEFAULT_TIMEOUT = 60;


    /**
     * User agents list, taken mostly from {@link http://www.useragentstring.com/}
     *
     * @var array
     */
    static public $user_agents = array(
        // Chrome
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.2 (KHTML, like Gecko) Chrome/6.0',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/533.3 (KHTML, like Gecko) Chrome/5.0.353.0 Safari/533.3',
        'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US) AppleWebKit/532.9 (KHTML, like Gecko) Chrome/5.0.310.0 Safari/532.9',
        // MSIE
        'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; InfoPath.2)',
        'Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 6.0; en-US)',
        'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        // FF
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2) Gecko/20100101 Firefox/3.6',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9) Gecko/2008052906 Firefox/3.0',
        // Opera
        'Opera/9.70 (Windows NT 5.2; U; en) Presto/2.1.1',
        'Opera/9.64 (Windows NT 5.1; U; en) Presto/2.1.1',
        'Opera/9.60 (Windows NT 5.0; U; en) Presto/2.1.1',
    );
    static public $mobile_user_agents = array(
        'Opera/9.50 (J2ME/MIDP; Opera Mini/4.0.10031/230; U; en)',
        'Opera/9.60 (J2ME/MIDP; Opera Mini/4.2.13337/504; U; en) Presto/2.2.0',
        'Opera/9.80 (J2ME/MIDP; Opera Mini/5.0.16823/1428; U; en) Presto/2.2.0',
    );

    static protected $_recoverable_errors = array(7, 18, 28, 35, 52, 56);
    static protected $_request_methods = array('get', 'post', 'ajax');


    /**
     * CURL connection
     *
     * @var resource
     */
    protected $_curl = null;

    /**
     * Current HTTP headers hash
     *
     * @var array
     */
    protected $_hdr = array();
    protected $_resp_hdr = array();

    /**
     * Current user agent string
     *
     * @var string
     */
    protected $_user_agent = '';

    /**
     * Default HTTP headers hash
     *
     * @var array
     */
    protected $_hdr_default = array(
        'Accept'          => '',
        'Accept-Language' => 'en-us,en',
        'Accept-Charset'  => 'UTF-8,ISO-8859-1;q=0.7,*;q=0.7',
        'Keep-Alive'      => 300,
        'Connection'      => 'keep-alive',
        'Expect'          => '',
        'Pragma'          => '',
    );

    /**
     * Cookies hash
     *
     * @var array
     */
    protected $_cookie = array();

    /**
     * Proxy to use
     *
     * @var Connection_Proxy
     */
    protected $_proxy = null;

    /**
     * Last HTTP response body
     *
     * @var string
     */
    protected $_resp = '';


    /**
     * Whether to retry on recoverable errors (see {@see ::$_recoverable_errors})
     * or not
     *
     * @var bool
     */
    public $retry_on_errors = false;

    /**
     * Flag whether to follow <meta http-equiv="refresh".../> URLs or not
     *
     * @var bool
     */
    public $follow_refresh = true;

    /**
     * Flag whether to follow 3xx HTTP responses
     *
     * @var bool
     */
    public $follow_location = true;


    /**
     * Whether to act as mobile client or not, so far affects User-Agent
     * request header only.
     *
     * @var bool
     */
    protected $_is_mobile = false;


    static public function prepare_header_key($key)
    {
        return ('X-' == substr($key, 0, 2))
            ? $key
            : implode('-', array_map('ucfirst', explode('-', strtolower($key))));
    }


    /**
     * Parses HTTP headers for cookies
     *
     * @param resource $conn cURL connection resource
     * @param string   $hdr  HTTP header line
     * @return int Header line length
     */
    public function parse_hdr($conn, $hdr)
    {
        list($key, $value) =
            array_pad(array_map('trim', explode(':', $hdr, 2)), 2, null);
        $key = $this->prepare_header_key($key);
        if (null !== $value) {
            if ('Set-Cookie' == $key) {
                $crumbs = array();
                foreach (explode(';', $value) as $s) {
                    $s = array_map('trim', explode('=', $s, 2));
                    $crumbs[$s[0]] = @$s[1];
                }
                reset($crumbs);
                list($k, $v) = each($crumbs);
                if (empty($crumbs['domain'])) {
                    $u = new Url(curl_getinfo($conn, CURLINFO_EFFECTIVE_URL));
                    $crumbs['domain'] = $u->host;
                }
                if (('deleted' == $v) || ('' === $v)) {
                    unset($this->_cookie[$k][$crumbs['domain']]);
                } else {
                    $this->_cookie[$k][$crumbs['domain']] = array(
                        'value'   => $v,
                        'expires' => strtotime(@$crumbs['expires']),
                    );
                }
            } else {
                $this->_resp_hdr[$key] = $value;
            }
        }
        return strlen($hdr);
    }

    /**
     * Closes opened connection
     */
    public function close()
    {
        if (is_resource($this->_curl)) {
            curl_close($this->_curl);
        }
        $this->_curl = $this->_resp = null;
        $this->_cookie = $this->_hdr = $this->_resp_hdr = array();
        return parent::close();
    }

    /**
     * (Re-)initializes cURL connection and object properties
     */
    public function init()
    {
        $this->close();

        $func = 'date_default_timezone_set';
        if (function_exists($func)) {
            $func('UTC');
        }

        $this->_curl = curl_init();
        curl_setopt_array($this->_curl, array(
            CURLOPT_VERBOSE        => false,
            CURLOPT_TIMEOUT        => self::DEFAULT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::DEFAULT_TIMEOUT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'gzip,deflate',
            CURLOPT_HEADERFUNCTION => array($this, 'parse_hdr'),
        ));

        $this->user_agent = $this->_is_mobile
            ? self::$mobile_user_agents[array_rand(self::$mobile_user_agents)]
            : self::$user_agents[array_rand(self::$user_agents)];

        $this->_hdr_default['Accept'] = self::DEFAULT_ACCEPT;
        $this->remove_headers();
        $this->set_headers($this->_hdr_default);

        if ($this->_proxy) {
            $this->set_proxy($this->_proxy);
        }

        return parent::init();
    }

    /**
     * Returns HTTP headers value
     *
     * @param string $name HTTP header name, null to return all headers
     * @return array|string|null
     */
    public function get_header($name=null, $verbatim_name=false)
    {
        if (null === $name) {
            return $this->_hdr;
        } else {
            if (!$verbatim_name) {
                $name = $this->prepare_header_key($name);
            }
            return isset($this->_hdr[$name])
                ? $this->_hdr[$name]
                : null;
        }
    }

    /**
     * Returns HTTP response headers value
     *
     * @param string $name HTTP response header name, null to return all headers
     * @return array|string|null
     */
    public function get_response_header($name=null)
    {
        if (null === $name) {
            return $this->_resp_hdr;
        } else {
            $name = $this->prepare_header_key($name);
            return isset($this->_resp_hdr[$name])
                ? $this->_resp_hdr[$name]
                : null;
        }
    }

    /**
     * Sets HTTP headers
     *
     * @param string|array $name  Header name or headers hash
     * @param mixed        $value Header value if a name's specified
     */
    public function set_headers($name, $value=null, $verbatim_name=false)
    {
        if (!is_array($name)) {
            $name = array($name => $value);
        }
        foreach ($name as $k => $v) {
            if (!$verbatim_name) {
                $k = $this->prepare_header_key($k);
            }
            $this->_hdr[$k] = (string)$v;
        }
        return $this;
    }

    /**
     * Removes HTTP headers
     *
     * @param string|array $name HTTP header name or a list of names;
     *                           will remove all headers if none specified
     */
    public function remove_headers($name=null, $verbatim_name=false)
    {
        if (null === $name) {
            $this->_hdr = array();
        } else {
            if (!is_array($name)) {
                $name = array($name);
            }
            foreach (($verbatim_name
                ? $name
                : array_map(array($this, 'prepare_header_key'), $name)
            ) as $k) {
                unset($this->_hdr[$k]);
            }
        }
        return $this;
    }

    /**
     * Returns cookies value
     *
     * @param string $name Cookie name, null to return all cookies
     * @return array|string|null
     */
    public function get_cookie($name=null, $domain=null)
    {
        if (null === $name) {
            return $this->_cookie;
        }
        if (!isset($this->_cookie[$name])) {
            return null;
        }
        reset($this->_cookie[$name]);
        $cookie = (null === $domain)
            ? current($this->_cookie[$name])
            : (isset($this->_cookie[$name][$domain])
                ? $this->_cookie[$name][$domain]
                : null);
        if ($cookie) {
            return $cookie['value'];
        }
    }

    /**
     * Adds/changes cookies
     *
     * @param string|array $name    Cookie name or cookies hash
     * @param mixed        $value   Cookie value
     * @param string       $domain  Cookie domain
     * @param int          $expires Cookie expiration timestamp
     */
    public function set_cookie($name, $value=null, $domain=null, $expires=false)
    {
        if (is_resource($this->_curl)) {
            if (!is_array($name)) {
                $name = array($name => $value);
            }
            if (null === $domain) {
                foreach ($name as $k => $v) {
                    $this->_cookie[$k] = array();
                }
                $u = new Url($this->last_url);
                $domain = $u->host;
            }
            foreach ($name as $k => $v) {
                $this->_cookie[$k][$domain] = array(
                    'value'   => (string)$v,
                    'expires' => $expires,
                );
            }
        }
        return $this;
    }

    /**
     * Removes cookies
     * * @param string|array|null $name Options cookie name or list of names
     */
    public function remove_cookie($name=null, $domain=null)
    {
        if (null === $name) {
            $this->_cookie = array();
        } else {
            if (!is_array($name)) {
                $name = array($name);
            }
            foreach ($name as $k) {
                if (null === $domain) {
                    unset($this->_cookie[$k]);
                } else {
                    unset($this->_cookie[$k][$domain]);
                }
            }
        }
        return $this;
    }

    public function set_user_agent($ua)
    {
        $this->_user_agent = $ua;
        if ($this->_curl) {
            curl_setopt($this->_curl, CURLOPT_USERAGENT, $ua);
        }
        return $this;
    }

    public function get_user_agent()
    {
        return $this->_user_agent;
    }

    public function set_is_mobile($is_mobile)
    {
        $this->_is_mobile = (bool)$is_mobile;
        if ($this->_is_mobile) {
            $this->user_agent =
                self::$mobile_user_agents[array_rand(self::$mobile_user_agents)];
        }
        return $this;
    }

    public function get_is_mobile()
    {
        return $this->_is_mobile;
    }

    /**
     * Sets proxy to use
     *
     * @param Connection_Proxy|string $proxy Proxy object, {@link http://tools.ietf.org/html/rfc3986#section-3.2 authority component}
     *                                       or just a host
     */
    public function set_proxy($proxy)
    {
        $this->_proxy = $proxy
            ? (!$proxy instanceof Connection_Proxy
                ? new Connection_Proxy($proxy)
                : $proxy)
            : null;
        if ($this->_proxy) {
            if (is_resource($this->_curl)) {
                curl_setopt_array($this->_curl, array(
                    CURLOPT_PROXY        => $this->_proxy->hostport,
                    CURLOPT_PROXYUSERPWD => $this->_proxy->userpass,
                    CURLOPT_PROXYTYPE    =>
                        (('socks5' == $this->_proxy->scheme)
                            ? CURLPROXY_SOCKS5
                            : CURLPROXY_HTTP)
                ));
            }
        } else {
            curl_setopt($this->_curl, CURLOPT_PROXY, null);
        }
        return $this;
    }

    /**
     * Returns the proxy currently in use
     *
     * @return Connection_Proxy|null
     */
    public function get_proxy()
    {
        return $this->_proxy;
    }

    /**
     * Returns last request's response body
     *
     * @return string|null
     */
    public function get_response()
    {
        return $this->_resp;
    }

    /**
     * Returns last request's CURL error description
     *
     * @return string|null
     */
    public function get_error()
    {
        return is_resource($this->_curl)
            ? curl_error($this->_curl)
            : null;
    }

    /**
     * Returns last request's CURL error number
     *
     * @return int|null
     */
    public function get_errno()
    {
        return is_resource($this->_curl)
            ? curl_errno($this->_curl)
            : null;
    }

    public function follow_refresh($src=null, $url=null)
    {
        if (null === $src) {
            $src = $this->_resp;
        }

        if (!preg_match(
            '#http-equiv=[\'"]?refresh[\'"]?\s+content=[\'"]\d+;\s*url=\s*[\'"]?([^\'"]+)#i',
            $src,
            $refresh_url
        )) {
            return $src;
        } else {
            $refresh_url = trim(
                html_entity_decode($refresh_url[1], ENT_QUOTES),
                " \t\n\r\x00\x0b\"'"
            );
        }

        $url = (null === $url)
            ? new Url($this->last_url)
            : (($url instanceof Url)
                ? $url
                : new Url($url));
        if (preg_match('#^https?://#', $refresh_url)) {
            $refresh_url = new Url($refresh_url);
        } else {
            $a = explode('?', $refresh_url, 2);
            $refresh_url = new Url();
            if (!$a[0] || ('/' != $a[0][0])) {
                $refresh_url->path = $a[0]
                    ? dirname($url->path) . '/' . $a[0]
                    : $url->path;
            }
            if (@$a[1]) {
                $refresh_url->query = $a[1];
            }
        }
        if (!$refresh_url->is_valid) {
            $refresh_url->scheme = $url->scheme;
            $refresh_url->userpass = $url->userpass;
            $refresh_url->hostport = $url->hostport;
        }
        if (!in_array($refresh_url->get(), array($url->get(), $this->last_url))) {
            $this->log("Refresh to {$refresh_url}",
                       Log_Abstract::LEVEL_DEBUG);
            return $this->request($refresh_url, null, '', $url);
        } else {
            return $src;
        }
    }

    /**
     * Sends an arbitrary HTTP request
     *
     * Treats '<meta http-equiv="refresh"...>' tags found in response body
     * as redirects if {@link ::$follow_refresh} flag set to true.
     *
     * @param string       $url     Request URL
     * @param string|array $query   GET query string or hash table
     * @param string       $referer HTTP referer
     * @param string|array $post    POST fields, string or hash table
     * @param array        $hdr     Optional HTTP headers
     * @return mixed HTTP response body
     */
    public function request(
        $url,
        $post=null,
        $query='',
        $referer=null,
        array $hdr=array()
    )
    {
        $this->_resp = null;
        $this->_resp_hdr = array();

        if (!is_resource($this->_curl)) {
            return false;
        }

        if (!$url instanceof Url) {
            $url = new Url($url);
        }
        if (!$url->is_valid) {
            return false;
        }

        if ($query) {
            $url->query = $query;
        }

        if (null !== $referer) {
            if (is_object($referer)) {
                $m = '__toString';
                if (method_exists($referer, $m)) {
                    $referer = $referer->{$m}();
                }
            }
            $referer = (string)$referer;
        } else {
            $referer = $this->last_url;
        }

        $options = array(
            CURLOPT_FRESH_CONNECT => false,
            CURLOPT_URL           => $url->__toString(),
        );
        if ($referer) {
            $options[CURLOPT_REFERER] = $referer;
        }
        if (null !== $post) {
            if (is_array($post)) {
                $m = '__toString';
                foreach ($post as &$v) {
                    if (is_object($v) && method_exists($v, $m)) {
                        $v = $v->{$m}();
                    }
                }
            }
            $method = 'POST';
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = &$post;
        } else {
            $method = 'GET';
            $options[CURLOPT_HTTPGET] = true;
        }

        $hdr_old = array();
        if (count($hdr)) {
            foreach (array_keys($hdr) as $k) {
                if (isset($this->_hdr[$k])) {
                    $hdr_old[$k] = $this->_hdr[$k];
                }
            }
            $this->set_headers($hdr, null, true);
        }
        if (count($this->_hdr)) {
            $options[CURLOPT_HTTPHEADER] = array();
            foreach ($this->_hdr as $k => $v) {
                if (null !== $v) {
                    $options[CURLOPT_HTTPHEADER][] = "{$k}: {$v}";
                }
            }
        }
        $cookies = array();
        foreach ($this->_cookie as $k => $cookie) {
            foreach ($cookie as $domain => $v) {
                if ($v['expires'] && (time() > $v['expires'])) {
                    unset($this->_cookie[$k][$domain]);
                } else if (!$domain || (false !== strpos(
                    '.' . $url->host,
                    $domain
                ))) {
                    $cookies[$k] = $v['value'];
                    if ($url->host == $domain) {
                        break;
                    }
                }
            }
        }
        $options[CURLOPT_COOKIE] = array();
        foreach ($cookies as $k => $v) {
            $options[CURLOPT_COOKIE][] = "{$k}={$v}";
        }
        $options[CURLOPT_COOKIE] = implode('; ', $options[CURLOPT_COOKIE]);

        curl_setopt_array($this->_curl, $options);

        $i = 3;
        do {
            $this->_resp = curl_exec($this->_curl);
            if ($this->retry_on_errors) {
                $errno = curl_errno($this->_curl);
                if (!$errno || !in_array($errno, self::$_recoverable_errors)) {
                    break;
                } else {
                    curl_setopt($this->_curl, CURLOPT_FRESH_CONNECT, true);
                    sleep(1);
                }
            }
            $i--;
        } while ($this->retry_on_errors && (0 < $i));
        $this->log(
            (isset($options[CURLOPT_POST])
                ? 'POST'
                : 'GET') . ' ' . $options[CURLOPT_URL] . ' -> ' . $this->status_code,
            Log_Abstract::LEVEL_DEBUG
        );

        if (count($hdr)) {
            $this->remove_headers(array_keys($hdr), true);
            if (count($hdr_old)) {
                $this->set_headers($hdr_old, null, true);
            }
        }

        if ((3 == (int)($this->status_code / 100)) && $this->follow_location) {
            $location = $this->get_response_header('Location');
            if ($location) {
                if (false === strpos($location, '://')) {
                    $location =
                        $url->scheme . '://' . $url->hostport .
                        (('/' == @$location[0])
                            ? ''
                            : rtrim(dirname($url->path), '/') . '/') .
                        $location;
                }
                if (302 == $this->status_code) {
                    $post = null;
                }
                return $this->request(new Url($location), $post, '', $url, $hdr);
            }
        }

        if ($this->follow_refresh) {
            $this->_resp = $this->follow_refresh($this->_resp);
        }
        return $this->_resp;
    }

    /**
     * Downloads a file
     *
     * @param string $url
     * @param string $fn  Local file name
     * @return bool
     * @uses ::request() For actual request
     * @see ::request() For arguments details
     * @throws Connection_Exception When failed to open a local file
     */
    public function download($url, $fn, $post=null, $query='', $referer=null, array $hdr=array())
    {
        $this->_resp = false;
        $this->_resp_hdr = array();

        if ($fp = fopen($fn, 'wb')) {
            if (!isset($hdr['Accept'])) {
                $hdr['Accept'] = self::IMG_ACCEPT;
            }

            curl_setopt_array($this->_curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_FILE           => $fp
            ));

            $this->request($url, $post, $query, $referer, $hdr);
            $this->_resp =
                ((bool)$this->_resp && !$this->errno && (400 > $this->http_code));

            curl_setopt_array($this->_curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => false
            ));
            fclose($fp);
        }

        return $this->_resp;
    }

    /**
     * @ignore
     */
    public function __set($key, $value)
    {
        if ('proxy' != $key) {
            $key = strtolower($key);
            switch ($key) {
            case 'useragent':
                $key = 'user_agent';
                break;

            case 'followlocation':
                $this->follow_location = (bool)$value;
                return;

            case 'do_follow_refresh':
            case 'followrefresh':
                $this->follow_refresh = (bool)$value;
                return;

            default:
                $const = 'CURLOPT_' . strtoupper($key);
                if (defined($const)) {
                    if (is_resource($this->_curl)) {
                        curl_setopt($this->_curl, constant($const), $value);
                    }
                    return;
                }
                break;
            }
        }

        parent::__set($key, $value);
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        $value = parent::__get($key);
        if (null !== $value) {
            return $value;
        }

        $key = strtoupper($key);
        switch ($key) {
        case 'FOLLOWLOCATION':
            return $this->follow_location;

        case 'DO_FOLLOW_REFRESH':
        case 'FOLLOWREFRESH':
            return $this->follow_refresh;

        case 'USERAGENT':
            return $this->_user_agent;

        default:
            if ('LAST_URL' == $key) {
                $key = 'EFFECTIVE_URL';
            } else if ('STATUS_CODE' == $key) {
                $key = 'HTTP_CODE';
            }
            $const = "CURLINFO_{$key}";
            if (is_resource($this->_curl) && defined($const)) {
                return curl_getinfo($this->_curl, constant($const));
            }
        }
    }

    /**
     * @ignore
     */
    public function __call($method, $args)
    {
        if (!in_array($method, self::$_request_methods)) {
            throw new BadMethodCallException("Method {$method} not found");
        }

        if ('post' == $method) {
            sleep(rand(2, 8));
        } else if ('get' == $method) {
            array_splice($args, 1, 0, array(null));
        }
        for ($i = 0; $i <= 4; $i++) {
            if (!isset($args[$i])) {
                $args[$i] = null;
            }
        }
        // Set up URL
        if (!$args[0] instanceof Url) {
            $args[0] = new Url($args[0]);
        }
        // Set up POST fields
        if (('post' == $method) && (null === $args[1])) {
            $args[1] = '';
        } else if (('ajax' == $method) && is_array($args[1])) {
            $args[1] = http_build_query($args[1]);
        }
        // Set up query string
        if ($args[2] && !is_array($args[2])) {
            $args[2] = (string)$args[2];
        }
        // Set up referer URL
        if ($args[3] && !($args[3] instanceof Url)) {
            $args[3] = new Url($args[3]);
        }
        // Set up optional request headers
        if (!is_array($args[4])) {
            $args[4] = array();
        }
        if ('ajax' == $method) {
            foreach (array('X-Requested-With', 'Accept') as $k) {
                if (!array_key_exists($k, $args[4])) {
                    $args[4][$k] = constant(
                        get_class($this) . '::AJAX_' .
                        strtoupper(strtr($k, '-', '_'))
                    );
                }
            }
        }
        return call_user_func_array(array($this, 'request'), $args);
    }
}
