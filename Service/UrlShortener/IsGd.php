<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_IsGd extends Service_UrlShortener_Abstract
{
    const HOST = 'http://is.gd';
    const SHORTEN_URL = '/create.php';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '#id="short_url" value="([^"]+)#',
            $this->_connection->post(
                self::HOST . self::SHORTEN_URL,
                http_build_query(array('URL' => $url)),
                null,
                self::HOST . '/'
            ),
            $m
        ))
            ? self::filter($m[1])
            : false;
    }
}
