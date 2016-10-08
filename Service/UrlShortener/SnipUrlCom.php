<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_SnipUrlCom extends Service_UrlShortener_Abstract
{
    const HOST = 'http://snipurl.com';
    const SHORTEN_URL = '/site/index';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return ($this->_connection && preg_match(
            '# val="([^ ]+)#',
            $this->_connection->post(
                self::HOST . self::SHORTEN_URL,
                http_build_query(array(
                    'current_alias' => '',
                    'hash'          => '',
                    'mode'          => 1,
                    'nickname'      => '',
                    'private_key'   => '',
                    'submit1'       => 'Snip it!',
                    'title'         => '',
                    'url'           => $url,
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
