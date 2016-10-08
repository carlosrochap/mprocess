<?php
/**
 * @package Pool
 */

/**
 * Base class for pools
 *
 * @property int   $size   Current pool size
 * @property array $config Current project configuration
 *
 * @package Pool
 */
abstract class Pool_Abstract extends Loggable
{
    /**
     * Default pool size, may be redeclared when extending
     */
    const DEFAULT_SIZE = 64;


    /**
     * Project configuration
     *
     * @param array
     */
    protected $_config = array();

    /**
     * Named pools
     *
     * @var array
     */
    protected $_pools = array();

    /**
     * Current pool size
     *
     * @var int
     */
    protected $_size = 0;

    /**
     * Current process ID
     *
     * @var int
     */
    protected $_pid = 0;


    /**
     * Prepares a pool name for internal usage
     *
     * @param string $name Pool name
     * @return string
     */
    protected function _prepare_name($name)
    {
        return strtolower($name);
    }

    /**
     * Fills a named pool using custom pool method (if defined)
     *
     * @param string $name Pool name
     * @param int    $size Pool size
     * @return bool True if the pool was filled
     */
    protected function _prepare($name, $size)
    {
        $pool = &$this->_pools[$name];
        if (empty($pool)) {
            $m = "_fill_{$name}";
            if (method_exists($this, $m)) {
                $args = func_get_args();
                $pool = call_user_func_array(
                    array($this, $m),
                    array_slice($args, 1)
                );
                if (is_array($pool)) {
                    shuffle($pool);
                }
            }
        }
        return (is_array($pool) && count($pool));
    }


    /**
     * @param array $config Project configuration
     */
    public function __construct(array $config=array())
    {
        $this->_pid = Environment::get_pid();
        $this->_config = $config;
        if (1 > $this->_size) {
            $this->_size = constant(get_class($this) . '::DEFAULT_SIZE');
        }
        $this->init();
    }

    /**
     * Ensures the pool's closed gracefully
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Extend to your needs, return $this or parent::init() for chaining.
     * May be called more than once.
     */
    public function init()
    {
        return $this;
    }

    /**
     * Extend to your needs, return $this or parent::close() for chaining.
     */
    public function close()
    {
        $this->_pools = array();
        return $this;
    }

    /**
     * Sets current project configuration
     *
     * @param array $config
     */
    public function set_config(array $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Returns current project configuration
     *
     * @return array
     */
    public function get_config()
    {
        return $this->_config;
    }

    /**
     * Sets pool size
     *
     * @param int $size
     */
    public function set_size($size)
    {
        $this->_size = max(1, (int)$size);
        return $this;
    }

    /**
     * Returns current pool size
     *
     * @return int
     */
    public function get_size()
    {
        return $this->_size;
    }

    /**
     * Clears named pool(s)
     *
     * @param mixed $name Pool name or list of names, null to clean all pools
     */
    public function clear($name=null)
    {
        if (null === $name) {
            $this->_pools = array();
        } else {
            foreach (array_map(
                array($this, '_prepare_name'),
                is_array($name) ? $name : array($name)
            ) as $k) {
                unset($this->_pools[$k]);
            }
        }
        return $this;
    }

    /**
     * Fetches a random item from named pool, trying to fill a pool
     * if needed
     *
     * @param string $name Optional pool name
     * @return mixed Pool item or null
     */
    public function get($name=null)
    {
        if (null === $name) {
            $s = get_class($this);
            $name = substr($s, strrpos($s, '_') + 1);
        }

        $args = func_get_args();
        $args[0] = $name = $this->_prepare_name($name);

        if (call_user_func_array(array($this, '_prepare'), array_merge(
            array($name, $this->_size),
            array_slice($args, 1)
        ))) {
            return array_pop($this->_pools[$name]);
        }

        $m = "get_{$name}";
        if (method_exists($this, $m)) {
            return call_user_func_array(
                array($this, $m),
                array_slice($args, 1)
            );
        }

        return null;
    }
}
