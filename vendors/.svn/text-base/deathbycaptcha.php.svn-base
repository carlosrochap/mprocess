<?php
/**
 * @package DBCAPI
 */

class DeathByCaptcha_Exception extends Exception
{}


class DeathByCaptcha_RuntimeException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_IOException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_ServerException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_ClientException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_AccessDeniedException extends DeathByCaptcha_ClientException
{}


class DeathByCaptcha_InvalidCaptchaException extends DeathByCaptcha_ClientException
{}


/**
 * Death by Captcha API base client
 *
 * @property-read array|null $user    User's details
 * @property-read float|null $balance User's balance (in US cents)
 *
 * @package DBCAPI
 * @subpackage PHP
 */
abstract class DeathByCaptcha_Client
{
    const API_VERSION        = 'DBC/PHP v4.0.4';
    const SOFTWARE_VENDOR_ID = 0;

    const MAX_CAPTCHA_FILESIZE = 65535;

    const DEFAULT_TIMEOUT = 120;
    const POLLS_INTERVAL  = 15;


    /**
     * DBC credentials
     *
     * @var array
     */
    protected $_userpwd = array();


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
        return json_decode(rtrim($s), true);
    }


    /**
     * Checks if CAPTCHA is valid
     *
     * @param string $captcha CAPTCHA image file name
     */
    protected function _check_captcha($captcha)
    {
        if (0 >= ($size = filesize($captcha))) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                'CAPTCHA image file is empty'
            );
        } else if (self::MAX_CAPTCHA_FILESIZE <= $size) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                'CAPTCHA image file is too big'
            );
        } else {
            return $captcha;
        }
    }


    /**
     * Connects to DBC API server
     *
     * @return DeathByCaptcha_Client
     */
    abstract public function connect();

    /**
     * Closes opened connection (if any), as gracefully as possible
     *
     * @return DeathByCaptcha_Client
     */
    abstract public function close();

    /**
     * Returns user details
     *
     * @return array|null
     */
    abstract public function get_user();

    /**
     * Returns user's balance (in US cents)
     *
     * @return float|null
     */
    public function get_balance()
    {
        return ($user = $this->get_user()) ? $user['balance'] : null;
    }

    /**
     * Returns CAPTCHA details
     *
     * @param int $cid CAPTCHA ID
     * @return array|null
     */
    abstract public function get_captcha($cid);

    /**
     * Returns CAPTCHA text
     *
     * @param int $cid CAPTCHA ID
     * @return string|null
     */
    public function get_text($cid)
    {
        return ($captcha = $this->get_captcha($cid)) ? $captcha['text'] : null;
    }

    /**
     * Reports an incorrectly solved CAPTCHA
     *
     * @param int $cid CAPTCHA ID
     * @return bool
     */
    abstract public function report($cid);

    /**
     * Removes an unsolved CAPTCHA
     *
     * @param int $cid CAPTCHA ID
     * @return bool
     */
    abstract public function remove($cid);

    /**
     * Uploads a CAPTCHA
     *
     * @param string|resource $captcha CAPTCHA image file name or file handle
     * @param bool $is_case_sensitive Optional flag telling whether the CAPTCHA is case-sensitive or not
     * @return array|null Uploaded CAPTCHA details on success
     * @throws DeathByCaptcha_InvalidCaptchaException On invalid CAPTCHA file
     */
    abstract public function upload($captcha, $is_case_sensitive=false);

    /**
     * Tries to solve CAPTCHA by uploading it and polling for its status/text
     * with arbitrary timeout.
     *
     * @see DeathByCaptcha_Client::upload()
     * @param int $timeout Optional solving timeout (in seconds)
     * @return array|null CAPTCHA details hash on success
     */
    public function decode($captcha, $timeout=self::DEFAULT_TIMEOUT, $is_case_sensitive=false)
    {
        $deadline = time() + (0 < $timeout ? $timeout : self::DEFAULT_TIMEOUT);
        if ($c = $this->upload($captcha, $is_case_sensitive)) {
            while ($deadline > time() && $c && !$c['text']) {
                sleep(self::POLLS_INTERVAL);
                $c = $this->get_captcha($c['captcha']);
            }
            if ($c) {
                if ($c['text']) {
                    if ($c['is_correct']) {
                        return $c;
                    }
                } else {
                    $this->remove($c['captcha']);
                }
            }
        }
        return null;
    }

    /**
     * @param string $username DBC account username
     * @param string $password DBC account password
     * @throws DeathByCaptcha_RuntimeException On missing or empty DBC credentials
     */
    public function __construct($username, $password)
    {
        foreach (array('username', 'password') as $k) {
            if (!$$k) {
                throw new DeathByCaptcha_RuntimeException(
                    "Account {$k} is missing or empty"
                );
            }
        }
        $this->_userpwd = array($username, $password);
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
        case 'user':
            return $this->get_user();
        case 'balance':
            return $this->get_balance();
        }
    }
}


