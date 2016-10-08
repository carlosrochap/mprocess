<?php
/**
 * @package DBCAPI
 */

class DeathByCaptcha_Exception extends Exception
{}


class DeathByCaptcha_RuntimeException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_ServerException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_ClientException extends DeathByCaptcha_Exception
{}


class DeathByCaptcha_InvalidAccountException extends DeathByCaptcha_ClientException
{}


class DeathByCaptcha_InvalidCaptchaException extends DeathByCaptcha_ClientException
{}


/**
 * Death by Captcha API Client
 *
 * @property string $username DBC account username
 * @property-write string $password DBC account password (stored and transferred hashed)
 * @property-read mixed ... Any last response field
 *
 * @package DBCAPI
 * @subpackage PHP
 */
class DeathByCaptcha_Client
{
    const API_VERSION        = 'DBC/PHP v3.0';
    const SOFTWARE_VENDOR_ID = 0;

    const MAX_CAPTCHA_FILESIZE = 131072;

    const POLLS_COUNT    = 4;
    const POLLS_PERIOD   = 15;
    const POLLS_INTERVAL = 5;

    const DEFAULT_TIMEOUT = 60;


    protected $_api_host = 'deathbycaptcha.com';
    protected $_api_ports = array(8123, 8130);

    protected $_username = '';
    protected $_password = '';

    protected $_response = array('status' => 0xff);


    /**
     * Verbosity flag
     *
     * @var bool
     */
    public $is_verbose = false;


