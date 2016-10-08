<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_TinyCc extends Service_UrlShortener_Abstract
{
    const HOST = 'http://tiny.cc';
    const SHORTEN_URL = '/ajax/create';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '#"tiny":"([^"]+)#',
            $this->_connection->ajax(self::HOST . self::SHORTEN_URL, array(
                'custom' => '',
                'url'    => &$url,
            ), null, self::HOST . '/'),
            $m
        ))
            ? self::HOST . "/{$m[1]}"
            : false;
    }
}
