<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_DoiopCom extends Service_UrlShortener_Abstract
{
    const HOST = 'http://doiop.com';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '#name="pkey"[^>]+value="([^"]+)#',
            $this->_connection->post(self::HOST . '/', http_build_query(array(
                'Submit' => 'Make it!',
                'pkey'   => '',
                'url'    => $url,
            )), null, self::HOST . '/'),
            $m
        ))
            ? self::HOST . '/' . self::filter($m[1])
            : false;
    }
}
