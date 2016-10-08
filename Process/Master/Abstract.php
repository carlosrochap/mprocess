<?php
/**
 * @package Process
 */

/**
 * Base class for processes
 *
 * Works with data pools (of profiles, accounts, proxies etc.), manages
 * slaves sending orders and processesing responses.
 *
 * @package Process
 * @subpackage Master
 */
abstract class Process_Master_Abstract extends Process_Abstract
{
    const DEFAULT_RATE = 0;


    /**
     * Active module name and handler
     *
     * @var string
     */
    protected $_module = '';

    /**
     * Active module handler
     *
     * @var string
     */
    protected $_module_handler = '';

    /**
     * Hash table of slaves mapping PIDs to free/busy (true/false) flags
     *
     * @var array
     */
    protected $_slaves = array();

    /**
     * Hash tables of project-specific actions' rates
     *
     * @var array
     */
    protected $_rates = array();


    /**
     * @see Process_Abstract::init()
     */
    public function init()
    {
        $this->_slaves = array();
        return parent::init();
    }

    /**
     * Sets active module
     *
     * @param string $module Module name
     * @throws Process_Master_Exception When module handler not found
     */
    public function set_module($module)
    {
        $module = strtolower($module);
        $this->_module_handler = "_handle_module_{$module}";
        if (!$module || !method_exists($this, $this->_module_handler)) {
            throw new Process_Master_Exception(
                "{$module} module not found",
                Process_Exception::MODULE_NOT_FOUND
            );
        }
        $this->_module = $module;
        return $this;
    }

    /**
     * Returns active module name
     *
     * @return string
     */
    public function get_module()
    {
        return $this->_module;
    }

    public function get_slaves()
    {
        return array_keys($this->_slaves);
    }

    /**
     * Calculates an action rate in actions per day
     *
     * @param string $action
     * @return int
     */
    public function get_rate($action)
    {
        if ($this->_config['project']['test'] || ('stop' == $this->module)) {
            return 0;
        } else if (!isset($this->_rates[$action])) {
            $this->_rates[$action] = isset($this->_config['rates'][$action])
                ? (int)$this->_config['rates'][$action]
                : constant(get_class($this) . '::DEFAULT_RATE');
        }
        return max(0, (int)((rand(90, 110) / 100) * $this->_rates[$action]));
    }

    /**
     * Fetches a (cached) pool, tries to instantiate project-specific
     * pools first, then falls back to common pools.
     *
     * @return Pool_Abstract
     */
    public function get_pool($name)
    {
        try {
            return Pool_Factory::factory(
                ucfirst($this->_config['project']['name']) . "_{$name}",
                $this->_log,
                $this->_config
            );
        } catch (BadMethodCallException $e) {
            return Pool_Factory::factory($name, $this->_log, $this->_config);
        }
    }

    /**
     * @see Process_Abstract::start()
     * @throws Process_Master_Exception When module to run is not set
     */
    public function start()
    {
        if (!$this->module) {
            throw new Process_Master_Exception(
                'No active modules',
                Process_Exception::MODULE_NOT_FOUND
            );
        }
        return parent::start();
    }

    public function kill()
    {
        $this->_slaves = array();
        $this->_handle_action_stop();
    }

    protected function _receive($action=null)
    {
        return parent::_receive(1, $action, false);
    }

    protected function _send($action, $payload=null, $recipient=1)
    {
        return parent::_send($action, $payload, $recipient);
    }

    /**
     * Sends orders to slaves, processes their responses
     *
     * When there are free slaves, calls current module handler for
     * new order's data. If no data available and none of the slaves are busy,
     * takes a nap, otherwise feeds free slaves with orders and waits
     * for their responses. When a slave responds, performs the requested
     * action.
     *
     * 'stop' action is special and handled in place. When it's received,
     * current module is changed to 'stop' shutting down the slaves.
     *
     * There are also some predefined actions:
     * @see ::_handle_action_ready()
     * @see ::_handle_action_gone()
     *
     * @throws Process_Master_Exception When invalid action requested
     */
    protected function _listen()
    {
        do {
            $free_slaves = array_keys(array_filter($this->_slaves));
            if (count($free_slaves)) {
                try {
                    $do_nothing = (count($free_slaves) == count($this->_slaves));
                    foreach ($free_slaves as $free_slave) {
                        $data = $this->{$this->_module_handler}($free_slave);
                        if (!$data) {
                            break;
                        }

                        $do_nothing = false;
                        $this->_slaves[$free_slave] = false;
                        $this->_send($this->_module, $data, $free_slave);
                        if ($this->_config['project']['test']) {
                            $this->module = 'stop';
                        }
                    }
                    if ($do_nothing) {
                        $this->log('Nothing to do, taking a nap');
                        sleep(self::RETRY_INTERVAL_LONG);
                    }
                } catch (Exception $e) {
                    $this->log($e, Log_Abstract::LEVEL_ERROR);
                    $this->kill();
                }
            }

            $msg = $this->_receive();
            if (!$msg) {
                sleep(self::RETRY_INTERVAL_SHORT);
                continue;
            }

            $k = 'action';
            $handler = "_handle_action_{$msg[$k]}";
            if (!method_exists($this, $handler)) {
                throw new Process_Master_Exception(
                    "No {$msg[$k]} action handlers found",
                    Process_Exception::ACTION_NOT_FOUND
                );
            }
            try {
                $this->{$handler}($msg['data'], $msg['from']);
            } catch (Exception $e) {
                $this->log($e, Log_Abstract::LEVEL_ERROR);
                $this->kill();
            }
        } while (count($this->_slaves) || ('stop' != $this->_module));

        $this->stop();
    }

    /**
     * Just a stub to pass module-supplied data checks, allowing
     * to send 'stop' orders to slaves.
     *
     * @return bool
     */
    protected function _handle_module_stop($pid)
    {
        return true;
    }


    /**
     * Counts a slave as free
     *
     * This action is requested by a slave when it's just created or done with
     * its previous order and is ready to accept a new one.
     *
     * @param mixed $unused
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_ready($unused, $pid)
    {
        if (isset($this->_slaves[$pid])) {
            $this->log("Slave {$pid} is ready",
                       Log_Abstract::LEVEL_DEBUG);
        } else {
            $this->log("Adding new slave {$pid}");
        }
        $this->_slaves[$pid] = true;
        return true;
    }

    /**
     * Removes a slave from the current slaves pool
     *
     * This action is requested by a slave when it's stopped.
     *
     * @param mixed $unused
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_gone($unused, $pid)
    {
        if (array_key_exists($pid, $this->_slaves)) {
            $this->log("Slave {$pid} is no more");
            unset($this->_slaves[$pid]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Starts shutting the master/slaves bots down
     *
     * @return bool
     */
    protected function _handle_action_stop()
    {
        $this->log('Shutting down');
        $this->module = 'stop';
        return true;
    }

    /**
     * Returns slaves-aware rate of arbitrary actions per day
     *
     * @param mixed $action
     * @param int   $pid
     * @return bool
     */
    protected function _handle_action_get_rate($action, $pid)
    {
        $this->log("Calculating rate for {$action} action",
                   Log_Abstract::LEVEL_DEBUG);
        return $this->_send(
            'get_rate',
            (int)($this->get_rate($action) / max(1, count($this->_slaves))),
            $pid
        );
    }
}