/**
 * Death by Captcha HTTP API Client
 *
 * @see DeathByCaptcha_Client
 * @package DBCAPI
 * @subpackage PHP
 */
class DeathByCaptcha_HttpClient extends DeathByCaptcha_Client
{
    const BASE_URL = 'http://api.deathbycaptcha.com/api';


    protected $_conn = null;
    protected $_response_type = '';
    protected $_response_parser = null;


    /**
     * Makes an API call
     *
     * @param string $cmd     API command
     * @param array  $payload API call payload, essentially HTTP POST fields
     * @return array|null API response hash table on success
     * @throws DeathByCaptcha_IOException On network related errors
     * @throws DeathByCaptcha_AccessDeniedException On failed login attempt
     */
    protected function _call($cmd, $payload=null)
    {
        if (null !== $payload) {
            $payload = array_merge($payload,
                                   array('username' => $this->_userpwd[0],
                                         'password' => $this->_userpwd[1]));
        }

        $this->connect();

        $opts = array(CURLOPT_URL => self::BASE_URL . '/' . trim($cmd, '/'),
                      CURLOPT_REFERER => '');
        if (null !== $payload) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = array_key_exists('captchafile', $payload)
                ? $payload
                : http_build_query($payload);
        } else {
            $opts[CURLOPT_HTTPGET] = true;
        }
        curl_setopt_array($this->_conn, $opts);

        if ($this->is_verbose) {
            echo time() . " SEND: {$cmd} " . serialize($payload) . "\n";
        }

        if (!$response = curl_exec($this->_conn)) {
            throw new DeathByCaptcha_IOException('Connection timed out');
        } else if (curl_error($this->_conn)) {
            throw new DeathByCaptcha_IOException(
                'Connection failed: ' .
                curl_errno($this->_conn) . ' ' .
                curl_error($this->_conn)
            );
        }

        if ($this->is_verbose) {
            echo time() . " RECV: {$response}\n";
        }

