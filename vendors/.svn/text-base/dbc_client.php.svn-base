<?php
/**
 * @package DBCAPI
 */

/**
 * Death by Captcha API Client
 *
 * @property-read bool $is_logged_in Logged in/out status flag
 * @property-read float|false $balance User's balance
 * @property-read array $last_response Last API call response, decoded from JSON or URL query string
 * @property-read array $response Alias for {@see ::$last_response}
 * @property-read int $status_code Last API call HTTP status code
 * @property-read mixed $... Any {$see ::$last_response} field if present
 *
 * @package DBCAPI
 * @subpackage PHP
 */
class DeathByCaptcha_Client
{
    const API_VERSION        = '2.5';
    const SOFTWARE_VENDOR_ID = 0;

    const API_SERVER_URL = 'http://www.deathbycaptcha.com/api';

    const API_USER_AGENT = 'Death by Captcha/PHP';

    const API_RESPONSE_JSON  = 'application/json';
    const API_RESPONSE_PLAIN = 'text/plain';

    const HASH_FUNC = 'sha1';

    const MAX_CAPTCHA_FILESIZE = 131072;

    const POLLS_COUNT    = 4;
    const POLLS_PERIOD   = 15;
    const POLLS_INTERVAL = 5;

    const DEFAULT_TIMEOUT = 60;


    protected $_conn = null;

    protected $_userpass = array();

    protected $_response = array();
    protected $_response_type = '';
    protected $_response_parser = null;


    /**
     * Verbosity flag
     *
     * @var bool
     */
    public $is_verbose = false;


    /**
     * Parses URL query encoded responses
     *
     * @param string $s
     * @return array
     */
    static public function parse_plain_response($s)
    {
        parse_str($s, $a);
        return $a;
    }

    /**
     * Parses JSON encoded response
     *
     * @param string $s
     * @return array
     */
    static public function parse_json_response($s)
    {
        return json_decode($s, true);
    }


    /**
     * Dumps a message, variable (serialized) or object string to stderr
     *
     * @param mixed $msg
     */
    protected function _dump($msg)
    {
        $m = '__toString';
        fputs(STDERR, ((is_object($msg) && method_exists($msg, $m))
            ? $msg->$m()
            : (is_array($msg)
                ? serialize($msg)
                : (string)$msg)) . "\n");
        return $this;
    }

    /**
     * Hashes arbitrary raw string with the function used by the service
     *
     * @param string $s
     */
    protected function _get_hash($s)
    {
        return call_user_func(self::HASH_FUNC, $s);
    }

    /**
     * Makes an API call
     *
     * @param string $method API method
     * @param string|array $args API call arguments, essentially
     *                           an HTTP POST request fields
     * @return array|false API response hash table on success,
     *                     otherwise false
     */
    protected function _call($method, $args=null, $do_login=false)
    {
        $this->_response = array();

        if (!is_resource($this->_conn)) {
            $this->init();
        }
        if (!$this->_conn) {
            $this->_dump('No connection to use');
            return false;
        }

        $opts = array(
            CURLOPT_URL     =>
                self::API_SERVER_URL . '/' . trim($method, '/'),
            CURLOPT_REFERER => '',
        );
        if ($do_login) {
            if (!$this->is_logged_in) {
                $this->_dump('Missing credentials');
                return false;
            }
            $credentials = array(
                'username'  => &$this->_userpass[0],
                'password'  => $this->_get_hash($this->_userpass[1]),
                'is_hashed' => 1,
                'swid'      => self::SOFTWARE_VENDOR_ID,
            );
            if (is_array($args)) {
                $args = array_merge($args, $credentials);
            } else {
                $args = ($args
                    ? "{$args}&"
                    : '') . http_build_query($credentials);
            }
        }
        if (null !== $args) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = &$args;
        } else {
            $opts[CURLOPT_HTTPGET] = true;
        }
        curl_setopt_array($this->_conn, $opts);

        if ($this->is_verbose) {
            $this->_dump("CALL: {$method}");
        }

        $response = curl_exec($this->_conn);
        if (!$response || curl_error($this->_conn)) {
            $this->_dump(
                'Connection failed: ' .
                curl_errno($this->_conn) . ' ' .
                curl_error($this->_conn)
            );
            return false;
        }

        if ($this->is_verbose) {
            $this->_dump("RECV: {$response}");
        }

        if (400 <= $this->status_code) {
            return false;
        }

