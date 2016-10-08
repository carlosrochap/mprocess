<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_NotLongCom extends Service_UrlShortener_Abstract
{
    const HOST = 'http://notlong.com';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        if ($this->_connection) {
            $this->_connection->get(self::HOST);
            if (preg_match(
               '#<blockquote>\s+<a href="([^"]+)#',
               $this->_connection->post(self::HOST . '/', http_build_query(array(
                   'nickname' => '',
                   'url'      => &$url
               ))),
               $m
            )) {
                return self::filter($m[1]);
            }
        }
        return false;
    }
}
