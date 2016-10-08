<?php
/**
 * @package Pool
 */

/**
 * Generic file-based proxies pool
 *
 * @package Pool
 */
class Pool_Proxy extends Pool_File_Abstract
{
    const DEFAULT_SCHEME   = 'http';
    const DEFAULT_PORT     = 2642;
    const DEFAULT_USERPASS = 'daft:2fmw5';


    /**
     * Fetches random proxy string
     *
     * @see Pool_File_Abstract::get()
     */
    public function get($key='default')
    {
        $s = parent::get('proxy');
        $proxy = new Connection_Proxy($s);
        if (!$proxy->is_valid) {
            $proxy = new Connection_Proxy(
                (isset($this->_config['proxy']['scheme'])
                    ? $this->_config['proxy']['scheme']
                    : constant(get_class($this) . '::DEFAULT_SCHEME')) .
                "://{$s}"
            );
        }
        foreach (array('port', 'userpass') as $k) {
            if (!$proxy->$k) {
                $proxy->$k = !empty($this->_config['proxy'][$k])
                    ? $this->_config['proxy'][$k]
                    : constant(get_class($this) . '::DEFAULT_' . strtoupper($k));
            }
        }
        return $proxy->is_valid
            ? $proxy
            : false;
    }

    public function enable($proxy)
    {
        return true;
    }

    public function disable($proxy)
    {
        return true;
    }

    public function report($sid_key, $is_failed=true)
    {
        return true;
    }
}
