<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_TwitterUrlNet extends Service_UrlShortener_Abstract
{
    const HOST = 'http://twitterurl.net';
    const SHORTEN_URL = '/index.php';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '#<p class="success">.+?<a href="([^"]+)#',
            $this->_connection->post(
                self::HOST . self::SHORTEN_URL,
                http_build_query(array(
                    'longurl' => &$url,
                    'submit'  => 'Make it Short',
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
