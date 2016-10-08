<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_TinyUrlCom extends Service_UrlShortener_Abstract
{
    const HOST = 'http://tinyurl.com';
    const SHORTEN_URL = '/create.php';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '#copy\(\'(' . preg_quote(self::HOST) . '/[^\']+)\'#',
            $this->_connection->post(
                self::HOST . self::SHORTEN_URL,
                http_build_query(array(
                    'alias'  => '',
                    'submit' => 'Make TinyURL!',
                    'url'    => $url
                )),
                null,
                self::HOST . '/'
            ),
            $m
        ))
            ? stripcslashes($m[1])
            : false;
    }
}
