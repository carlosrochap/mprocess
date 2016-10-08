<?php
/**
 * @package Actor
 */

/**
 * Base class for actors
 *
 * @property Process_Interface $process Parent process that uses the actor
 *
 * @package Actor
 */
abstract class Actor_Abstract extends Loggable
{
    /**
     * Maximum number of arbitrary action retries and short/long
     * intervals (in seconds) between retries
     */
    const RETRY_COUNT          = 3;
    const RETRY_INTERVAL_SHORT = 5;
    const RETRY_INTERVAL_LONG  = 60;


    /**
     * Parent process
     *
     * @var object
     */
    protected $_process = null;


    /**
     * Instantiates an actor. Try not to extend the constructor,
     * use {@link ::init()} instead for actor-specific initialization.
     *
     * @param Log_Interface $log Logger
     */
    public function __construct(Log_Interface $log=null)
    {
        if ($log) {
            $this->set_log($log);
        }
        $this->init();
    }

    /**
     * Destructs an actor. Try not to extend the destructor, use
     * {@link ::close()} for actor-specific de-initialization instead.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initializes the actor
     *
     * Extend to your needs, make sure to return parent::init() for chaining.
     * May be called more than once.
     */
    public function init()
    {
        return $this;
    }

    /**
     * De-initializes the actor.
     *
     * Extend to your needs, make sure to return parent::close() or $this
     * for chaining. May be called more than once.
     */
    public function close()
    {
        return $this;
    }

    /**
     * Defines a process that uses the actor's instance
     *
     * @param Process_Interface $process
     */
    public function set_process(Process_Interface $process)
    {
        $this->_process = $process;
        return $this;
    }

    /**
     * Returns an actor's parent process if defined
     *
     * @return Process_Interface|null
     */
    public function get_process()
    {
        return $this->_process;
    }

    /**
     * Checks for and calls parent process' method with arbitrary arguments
     *
     * @param string $method
     * @param mixed  $...
     * @return mixed
     */
    public function call_process_method($method)
    {
        if (!$this->_process) {
            $this->log('No processes to use',
                       Log_Abstract::LEVEL_ERROR);
        } else if (!method_exists($this->_process, $method)) {
            $this->log("Process method {$method} not found",
                       Log_Abstract::LEVEL_ERROR);
        } else {
            $args = func_get_args();
            return call_user_func_array(
                array($this->_process, $method),
                array_slice($args, 1)
            );
        }
        return null;
    }
}