        $status_code = curl_getinfo($this->_conn, CURLINFO_HTTP_CODE);
        if (403 == $status_code) {
            throw new DeathByCaptcha_AccessDeniedException(
                'Access denied, check your credentials and/or balance'
            );
        } else if (!$response = call_user_func($this->_response_parser, $response)) {
            throw new DeathByCaptcha_ServerException(
                'Invalid API response'
            );
        } else {
            return $response;
        }
    }

    /**
     * Checks runtime environment
     *
     * @see DeathByCaptcha_Client::__construct()
     * @throws DeathByCaptcha_RuntimeException When required extensions or functions not found
     */
    public function __construct($username, $password)
    {
        if (!extension_loaded('curl')) {
            throw new DeathByCaptcha_RuntimeException(
                'CURL extension not found'
            );
        }
        if (function_exists('json_decode')) {
            $this->_response_type = 'application/json';
            $this->_response_parser = array($this, 'parse_json_response');
        } else {
            $this->_response_type = 'text/plain';
            $this->_response_parser = array($this, 'parse_plain_response');
        }
        parent::__construct($username, $password);
    }

    /**
     * Sets up CURL connection
     */
    public function connect()
    {
        if (!is_resource($this->_conn)) {
            if ($this->is_verbose) {
                echo time() . " CONN\n";
            }

            if (!$this->_conn = curl_init()) {
                throw new DeathByCaptcha_RuntimeException(
                    'Failed initializing a CURL connection'
                );
            }

            curl_setopt_array($this->_conn, array(
                CURLOPT_TIMEOUT        => self::DEFAULT_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => (int)(self::DEFAULT_TIMEOUT / 4),
                CURLOPT_HEADER         => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER    => false,
                CURLOPT_HTTPHEADER     => array(
                    'Accept: ' . $this->_response_type,
                    'Expect: ',
                    'User-Agent: ' . self::API_VERSION
                )
            ));
        }
        return $this;
    }

    /**
     * Closes an opened CURL connection
     */
    public function close()
    {
        if (is_resource($this->_conn)) {
            if ($this->is_verbose) {
                echo time() . " CLOSE\n";
            }
            curl_close($this->_conn);
            $this->_conn = null;
        }
        return $this;
    }

    /**
     * @see DeathByCaptcha_Client::get_user()
     */
    public function get_user()
    {
        $user = $this->_call('user', array());
        return (0 < ($id = (int)@$user['user']))
            ? array('user'      => $id,
                    'balance'   => (float)@$user['balance'],
                    'is_banned' => (bool)@$user['is_banned'])
            : null;
    }

    /**
     * @see DeathByCaptcha_Client::upload()
     */
    public function upload($captcha, $is_case_sensitive=false)
    {
        $tmp_fn = null;

        if (is_resource($captcha)) {
            $tmp_fn = tempnam(null, 'captcha');
            if (!$tmp_f = fopen($tmp_fn, 'wb')) {
                throw new DeathByCaptcha_RuntimeException(
                    "Failed creating a temporary CAPTCHA file"
                );
            }

            rewind($captcha);
            try {
                while ($s = fread($captcha, 8192)) {
                    while ($s) {
                        if (false === ($n = fwrite($tmp_f, $s, 8192))) {
                            throw new DeathByCaptcha_RuntimeException(
                                "Failed saving temporary CAPTCHA file"
                            );
                        } else if ($n) {
                            $s = substr($s, $n);
                        }
                    }
                }
            } catch (Exception $e) {
                fclose($tmp_f);
                throw $e;
            }
            fclose($tmp_f);
            $captcha = &$tmp_fn;
        }

        try {
            $captcha = $this->_call('captcha', array(
                'swid'              => self::SOFTWARE_VENDOR_ID,
                'captchafile'       => '@' . $this->_check_captcha($captcha),
                'is_case_sensitive' => (int)(bool)$is_case_sensitive,
            ));
            if ($tmp_fn) {
                @unlink($tmp_fn);
            }
        } catch (Exception $e) {
            if ($tmp_fn) {
                @unlink($tmp_fn);
            }
            throw $e;
        }

        return (0 < ($cid = (int)@$captcha['captcha']))
            ? array('captcha'    => $cid,
                    'text'       => (!empty($captcha['text']) ? $captcha['text'] : null),
                    'is_correct' => (bool)@$captcha['is_correct'])
            : null;
    }

    /**
     * @see DeathByCaptcha_Client::get_captcha()
     */
    public function get_captcha($cid)
    {
        $captcha = $this->_call('captcha/' . (int)$cid);
        return (0 < ($cid = (int)@$captcha['captcha']))
            ? array('captcha'    => $cid,
                    'text'       => (!empty($captcha['text']) ? $captcha['text'] : null),
                    'is_correct' => (bool)$captcha['is_correct'])
            : null;
    }

    /**
     * @see DeathByCaptcha_Client::report()
     */
    public function report($cid)
    {
        $captcha = $this->_call('captcha/' . (int)$cid . '/report', array());
        return !(bool)@$captcha['is_correct'];
    }

    /**
     * @see DeathByCaptcha_Client::remove()
     */
    public function remove($cid)
    {
        $captcha = $this->_call('captcha/' . (int)$cid . '/remove', array());
        return !(int)@$captcha['captcha'];
    }
}


/**
 * Death by Captcha socket API Client
 *
 * @see DeathByCaptcha_Client
 * @package DBCAPI
 * @subpackage PHP
 */
class DeathByCaptcha_SocketClient extends DeathByCaptcha_Client
{
    const HOST       = 'api.deathbycaptcha.com';
    const FIRST_PORT = 8123;
    const LAST_PORT  = 8130;


    protected $_sock = null;