        $this->_response =
            call_user_func($this->_response_parser, $response);
        if (!$this->_response) {
            $this->_response = array();
            $this->_dump("Bad response: {$response}");
            return false;
        } else {
            return true;
        }
    }


    /**
     * Cleans up after session, closes opened cURL connection
     */
    public function close()
    {
        if (is_resource($this->_conn)) {
            curl_close($this->_conn);
        }
        $this->_conn = null;
        $this->_response = array();
        return $this;
    }

    /**
     * Initializes the client's properties, sets up cURL connection
     */
    public function init()
    {
        $this->close();

        $this->_conn = curl_init();
        curl_setopt_array($this->_conn, array(
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER    => false,
            CURLOPT_HTTPHEADER     => array(
                'Accept: ' . $this->_response_type,
                'Expect: ',
                'User-Agent: ' . self::API_USER_AGENT . '; v' . self::API_VERSION
            )
        ));

        return $this;
    }

    /**
     * Checks runtime environment
     *
     * @throws RuntimeException When required extensions or functions not found
     */
    public function __construct()
    {
        $k = 'curl';
        if (!extension_loaded($k) && !dl($k)) {
            throw new RuntimeException("{$k} extension not found");
        }
        if (!function_exists(self::HASH_FUNC)) {
            throw new RuntimeException(
                'Function ' . self::HASH_FUNC . '() not found'
            );
        }
        if (function_exists('json_decode')) {
            $this->_response_type = self::API_RESPONSE_JSON;
            $this->_response_parser = array($this, 'parse_json_response');
        } else {
            $this->_response_type = self::API_RESPONSE_PLAIN;
            $this->_response_parser = array($this, 'parse_plain_response');
        }
        $this->init();
    }

    /**
     * @ignore
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
        case 'status_code':
            return is_resource($this->_conn)
                ? curl_getinfo($this->_conn, CURLINFO_HTTP_CODE)
                : null;

        case 'balance':
            return $this->get_balance();

        case 'last_response':
            $key = 'response';
            // Fall through
        case 'response':
            return $this->{"_{$key}"};

        case 'is_logged_in':
            return !empty($this->_userpass);

        default:
            return isset($this->_response[$key])
                ? $this->_response[$key]
                : null;
        }
    }

    /**
     * Logs into API server
     *
     * @param string $username
     * @param string $pass
     * @return bool
     * @throws InvalidArgumentException When credentials are missing
     */
    public function login($username, $pass)
    {
        if (!$username || !$pass) {
            throw new InvalidArgumentException('Missing credentials');
        }
        $this->init();
        $this->_userpass = array($username, $pass);
        return true;
    }

    /**
     * Logs out of API service
     */
    public function logout()
    {
        $this->_userpass = array();
        return $this;
    }

    /**
     * Returns current user's balance (in US cents)
     *
     * @return float|false
     */
    public function get_balance()
    {
        $k = 'balance';
        return ($this->_call('user', null, true) && isset($this->_response[$k]))
            ? (float)$this->_response[$k]
            : false;
    }

    /**
     * Uploads a CAPTCHA image
     *
     * @param string $fn CAPTCHA image file name
     * @return int|false Uploaded CAPTCHA ID on success
     * @throws InvalidArgumentException When image file is not found or unreadable
     * @throws LengthException When image file is empty or bigger than {@link ::MAX_CAPTCHA_FILESIZE}
     */
    public function upload($fn)
    {
        if (!$fn || !is_file($fn) || !is_readable($fn)) {
            throw new InvalidArgumentException(
                'CAPTCHA image file is not found or unreadable'
            );
        } else if (!filesize($fn)) {
            throw new LengthException(
                'CAPTCHA image file is empty'
            );
        } else if (self::MAX_CAPTCHA_FILESIZE <= filesize($fn)) {
            throw new LengthException(
                'CAPTCHA image file is too big'
            );
        }

        if (!$this->_call('captcha', array('captchafile' => "@{$fn}"), true)) {
            return false;
        } else {
            return ($id = (int)@$this->_response['captcha'])
                ? $id
                : false;
        }
    }

    /**
     * Polls for uploaded CAPTCHA status and return the text if decoded
     *
     * @param int $id      CAPTCHA id
     * @param int $timeout CAPTCHA timeout (in seconds), 0 for indefinite
     * @return string|false
     */
    public function get_text($id, $timeout=self::DEFAULT_TIMEOUT)
    {
        $id = max(0, (int)$id);
        if (!$id) {
            return false;
        } else if ($id != (int)@$this->_response['captcha']) {
            $this->_response['text'] = null;
        }

        $method = "captcha/{$id}";
        $timeout = max(2 * self::POLLS_PERIOD, (int)$timeout);
        $deadline = time() + $timeout;
        $attempt = 0;
        while (($deadline > time()) && !$this->_response['text']) {
            $attempt++;
            sleep((1 == ($attempt % self::POLLS_COUNT))
                ? self::POLLS_PERIOD
                : self::POLLS_INTERVAL);
            if (!$this->_call($method)) {
                break;
            }
        }
        return !empty($this->_response['text'])
            ? $this->_response['text']
            : false;
    }

    /**
     * Reports whether the CAPTCHA was correctly decoded.
     * You don't need to report when CAPTCHA was decoded correctly though.
     *
     * @param int $id CAPTCHA id
     * @param bool $is_correct
     * @return bool
     */
    public function report($id, $is_correct=false)
    {
        $id = max(0, (int)$id);
        return $this->is_logged_in && $id && ($is_correct || (
            $this->_call("captcha/{$id}/report", null, true) &&
            (bool)@$this->_response['is_bad']
        ));
    }

    /**
     * Removes previously uploaded but undecoded CAPTCHA
     *
     * @param int $id CAPTCHA id
     * @return bool
     */
    public function remove($id)
    {
        $id = max(0, (int)$id);
        return $id && $this->_call("captcha/{$id}/remove", null, true);
    }

    /**
     * Runs the typical sequence of actions:
     * 1) upload a CAPTCHA image file,
     * 2) poll for decoded text,
     * 3) remove the uploaded CAPTCHA if not decoded
     *
     * @param string $fn CAPTCHA image file name or URL
     * @param int $timeout CAPTCHA timeout (in seconds), 0 if indefinite
     * @return array|false (ID, text) tuple if solved
     */
    public function decode($fn, $timeout=self::DEFAULT_TIMEOUT)
    {
        if ($id = $this->upload($fn)) {
            $text = $this->get_text($id, $timeout);
            if ($text) {
                return array($id, $text);
            }
            $this->remove($id);
        }
        return false;
    }
}
