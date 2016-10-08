<?php
/**
 * @package Process
 */

/**
 * Base class for processes
 *
 * @property array           $config Project configurations
 * @property Queue_Interface $queue  Message queue in use
 *
 * @property-read bool $is_slave Master/slave status flag
 *
 * @package Process
 */
abstract class Process_Abstract extends Loggable implements Process_Interface
{
    /**
     * Master/slave status flag
     *
     * @var bool
     */
    protected $_is_slave = false;

    /**
     * Project-specific configuration
     *
     * @var array
     */
    protected $_config = array();

    /**
     * Message queue to use
     *
     * @var Queue_Interface
     */
    protected $_queue = null;


    /**
     * @param array           $config Project configuration
     * @param Queue_Interface $queue  Message queue to use
     * @param Log_Interface   $log    Logger used
     * @uses ::init() For process specific initialization
     */
    public function __construct(array $config, Queue_Interface $queue=null, Log_Interface $log=null)
    {
        $this->_config = $config;
        foreach (array('queue', 'log') as $k) {
            if ($$k) {
                $this->$k = $$k;
            }
        }
        $this->init();
    }

    /**
     * Destroys a process instance
     *
     * @uses ::close() For process specific clean-up
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initializes a process instance
     *
     * Extend to your needs. Should be safe to be called multiple times.
     * Return $this for chaining.
     */
    public function init()
    {
        return $this;
    }

    /**
     * Cleans up
     *
     * Extend to your needs. Should be safe to be called multiple times.
     * Return $this for chaining.
     */
    public function close()
    {
        return $this;
    }

    /**
     * Returns process' slave/master status flag
     *
     * @return bool
     */
    public function get_is_slave()
    {
        return $this->_is_slave;
    }

    /**
     * Sets a message queue to use
     *
     * @param Queue_Interface $queue
     */
    public function set_queue(Queue_Interface $queue)
    {
        $this->_queue = $queue;
        return $this;
    }

    /**
     * Returns the message queue in use if set
     *
     * @return null|Queue_Interface
     */
    public function get_queue()
    {
        return $this->_queue;
    }

    /**
     * Sets a project configuration
     *
     * @param array $config
     */
    public function set_config(array $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Returns a project configuration
     *
     * @return array
     */
    public function get_config()
    {
        return $this->_config;
    }

    /**
     * Logs arbitrary message
     *
     * @see Loggable::log()
     */
    public function log($msg, $lvl=Log_Abstract::LEVEL_INFO)
    {
        return parent::log(($this->is_slave
            ? 'S'
            : 'M') . ' ' . Log_Abstract::prepare($msg), $lvl);
    }


    /**
     * Sends a message
     *
     * @see Queue_Interface::send()
     * @return bool
     */
    protected function _send($action, $payload=null, $recipient=1)
    {
        return $this->_queue->send($recipient, array(
            'action' => strtolower($action),
            'data'   => $payload,
            'from'   => Environment::get_pid(),
        ));
    }

    /**
     * Receives a message
     *
     * @see Queue_Interface::receive()
     * @return array|null Hash table with (at least) 'action' and 'data' keys on success
     */
    protected function _receive($recipient, $action=null, $block=true)
    {
        $msg = $this->_queue->receive($recipient, $block);
        if (!is_array($msg) || empty($msg['action'])) {
            if ($block) {
                throw new Process_Exception(
                    'Invalid message received',
                    Process_Exception::IO_ERROR
                );
            } else {
                return null;
            }
        } else if ((null === $action) || ($action == $msg['action'])) {
            return $msg;
        } else if (!$this->_queue->send($recipient, $msg)) {
            throw new Process_Exception(
                'Failed bouncing unrequested message back',
                Process_Exception::IO_ERROR
            );
        }
        sleep(self::RETRY_INTERVAL_SHORT);
        return $this->receive($recipient, $action, $block);
    }

    /**
     * Combines {@link ::_send()} and {@link ::_receive()} for common usage
     * pattern when a slave requests some additional data from its master.
     * Returns only payload part of the message received!
     *
     * @return mixed
     * @see ::_send() for arguments details
     */
    protected function _send_and_receive($action, $payload=null, $recipient=1)
    {
        $this->_send($action, $payload, $recipient);
        $msg = $this->_receive($action);
        return @$msg['data'];
    }

    /**
     * Message queue listener, dispatches appropriate module/action handlers
     */
    abstract protected function _listen();

    /**
     * Initializes the process and starts listening for messages
     *
     * @throws Process_Exception When message queue or project config not set
     */
    public function start()
    {
        if (!$this->queue) {
            throw new Process_Exception(
                'No message queue to use',
                Process_Exception::INVALID_ARGUMENT
            );
        }
        if (!$this->config) {
            throw new Process_Exception(
                'Project configuration is not set',
                Process_Exception::INVALID_ARGUMENT
            );
        }
        $this->log('Starting', Log_Abstract::LEVEL_DEBUG);
        $this->init();
        $this->_listen();
        return $this;
    }

    /**
     * Stops listening for messages and cleans up
     */
    public function stop()
    {
        $this->log('Stopping');
        return $this;
    }
}