    /**
     * socket_send() wrapper
     *
     * @param string $buf Raw API request to send
     */
    protected function _send($buf)
    {
        $buf .= "\n";
        $deadline = time() + 3 * self::POLLS_INTERVAL;
        while ($deadline > time() && $buf) {
            $rd = array();
            $wr = array($this->_sock);
            $ex = array($this->_sock);
            if (!socket_select($rd, $wr, $ex, self::POLLS_INTERVAL)) {
                // select() timed out
            } else if (count($ex)) {
                // select() failed
                break;
            } else if (count($wr)) {
                while ($buf && 0 < ($i = @socket_send($wr[0], $buf, 4096, 0))) {
                    $buf = substr($buf, $i);
                }
            }
        }
        return $this;
    }

    /**
     * socket_recv() wrapper
     *
     * @return string Raw API response on success, null if failed
     */
    protected function _recv()
    {
        $buf = '';
        $deadline = time() + 3 * self::POLLS_INTERVAL;
        while ($deadline > time()) {
            $rd = array($this->_sock);
            $wr = array();
            $ex = array($this->_sock);
            if (!socket_select($rd, $wr, $ex, self::POLLS_INTERVAL)) {
                // select() timed out
            } else if (count($ex)) {
                // select() failed
                break;
            } else if (count($rd)) {
                $s = null;
                while (0 < ($i = @socket_recv($rd[0], $s, 256, 0))) {
                    $buf .= $s;
                    if ("\n" == $s[$i - 1]) {
                        return rtrim($buf, "\n");
                    } else {
                        $s = null;
                    }
                }
                if (!$buf) {
                    break;
                }
            }
        }
        return null;
    }


    /**
     * Makes an API call
     *
     * @param string $cmd     API command to call
     * @param array  $payload API request payload
     * @return array|null API response hash map on success
     * @throws DeathByCaptcha_IOException On network errors
     * @throws DeathByCaptcha_AccessDeniedException On failed login attempt
     * @throws DeathByCaptcha_ServerException On API server errors
     */
    protected function _call($cmd, $payload=null)
    {
        if (null === $payload) {
            $payload = array();
        }
        $payload = array_merge($payload,
                               array('cmd' => $cmd,
                                     'version' => self::API_VERSION));
        if ('captcha' != $cmd && 'ping' != $cmd) {
            list($payload['username'], $payload['password']) = $this->_userpwd;
        }
        $payload = json_encode($payload);
        if ($this->is_verbose) {
            echo time() . ' SEND: ' . strlen($payload) . " {$payload}\n";
        }

        $response = null;
        for ($i = 2; $i && !$response; $i--) {
            if (!($response = $this->connect()->_send($payload)->_recv())) {
                $this->close();
            }
        }
        if ($this->is_verbose) {
            echo time() . ' RECV: ' . strlen($response) . " {$response}\n";
        }
        try {
            if (!$response) {
                throw new DeathByCaptcha_IOException(
                    'Connection lost or timed out'
                );
            } else if (!$response = $this->parse_json_response($response)) {
                throw new DeathByCaptcha_ServerException(
                    'Invalid API response'
                );
            }
            $status = isset($response['status'])
                ? $response['status']
                : 0xff;
            if (0x00 < $status && 0x10 > $status) {
                throw new DeathByCaptcha_AccessDeniedException(
                    'Access denied, check your credentials and/or balance'
                );
            } else if (0xff == $status) {
                throw new DeathByCaptcha_ServerException(
                    'API server error occured'
                );
            }
        } catch (Exception $e) {
            $this->close();
            throw $e;
        }
        return $response;
    }


    /**
     * @see DeathByCaptcha_Client::__construct()
     * @throws DeathByCaptcha_RuntimeException When run in unsuitable environment
     */
    public function __construct($username, $password)
    {
        foreach (array('json', ) as $k) {
            if (!extension_loaded($k)) {
                throw new DeathByCaptcha_RuntimeException(
                    "Required {$k} extension not found, check your PHP configuration"
                );
            }
        }
        foreach (array('json_encode', 'json_decode', 'base64_encode') as $k) {
            if (!function_exists($k)) {
                throw new DeathByCaptcha_RuntimeException(
                    "Required {$k}() function not found, check your PHP configuration"
                );
            }
        }
        parent::__construct($username, $password);
    }