    /**
     * Opens a socket connection to the API server if necessary
     *
     * @param string $host API host name
     * @param int $port API server's port
     * @return resource Opened socket
     */
    protected function _connect($host, $port)
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        try {
            if (!$socket) {
                throw new DeathByCaptcha_RuntimeException('Failed creating socket');
            } else if (!@socket_connect(
                $socket,
                gethostbyname($host), $port
            )) {
                throw new DeathByCaptcha_RuntimeException(
                    'Failed connecting to the API server'
                );
            } else if (!@socket_set_nonblock($socket)) {
                throw new DeathByCaptcha_RuntimeException(
                    'Failed making the socket non-blocking'
                );
            }
        } catch (Exception $e) {
            @socket_close($socket);
            $this->close();
            throw $e;
        }
        return $socket;
    }

    /**
     * Dumps a message, variable (serialized) or object string to stderr
     *
     * @param mixed $msg
     */
    protected function _dump($msg)
    {
        if ($this->is_verbose) {
            $m = '__toString';
            fputs(STDERR, (string)posix_getpid() . ' ' . (string)time() . ' ' . (
                (is_object($msg) && method_exists($msg, $m))
                    ? $msg->{$m}()
                    : (is_array($msg)
                          ? serialize($msg)
                          : (string)$msg)
            ) . "\n");
        }
        return $this;
    }

    /**
     * socket_send() wrapper
     *
     * @param resource $socket Socket to use
     * @param array|string $buff
     */
    protected function _send($socket, $buff)
    {
        if (is_array($buff) || is_object($buff)) {
            $buff = json_encode($buff);
        }
        $this->_dump("SEND: {$buff}");
        while ($buff) {
            $wr = array($socket);
            $rd = $ex = null;
            if (!socket_select($rd, $wr, $ex, self::DEFAULT_TIMEOUT) || !count($wr)) {
                break;
            }
            while ($i = @socket_send($wr[0], $buff, 4096, 0)) {
                $buff = substr($buff, $i);
            }
        }
        if ($buff) {
            @socket_close($socket);
            $this->close();
            throw new DeathByCaptcha_RuntimeException(
                'Connection lost while sending a data'
            );
        }
        return $this;
    }

    /**
     * socket_read() wrapper
     *
     * @param resource $socket Socket to use
     * @return array|false
     */
    protected function _recv($socket, $timeout=self::DEFAULT_TIMEOUT)
    {
        $response = $buff = '';
        $deadline = time() + $timeout;
        while (!$response && ($deadline > time())) {
            $s = null;
            $rd = array($socket);
            $wr = $ex = null;
            if (!socket_select($rd, $wr, $ex, $timeout) || !count($rd)) {
                break;
            } else if (@socket_recv($rd[0], $s, 4096, 0)) {
                $buff .= $s;
                $response = json_decode(trim($buff), true);
            } else if (null === $s) {
                break;
                sleep(1);
            }
        }
        $this->_dump("RECV: " . serialize($response));
        if (!is_array($response) || !isset($response['status'])) {
            socket_close($socket);
            $this->close();
            throw new DeathByCaptcha_RuntimeException(($deadline > time())
                ? 'Connection lost while reading a response'
                : 'Connection timed out');
        } else {
            return $response;
        }
    }

    /**
     * Calls an API command
     *
     * @param string $cmd API command to call
     * @param array $args API command arguments
     * @return array|null API response hash table on success, null otherwise
     */
    protected function _call($cmd, array $args=array(), $timeout=self::DEFAULT_TIMEOUT)
    {
        $socket = $this->init()->_connect(
            $this->_api_host,
            $this->_api_ports[array_rand($this->_api_ports)]
        );
        $this->_response = $this->_send($socket, array_merge($args, array(
            'cmd'       => &$cmd,
            'version'   => self::API_VERSION,
            'swid'      => self::SOFTWARE_VENDOR_ID,
        ), (('get_text' == $cmd) ? array() : array(
            'username'  => &$this->_username,
            'password'  => &$this->_password,
            'is_hashed' => true,
        ))))->_recv($socket, $timeout);
        @socket_close($socket);
        if ((0x01 <= $this->status) && (0x10 > $this->status)) {
            throw new DeathByCaptcha_InvalidAccountException(
                'Login failed, check your credentials, banned status and/or balance'
            );
        } else if ((0x10 <= $this->status) && (0x20 > $this->status)) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                'Failed uploading/fetching CAPTCHA, check its ID'
            );
        } else if (0x00 != $this->status) {
            throw new DeathByCaptcha_ServerException('Server error occured');
        }
        return $this->_response;
    }


    /**
     * Performs post-session cleanup
     */
    public function close()
    {
        $this->_response = array('status' => 0xff);
        return $this;
    }

    /**
     * Initializes the client
     */
    public function init()
    {
        $this->close();
        return $this;
    }

    /**
     * Checks runtime environment for required extensions/function
     */
    public function __construct($username, $password)
    {
        $this->_api_ports = range($this->_api_ports[0], $this->_api_ports[1]);
        foreach (array('json', ) as $k) {
            if (!extension_loaded($k) && !@dl($k)) {
                throw new DeathByCaptcha_RuntimeException(
                    "Required {$k} extension not found, check your PHP configuration"
                );
            }
        }
        foreach (array('sha1', 'base64_encode') as $k) {
            if (!function_exists($k)) {
                throw new DeathByCaptcha_RuntimeException(
                    "Required {$k}() function not found, check your PHP configuration"
                );
            }
        }
        foreach (array('username', 'password') as $k) {
            if (!$$k) {
                throw new DeathByCaptcha_RuntimeException(
                    "Account {$k} is missing or empty"
                );
            } else {
                $this->{$k} = $$k;
            }
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
        case 'username':
            return $this->{"_{$key}"};

        case 'balance':
            return $this->get_balance();

        case 'error':
        case 'errno':
            $key = 'status';
            // Fall through
        default:
            return ($this->_response && isset($this->_response[$key]))
                ? $this->_response[$key]
                : null;
        }
    }

    /**
     * @ignore
     */
    public function __set($key, $value)
    {
        switch ($key) {
        case 'password':
            $value = sha1($value);
            // Fall through
        case 'username':
            $this->{"_{$key}"} = (string)$value;
            break;
        }
    }

    /**
     * Uploads a CAPTCHA
     *
     * @param string $filename CAPTCHA image file name
     * @return int|false Uploaded CAPTCHA ID on success
     */
    public function upload($filename)
    {
        if (!$filename || !is_file($filename) || !is_readable($filename)) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                "{$filename} not found or unreadable"
            );
        } else if (!filesize($filename)) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                "{$filename} is empty"
            );
        } else if (self::MAX_CAPTCHA_FILESIZE <= filesize($filename)) {
            throw new DeathByCaptcha_InvalidCaptchaException(
                "{$filename} is too big"
            );
        }
        $this->_call('upload', array(
            'captcha' => base64_encode(file_get_contents($filename)),
        ));
        return $this->captcha
            ? (int)$this->captcha
            : false;
    }

    /**
     * Fetches user's balance
     *
     * @return float|false
     */
    public function get_balance()
    {
        $this->_call('get_user');
        return (float)$this->balance;
    }

    /**
     * Retrieves a CAPTCHA text
     *
     * @param int $id CAPTCHA ID
     * @return string|false
     */
    public function get_text($id, $timeout=self::DEFAULT_TIMEOUT)
    {
        $cmd_args = array('captcha' => (int)$id);
        $attempt = 0;
        $timeout = max(2 * self::POLLS_PERIOD, (int)$timeout);
        $deadline = time() + $timeout;
        while (($deadline > time()) && !$this->text) {
            $attempt++;
            sleep((1 == ($attempt % self::POLLS_COUNT))
                ? self::POLLS_PERIOD
                : self::POLLS_INTERVAL);
            $this->_call('get_text', $cmd_args);
        }
        return $this->text
            ? $this->text
            : false;
    }

    /**
     * Reports the CAPTCHA as incorrectly solved
     * (you don't have to report correctly solved CAPTCHA)
     *
     * @param int $id CAPTCHA ID
     * @return bool
     */
    public function report($id)
    {
        $this->_call('report', array('captcha' => (int)$id));
        return !$this->_response['is_correct'];
    }

    /**
     * Removes an unsolved CAPTCHA
     *
     * @param int $id CAPTCHA ID
     * @return bool
     */
    public function remove($id)
    {
        $this->_call('remove', array('captcha' => (int)$id));
        return !$this->_response['captcha'];
    }

    /**
     * Uploads a CAPTCHA image
     *
     * @param string $filename CAPTCHA image file name
     * @return array|false Uploaded CAPTCHA (ID, text) tuple if solved
     */
    public function decode($filename, $timeout=self::DEFAULT_TIMEOUT)
    {
        if ($id = $this->upload($filename)) {
            $text = $this->get_text($id, $timeout);
            if ($text) {
                return array($id, $text);
            }
            $this->remove($id);
        }
        return false;
    }
}
