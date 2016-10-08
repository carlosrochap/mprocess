<?php
/**
 * @package Connection
 */

/**
 * Proxy details container
 *
 * @package Connection
 * @subpackage Proxy
 */
class Connection_Proxy extends Url
{
    /**
     * @ignore
     */
    static public function parse($url)
    {
        $components = parent::parse($url);

        foreach (array('path', 'query', 'fragment') as $k) {
            unset($components[$k]);
        }

        return $components;
    }

    /**
     * Composes proxy URL from components
     *
     * @see Url::compose()
     */
    static public function compose(array $components)
    {
        foreach (array('path', 'query', 'fragment') as $k) {
            unset($components[$k]);
        }

        return rtrim(parent::compose($components), '/');
    }


    /**
     * @see Url::set()
     */
    public function set($url)
    {
        if (is_array($url)) {
            $url =
                (!empty($url['userpass']) ? "{$url['userpass']}@" : '') .
                $url['host'] .
                (!empty($url['port']) ? ":{$url['port']}" : '');
        }

        return parent::set($url);
    }
}
