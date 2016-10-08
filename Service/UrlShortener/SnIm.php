<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_SnIm extends Service_UrlShortener_Abstract
{
    const HOST = 'http://sn.im';
    const SHORTEN_URL = '/site/index';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        $host = constant(get_class($this) . '::HOST');
        $shorten_url = constant(get_class($this) . '::SHORTEN_URL');
        return ($this->_connection && preg_match(
            '#input class="snipped[^"]+".+?value="([^"]+)#',
            $this->_connection->post(
                "{$host}{$shorten_url}",
                http_build_query(array(
                    'current_alias' => '',
                    'hash'          => '',
                    'mode'          => 'i',
                    'nickname'      => '',
                    'private_key'   => '',
                    'submit1'       => 'Snip it!',
                    'title'         => '',
                    'url'           => &$url,
                )), null, "{$host}/"
            ),
            $m
        ))
            ? self::filter($m[1])
            : false;
    }
}
