<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_MooUrlCom extends Service_UrlShortener_Abstract
{
    const HOST = 'http://moourl.com';
    const SHORTEN_URL = '/create/';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        $result = false;
        if ($this->_connection) {
            $old_follow = $this->_connection->follow_location;
            $this->_connection->follow_location = false;
            $this->_connection->get(self::HOST . self::SHORTEN_URL, array(
                'source' => &$url,
                'x'      => rand(45, 55),
                'y'      => rand(5, 15),
            ));
            $this->_connection->follow_location = $old_follow;
            if ((302 == $this->_connection->status_code) && preg_match(
                '#\?moo=(.+)#',
                $this->_connection->get_response_header('Location'),
                $m
            )) {
                $result = self::HOST . "/{$m[1]}";
            }
        }
        return $result;
    }
}
