<?php
/**
 * @package Actor
 */

/**
 * Base class for actors who use HTTP
 *
 * @method string get()      See {@link Connection_Curl::get()}
 * @method string post()     See {@link Connection_Curl::post()}
 * @method string ajax()     See {@link Connection_Curl::ajax()}
 * @method string request()  See {@link Connection_Curl::request()}
 * @method bool   download() See {@link Connection_Curl::download()}
 * @method string submit()   See {@link Html_Form::submit()}
 * @property Connection_Interface $connection Connection used by the actor
 * @property Connection_Proxy     $proxy      Proxy used by the connection
 * @property mixed                $user_id    Project specific user ID
 * @property-read string    $response Last response body
 * @property-read Html_Form $form     Last used HTML form
 *
 * @package Actor
 * @subpackage Http
 */
abstract class Actor_Http_Abstract
    extends Actor_Abstract
    implements Actor_Interface
{
    const RECAPTCHA_HOST        = 'http://api.recaptcha.net';
    const RECAPTCHA_SECURE_HOST = 'https://api-secure.recaptcha.net';

    const RECAPTCHA_CHALLENGE_URL = '/challenge';
    const RECAPTCHA_IMAGE_URL     = '/image';
    const RECAPTCHA_RELOAD_URL    = '/reload';


    protected $_dump_prefix = '';

    /**
     * Connection to use
     *
     * @var Connection_Interface
     */
    protected $_connection = null;

    /**
     * Last response body
     *
     * @var string
     */
    protected $_response = null;

    /**
     * Last used HTML form
     *
     * @var Html_Form
     */
    protected $_form = null;

    /**
     * Logged in project specific user ID
     *
     * @var mixed
     */
    protected $_user_id = null;


    /**
     * Dumps last response into a file for debug purposes, works only when
     * appropriate verbosity level is set
     *
     * @param string $fn      Dump file name
     * @param mixed  $content Optional content to dump, defaults to
     *                        {@link ::$_reponse}
     */
    protected function _dump($fn, $content=null)
    {
        if ($this->is_verbose) {
            if (!$this->_dump_prefix) {
                $a = explode('_', get_class($this), 3);
                $this->_dump_prefix = strtolower($a[1]) . '.';
                if ($this instanceof Actor_Interface_Registrator) {
                    $this->_dump_prefix .= 'reg';
                } else if ($this instanceof Actor_Interface_Confirmer) {
                    $this->_dump_prefix .= 'confirm';
                } else if ($this instanceof Actor_Interface_Messenger) {
                    $this->_dump_prefix .= 'msg';
                } else if ($this instanceof Actor_Interface_Searcher) {
                    $this->_dump_prefix .= 'search';
                } else if ($this instanceof Actor_Interface_Grabber) {
                    $this->_dump_prefix .= 'grab';
                } else if (!empty($a[2])) {
                    $this->_dump_prefix .= strtr(strtolower($a[2]), '_', '.');
                }
            }
            file_put_contents(implode('.', array(
                'log' . DIRECTORY_SEPARATOR . Environment::get_pid(),
                time(),
                $this->_dump_prefix,
                basename($fn)
            )), Log_Abstract::prepare((null !== $content)
                ? $content
                : $this->_response));
        }
        return $this;
    }

    /**
     * Fetches an instance of project-specific CAPTCHA decoder
     *
     * @param string $decoder Decoder's name
     * @return Captcha_Decoder_Interface|null
     */
    protected function _get_captcha_decoder($decoder=null)
    {
        if (!$decoder) {
            $config = $this->call_process_method('get_config');
            if ($config) {
                $decoder = @$config['captcha'];
            }
        }
        return $decoder
            ? Captcha_Decoder_Factory::factory($decoder, $this->_log)
            : null;
    }

    protected function _get_email_client($email, $pass, $share_connection=true)
    {
        try {
            $mailer = Email_Factory::factory($email, $this->_log);
        } catch (Email_Exception $e) {
            $this->log($e, Log_Abstract::LEVEL_ERROR);
            return null;
        }

        if ($share_connection) {
            $mailer->proxy = $this->_connection->proxy;
            $mailer->connection = $this->_connection;
        }

        try {
            if ($mailer->login($email, $pass)) {
                return $mailer;
            }
        } catch (Email_Exception $e) {
            if (Email_Exception::INVALID_CREDENTIALS == $e->getCode()) {
                throw new Actor_Exception(
                    'Invalid e-mail service username/password',
                    Actor_Exception::INVALID_CREDENTIALS
                );
            } else {
                $this->log($e, Log_Abstract::LEVEL_ERROR);
            }
        }

        return null;
    }

    /**
     * Decodes an arbitrary CAPTCHA
     *
     * @param string|Url $url CAPTCHA image URL
     * @param string     $ext Optional image file extension
     * @return array|false CAPTCHA ID & text tuple on success
     */
    protected function _decode_captcha($url, $ext='jpg')
    {
        $this->log('Decoding CAPTCHA');
        $this->log($url, Log_Abstract::LEVEL_DEBUG);

        $fn = Environment::get_tmp_file_name('captcha.' . basename($ext));
        if (!$this->_connection->download($url, $fn)) {
            @unlink($fn);
            throw new Actor_Exception(
                'Failed fetching CAPTCHA image',
                Actor_Exception::PROXY_BANNED
            );
        }
        $result = $this->_get_captcha_decoder()->decode($fn);
        @unlink($fn);
        if (!$result) {
            $this->log('Failed decoding CAPTCHA',
                       Log_Abstract::LEVEL_ERROR);
        }
        return $result;
    }

    /**
     * Decodes arbitrary ReCAPTCHA
     *
     * @param string    $key  ReCAPTCHA public key
     * @param Html_Form $form Optional form to put ReCAPTCHA challenge to
     * @param bool      $use_secure_host If true, use secure ReCAPTCHA host
     * @return array|false CAPTCHA ID & text tuple on success
     */
    protected function _decode_recaptcha($key, Html_Form $form=null, $use_secure_host=false)
    {
        if (null === $form) {
            $form = $this->_form;
        }
        if (!$form) {
            $this->log('No forms to put ReCAPTCHA challenge to',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $this->log('Fetching ReCAPTCHA challenge');

        $response = $this->_connection->get(
            ($use_secure_host
                ? self::RECAPTCHA_SECURE_HOST
                : self::RECAPTCHA_HOST) . self::RECAPTCHA_CHALLENGE_URL,
            array('k' => &$key)
        );
        $this->_dump('recaptcha-challenge.js', $response);
        if (!preg_match(
            '#\Wchallenge[\'"]?\s*:\s*[\'"]([^\'"]+)#',
            $response,
            $challenge
        )) {
            throw new Actor_Exception(
                'ReCAPTCHA challenge not found',
                Actor_Exception::PROXY_BANNED
            );
        } else {
            $challenge = stripcslashes($challenge[1]);
            $this->log("Challenge: {$challenge}",
                       Log_Abstract::LEVEL_DEBUG);
        }

        $form['recaptcha_challenge_field'] = $challenge;
        $captcha_url = new Url(($use_secure_host
            ? self::RECAPTCHA_SECURE_HOST
            : self::RECAPTCHA_HOST) . self::RECAPTCHA_IMAGE_URL);
        $captcha_url->query = array('c' => &$challenge);
        $result = $this->_decode_captcha($captcha_url, 'jpg');
        if ($result) {
            $form['recaptcha_response_field'] = $result[1];
        }
        return $result;
    }


    /**
     * @see Actor_Abstract::init()
     */
    public function init()
    {
        $this->close();
        if ($this->_connection) {
            $this->_connection->init();
        }
        return parent::init();
    }

    /**
     * @see Actor_Abstract::close()
     */
    public function close()
    {
        if ($this->_user_id && method_exists($this, 'logout')) {
            $this->logout();
        }
        $this->_response = $this->_form = $this->_user_id = null;
        return parent::close();
    }

    /**
     * Sets a connection to use by the actor
     *
     * @param Connection_Interface $connection
     */
    public function set_connection(Connection_Interface $connection)
    {
        $this->_connection = $connection;
        return $this;
    }

    /**
     * Returns the connection used by the actor
     *
     * @return Connection_Interface
     */
    public function get_connection()
    {
        return $this->_connection;
    }

    /**
     * Sets currenly used proxy
     *
     * @param mixed $proxy URL or Connection_Proxy instance
     */
    public function set_proxy($proxy)
    {
        if ($this->_connection) {
            $this->_connection->proxy = $proxy;
        }
        return $this;
    }

    /**
     * Returns a proxy currenly used by the connection
     *
     * @return Connection_Proxy
     */
    public function get_proxy()
    {
        return $this->_connection
            ? $this->_connection->proxy
            : null;
    }

    /**
     * Finds a specific form in last request's response body
     *
     * @param mixed ... Either numeric form index (zero based), or form search
     *                  params, will be proxied to {@link Html_form::get()}
     * @return Html_Form
     */
    public function get_form()
    {
        $this->_form = null;
        if ($this->_connection) {
            $args = func_get_args();
            array_unshift($args, $this->_response, $this->_connection);
            $this->_form = call_user_func_array(
                array('Html_Form', 'get'),
                $args
            );
        }
        return $this->_form;
    }

    /**
     * Sets currently logged in user's ID
     *
     * @param mixed $user_id
     */
    public function set_user_id($user_id)
    {
        $this->_user_id = $user_id;
        return $this;
    }

    /**
     * Returns currently logged in user's ID
     *
     * @return mixed
     */
    public function get_user_id()
    {
        return $this->_user_id;
    }

    /**
     * Logs out
     */
    public function logout()
    {
        $this->_user_id = null;
        return $this;
    }

    /**
     * Uploads userpic by calling implementation specific uploader if defined
     *
     * @param string $userpic Userpic URL
     * @return bool
     */
    public function upload_userpic($userpic)
    {
        $this->log('Uploading userpic');
        $this->log($userpic, Log_Abstract::LEVEL_DEBUG);

        $uploader = '_upload_userpic';
        if (!method_exists($this, $uploader)) {
            $this->log('Userpic uploader not defined',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }

        $result = false;
        $fn = Environment::get_tmp_file_name('userpic.jpg');
        if (!$this->_connection->download($userpic, $fn)) {
            $this->log('Failed fetching userpic',
                       Log_Abstract::LEVEL_ERROR);
        } else {
            try {
                $result = $this->{$uploader}($fn);
            } catch (Exception $e) {
                $result = $e;
            }
        }
        @unlink($fn);
        if (!$result) {
            $this->log('Failed uploading userpic',
                       Log_Abstract::LEVEL_ERROR);
        } else if (is_object($result) && $result instanceof Exception) {
            throw $result;
        }
        return $result;
    }


    /**
     * @ignore
     */
    public function __get($key)
    {
        $result = parent::__get($key);
        return (null !== $result)
            ? $result
            : ($this->_connection
                ? $this->_connection->{$key}
                : null);
    }

    /**
     * @ignore
     */
    public function __call($method, $args)
    {
        if (!$this->_connection) {
            throw new Actor_Exception(
                'No connections to use',
                Actor_Exception::NETWORK_ERROR
            );
        } else if (!$this->_connection->proxy) {
            throw new Actor_Exception(
                'Proxy is not set',
                Actor_Exception::NETWORK_ERROR
            );
        }

        $this->_response = null;

        $callback = array(null, $method);

        switch ($method) {
        case 'get':
        case 'post':
        case 'ajax':
        case 'request':
        case 'download':
            $callback[0] = $this->_connection;
            break;

        case 'submit':
            if (!$this->_form) {
                throw new RuntimeException('No forms to submit');
            }
            $callback[0] = $this->_form;
            array_unshift($args, $this->_connection);
            break;

        default:
            throw new Actor_Exception(
                "Actor method {$method} not found",
                Actor_Exception::NOT_IMPLEMENTED
            );
        }

        if (!is_callable($callback)) {
            $this->log('Invalid callback: ' . serialize($callback),
                       Log_Abstract::LEVEL_ERROR);
        } else {
            $this->_response = call_user_func_array($callback, $args);
            if ($this->_connection->errno) {
                $this->log(
                    "Network error: {$this->_connection->errno} {$this->_connection->error}",
                    Log_Abstract::LEVEL_ERROR
                );
                $this->log($args, Log_Abstract::LEVEL_DEBUG);
            }
        }

        return $this->_response;
    }
}
