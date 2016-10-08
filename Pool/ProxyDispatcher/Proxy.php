<?php
/**
 * @package Pool
 */

/**
 * Proxy dispatcher related pool
 *
 * @package Pool
 * @subpackage ProxyDispatcher
 */
class Pool_ProxyDispatcher_Proxy extends Pool_Abstract
{
    /**
     * Proxy dispatcher client instance
     *
     * @var ProxyDispatcher
     */
    protected $_pd = null;


    /**
     * Checks whether an arbitrary session ID is a valid session ID
     *
     * @param mixed $sid
     * @return bool
     */
    static public function is_sid($sid)
    {
        return preg_match('/^[a-f\d]{32,40}$/', $sid);
    }


    /**
     * @see Pool_Abstract::init()
     */
    public function init()
    {
        if (!$this->_pd) {
            $this->_pd = new ProxyDispatcher();
            $this->_pd->debug = @$this->_config['project']['test'];
        }
        return parent::init();
    }

    /**
     * Creates/opens a proxy dispatcher session using credentials from
     * project configuration, and fetches a new proxies list
     *
     * @see Pool_Abstract::get()
     * @throws Pool_Exception When proxy dispatcher credentials not found
     * @return array|null
     */
    public function get($name=null)
    {
        $config = &$this->_config['proxy_dispatcher'];
        if (!$this->_pd->project) {
            $userpass = explode(':', @$config['userpass'], 2);
            if (2 != count($userpass)) {
                throw new Pool_Exception(
                    'Proxy dispatcher credentials not found'
                );
            } else if (!$this->_pd->init($userpass[0], $userpass[1])) {
                return;
            }
        }
        if ($this->_pd->init()) {
            foreach (array(
                'types',
                'softnets',
                'countries',
                'proxies',
                'excludes',
            ) as $k) {
                if (!empty($config[$k])) {
                    $this->_pd->set_options($k, $config[$k]);
                }
            }
        }
        if ($this->_pd->sid) {
            return $this->_pd->get_proxy(!empty($config['tunnel'])
                ? $config['tunnel']
                : ProxyDispatcher::DEFAULT_TUNNEL);
        }
    }

    /**
     * Reports a failed proxy
     *
     * @param string $sid_key
     * @param bool   $is_failed
     * @return true
     */
    public function report($sid_key, $is_failed=true)
    {
        return true;
    }

    public function enable($proxy)
    {
        return true;
    }

    public function disable($proxy)
    {
        return true;
    }
}