    /**
     * Opens a socket connection to the API server
     *
     * @throws DeathByCaptcha_IOException When API connection fails
     * @throws DeathByCaptcha_RuntimeException When socket operations fail
     */
    public function connect()
    {
        if (!$this->_sock) {
            if ($this->is_verbose) {
                echo time() . " CONN\n";
            }

            if (!($sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
                throw new DeathByCaptcha_RuntimeException(
                    'Failed creating a socket'
                );
            } else if (!@socket_set_nonblock($sock)) {
                @socket_close($sock);
                throw new DeathByCaptcha_RuntimeException(
                    'Failed making the socket non-blocking'
                );
            }

            $port = rand(self::FIRST_PORT, self::LAST_PORT);
            if (!@socket_connect($sock, gethostbyname(self::HOST), $port)) {
                $err = socket_last_error();
                if (SOCKET_EINPROGRESS != $err && SOCKET_EALREADY != $err) {
                    @socket_close($sock);
                    throw new DeathByCaptcha_IOException(
                        'Failed connecting to ' . self::HOST . ':' . $port
                    );
                }
            }

            $this->_sock = $sock;
        }

        return $this;
    }

    /**
     * Closes opened socket
     */
    public function close()
    {
        if ($this->_sock) {
            if ($this->is_verbose) {
                echo time() . " CLOSE\n";
            }

            $this->_send(json_encode(array('cmd' => 'quit')));

            @socket_shutdown($this->_sock, 2);
            @socket_close($this->_sock);
            $this->_sock = null;
        }
        return $this;
    }

    /**
     * @see DeathByCaptcha_Client::get_user()
     */
    public function get_user()
    {
        $user = $this->_call('user');
        return (0 < ($id = (int)@$user['user']))
            ? array('user'      => $id,
                    'balance'   => (float)@$user['balance'],
                    'is_banned' => (bool)@$user['is_banned'])
            : null;
    }

    /**
     * @see DeathByCaptcha_Client::get_user()
     */
    public function upload($captcha, $is_case_sensitive=false)
    {
        $raw_captcha = '';
        if (is_resource($captcha)) {
            $pos = ftell($captcha);
            rewind($captcha);
            while ($s = fread($captcha, 8192)) {
                $raw_captcha .= $s;
            }
            fseek($captcha, $pos, SEEK_SET);
        } else if (!$captcha || !is_file($captcha) || !is_readable($captcha)) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                "CAPTCHA image file {$captcha} not found or unreadable"
            );
        } else {
            $raw_captcha = file_get_contents($captcha);
        }

        if (0 >= ($size = strlen($raw_captcha))) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                'CAPTCHA image is empty'
            );
        } else if (self::MAX_CAPTCHA_FILESIZE <= $size) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                'CAPTCHA image is too big'
            );
        }

        $captcha = $this->_call('upload', array(
            'swid'              => self::SOFTWARE_VENDOR_ID,
            'captcha'           => base64_encode($raw_captcha),
            'is_case_sensitive' => (bool)$is_case_sensitive,
        ));
        return (0 < ($cid = (int)@$captcha['captcha']))
            ? array('captcha'    => $cid,
                    'text'       => (!empty($captcha['text']) ? $captcha['text'] : null),
                    'is_correct' => (bool)@$captcha['is_correct'])
            : null;
    }

    /**
     * @see DeathByCaptcha_Client::get_captcha()
     */
    public function get_captcha($cid)
    {
        $captcha = $this->_call('captcha', array('captcha' => (int)$cid));
        return (0 < ($cid = (int)@$captcha['captcha']))
            ? array('captcha'    => $cid,
                    'text'       => (!empty($captcha['text']) ? $captcha['text'] : null),
                    'is_correct' => (bool)$captcha['is_correct'])
            : null;
    }

    /**
     * @see DeathByCaptcha_Client::report()
     */
    public function report($cid)
    {
        $captcha = $this->_call('report', array('captcha' => (int)$cid));
        return !@$captcha['is_correct'];
    }

    /**
     * @see DeathByCaptcha_Client::remove()
     */
    public function remove($cid)
    {
        $captcha = $this->_call('remove', array('captcha' => (int)$cid));
        return !@$captcha['captcha'];
    }
}
