<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_BitLy extends Service_UrlShortener_Abstract
{
    const HOST = 'http://bit.ly/';
    const POST = 'data/shorten';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
    	$response = $this->_connection->get(self::HOST);
    	if(!preg_match('#<input[^>]+name="_xsrf"[^>]+value="([^"]+)"#',
    	               $response,
    	               $m)) {
    		return false;
    	}
    	$response = $this->_connection->post(self::HOST . self::POST, 
	    	http_build_query(array(
	            '_xsrf' => $m[1],
	    	    'url'   => $url
	        )));
        if(!preg_match('#"url":[^"]+"([^"]+)"#', $response, $m)) {
        	return false;
        }
        return $m[1];
    }
}
