<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_IxLt extends Service_UrlShortener_Abstract
{
    const HOST = 'http://ix.lt';
    const SHORTEN_URL = '/create.php';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '#Your New Short URL: <a href=([^ ]+)#',
            $this->_connection->post(
                self::HOST . self::SHORTEN_URL,
                http_build_query(array(
                    'send'    => 'Make It Short!',
                    'tag'     => '',
                    'url'     => $url
                )),
                null,
                self::HOST . '/'
            ),
            $m
        ))
            ? self::filter($m[1])
            : false;
    }
}
