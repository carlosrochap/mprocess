<?php
/**
 * @package Process
 */

/**
 * Base class for slave processes
 *
 * @package Process
 * @subpackage Slave
 */
abstract class Process_Slave_Abstract extends Process_Abstract
{
    const TTL = 7200;


    /**
     * @see Process_Abstract::$_is_slave
     */
    protected $_is_slave = true;

    /**
     * Generic timer
     *
     * @var Timer
     */
    protected $_timer = null;

    /**
     * HTTP connection to use
     *
     * @var Connection_Curl
     */
    protected $_connection = null;


    /**
     * @see Process_Abstract::init()
     */
    public function init()
    {
        if (!$this->_timer) {
            $this->_timer = new Timer();
        }
        if (!$this->_connection) {
            $this->_connection = new Connection_Curl();
        }
        $this->_connection->log = $this->log;
        $this->_connection->init();
        return parent::init();
    }

    /**
     * @see Process_Abstract::close()
     */
    public function close()
    {
        if ($this->_connection) {
            $this->_connection->close();
        }
        return parent::close();
    }

    /**
     * Sets proxy to use by HTTP connection
     *
     * @param string $proxy Proxy {@link http://tools.ietf.org/html/rfc3986#section-3.2 authority component}
     *                      or just a host if port and credentials are
     *                      defined in config.ini
     */
    public function set_proxy($proxy)
    {
        if (is_array($proxy)) {
            $proxy = isset($proxy['host'])
                ? $proxy['host']
                : '';
        }
        if ($proxy && $this->_connection) {
            if (!$proxy instanceof Connection_Proxy) {
                $proxy = new Connection_Proxy($proxy);
            }
            if (!$proxy || !$proxy->is_valid) {
                throw new Process_Slave_Exception(
                    'Invalid proxy',
                    Process_Exception::INVALID_ARGUMENT
                );
            }
            if (null !== ($config = &$this->_config['proxy'])) {
                foreach (array('userpass', 'port') as $k) {
                    if (!$proxy->$k && !empty($config[$k])) {
                        $proxy->$k = $config[$k];
                    }
                }
            }
            $this->_connection->proxy = $proxy;
            $this->log('PROXY: ' . $proxy->get(),
                       Log_Abstract::LEVEL_DEBUG);
        }
        return $this;
    }

    /**
     * Fetches a (cached) actor, tries to instantiate project-specific
     * actors first, then falls back to common actors
     *
     * @param string $name    Optional (sub)actor name
     * @param string $user_id Optional user ID to use by the actor
     * @return Actor_Abstract
     */
    public function get_actor($name=null, $user_id=null)
    {
        $project_name = ucfirst($this->_config['project']['name']);
        if ($name) {
            try {
                $actor = Actor_Factory::factory(
                    "{$project_name}_{$name}",
                    $this->_log
                );
            } catch (BadMethodCallException $e) {
                $actor = Actor_Factory::factory($name, $this->_log);
            }
        } else {
            $actor = Actor_Factory::factory($project_name, $this->_log);
        }
        $actor->process = $this;
        if (($actor instanceof Actor_Http_Abstract) && $this->_connection) {
            $actor->connection = $this->_connection;
        }
        if (null !== $user_id) {
            $actor->user_id = $user_id;
        }
        return $actor;
    }


    protected function _receive($action=null)
    {
        return parent::_receive(Environment::get_pid(), $action, true);
    }

    protected function _send($action, $payload=null)
    {
        return parent::_send($action, $payload, 1);
    }

    /**
     * Listens for master's orders
     *
     * The order received is processed by corresponding handler. When ready
     * to accept a new order, a 'ready' response should be sent to the master.
     *
     * @throws Process_Slave_Exception When unknown action requested
     */
    protected function _listen()
    {
        $deadline = time() + self::TTL;
        do {
            $this->_send('ready');

            $msg = $this->_receive();
            if (!$msg) {
                $this->log('No orders received');
                break;
            } else if ('stop' == $msg['action']) {
                break;
            }

            $handler = "_handle_action_{$msg['action']}";
            if (!method_exists($this, $handler)) {
                throw new Process_Slave_Exception(
                    "No {$msg['action']} action handlers found",
                    Process_Exception::ACTION_NOT_FOUND
                );
            }
            $this->_timer->start();
            try {
                if ($this->{$handler}($msg['data'])) {
                    $this->_timer->rate = $this->_send_and_receive(
                        'get_rate',
                        $msg['action']
                    );
                    $this->_timer->stop();
                }
            } catch (Exception $e) {
                $this->log($e, Log_Abstract::LEVEL_ERROR);
            }

            $this->log('Memory used: ' . memory_get_usage(),
                       Log_Abstract::LEVEL_DEBUG);
        } while (true);//$deadline > time());
    }

    /**
     * Notifies the master that the slave is gone
     *
     * @see Process_Abstract::stop()
     */
    public function stop()
    {
        return parent::stop();
    }
}
