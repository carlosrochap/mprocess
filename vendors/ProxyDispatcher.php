<?php
/**
 * @package Pool
 */

/**
 * Proxy dispatcher client
 *
 * @property string project  Project name
 * @property string password Project password
 * @property string pass     Alias for {@link ::$password}
 * @property string sid      Session ID
 * @property string session  Alias for {@link ::$sid}
 * @package Pool
 */
class ProxyDispatcher
{
    const DEFAULT_TUNNEL = 'socks5';

    const DISPATCHER_PORT    = 8080;
    const TUNNEL_SOCKS5_PORT = 1080;
    const TUNNEL_HTTP_PORT   = 8888;

    const PROXY_TYPE_PUBLIC   = 'PUBLIC';
    const PROXY_TYPE_PRIVATE  = 'PRIVATE';
    const PROXY_TYPE_SOFTNET  = 'SOFTNET';


    // @todo: move it to the proxy dispatcher itself
    protected $_dispatchers = null;
    protected $_tunnels = array(
        // proxy15
        '85.17.172.11',
        '85.17.172.14',
        '85.17.172.15',
        '85.17.172.16',
        '85.17.172.17',
        '85.17.172.18',
        '85.17.172.19',
        '85.17.172.20',
        '85.17.172.21',
        '85.17.172.22',
        '85.17.172.23',
        '85.17.172.24',
        '85.17.172.25',
        '85.17.172.26',
        '85.17.172.27',
        '85.17.172.28',
        // proxy16
        '87.117.253.181',
        '87.117.253.35',
        '87.117.253.36',
        '87.117.253.37',
        '87.117.253.38',
        '87.117.253.39',
        '87.117.253.41',
        '87.117.253.42',
        '87.117.253.43',
        '87.117.253.44',
        '87.117.253.45',
        '87.117.253.46',
        '87.117.253.47',
        '87.117.253.48',
        '87.117.253.49',
        '87.117.253.50',
        '87.117.253.51',
        '87.117.253.52',
        '87.117.253.53',
        '87.117.253.54',
    );

    protected $_co = null;
    protected $_project = '';
    protected $_password = '';
    protected $_sid = null;

    public $debug = false;


    protected function _check_sid()
    {
        if (!$this->_sid) {
            throw new RuntimeException('Session is not initialized');
        }
        return $this;
    }

    protected function _check_credentials()
    {
        if (!$this->_project || !$this->_password) {
            throw new InvalidArgumentException(
                'Project name or password not set'
            );
        }
        return $this;
    }

    protected function _get_session_url($path=null)
    {
        return
            'http://' .
            $this->_dispatchers[array_rand($this->_dispatchers)] . ':' .
            self::DISPATCHER_PORT .
            ($this->_sid ? "/{$this->_sid}" : '') .
            ($path ? "/{$path}" : '');
    }

    protected function _call($method, $data=null)
    {
        $curl_opts = array(
            CURLOPT_VERBOSE => (bool)$this->debug,
            CURLOPT_URL     => $this->_get_session_url($method),
            CURLOPT_USERPWD => "{$this->_project}:{$this->_password}",
        );
        if (null !== $data) {
            $curl_opts[CURLOPT_POST] = true;
            $curl_opts[CURLOPT_POSTFIELDS] = is_array($data)
                ? http_build_query($data)
                : (string)$data;
        } else {
            $curl_opts[CURLOPT_HTTPGET] = true;
        }
        curl_setopt_array($this->_co, $curl_opts);

        $resp = curl_exec($this->_co);
        return (
            !curl_errno($this->_co) && 
            (200 == curl_getinfo($this->_co, CURLINFO_HTTP_CODE)) &&
            ('application/json' == curl_getinfo(
                $this->_co,
                CURLINFO_CONTENT_TYPE
            ))
        )
            ? json_decode($resp, true)
            : false;
    }


    /**
     * @ignore
     */
    public function __construct()
    {
        $this->_dispatchers = &$this->_tunnels;
    }

    /**
     * @ignore
     */
    public function __destruct()
    {
        if (is_resource($this->_co)) {
            curl_close($this->_co);
        }
    }

    /**
     * Initializes a new project session
     *
     * @param string $project  Project name
     * @param string $password Project password
     * @return string|false Session ID on success
     */
    public function init($project=null, $password=null)
    {
        $this->_sid = null;
        if (null !== $project) {
            $this->project = $project;
        }
        if (null !== $password) {
            $this->password = $password;
        }

        if (is_resource($this->_co)) {
            curl_close($this->_co);
        }
        $this->_co = curl_init();
        curl_setopt_array($this->_co, array(
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
        ));
        return ($resp = $this->_call('')) && !empty($resp['session'])
            ? ($this->_sid = $resp['session'])
            : false;
    }

    /**
     * Fetches current session (proxies) options
     *
     * @return array|false
     */
    public function get_options()
    {
        $this->_check_sid();
        return is_array($resp = $this->_call('options'))
            ? $resp
            : false;
    }

    /**
     * Sets session options
     *
     * @param string|array $option Option name or dict of options
     * @param mixed        $value  Option value
     * @return bool
     */
    public function set_options($option, $value=null)
    {
        $this->_check_sid();
        if (!is_array($option)) {
            $option = array($option => &$value);
        }
        foreach (array_filter($option, 'is_array') as $k => $v) {
            $option[$k] = implode(',', $v);
        }
        return (bool)$this->_call('options', $option);
    }

    /**
     * Resets session options
     *
     * @return bool
     */
    public function reset_options()
    {
        $this->_check_sid();
        return is_array($resp = $this->_call('options/reset', ''));
    }

    /**
     * Returns random proxy tunnel as fully qualified URL
     *
     * @return string
     */
    public function get_proxy($tunnel=self::DEFAULT_TUNNEL)
    {
        $this->_check_sid();
        if (!$tunnel) {
            $tunnel = rand(0, 1) ? 'socks5' : 'http';
        }
        return
            "{$tunnel}://{$this->project}:{$this->sid}@" .
            $this->_tunnels[array_rand($this->_tunnels)] . ':' .
            (('socks5' == $tunnel)
                ? self::TUNNEL_SOCKS5_PORT
                : self::TUNNEL_HTTP_PORT);
    }

    /**
     * Switch to another proxy
     *
     * @return bool
     */
    public function switch_proxy()
    {
        $this->_check_sid();
        return (bool)$this->_call('proxy', '');
    }

    /**
     * Reports current proxy as failed
     *
     * @return bool
     */
    public function report_failed_proxy()
    {
        $this->_check_sid();
        return (bool)$this->_call('proxy/failed', '');
    }

    /**
     * @ignore
     */
    public function __set($key, $value)
    {
        switch ($key) {
        case 'project':
            $this->_project = $value;
            break;

        case 'pass':
        case 'password':
            $this->_password = $value;
            break;

        case 'sid':
        case 'session':
            $this->_sid = $value;
            break;
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
        case 'project':
            return $this->_project;

        case 'pass':
        case 'password':
            return $this->_password;

        case 'sid':
        case 'session':
            return $this->_sid;
        }

        return null;
    }
}
