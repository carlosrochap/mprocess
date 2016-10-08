<?php
/**
 * @package Service
 */

/**
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_AdVu extends Service_UrlShortener_Abstract
{
    const HOST = 'http://www.adjix.com';
    const SHORTEN_URL = '/WebObjects/Adjix.woa/wa/shrinkLink';


    /**
     * @see Service_UrlShortener_Interface::decode()
     */
    public function shorten($url)
    {
        return (
            $this->_connection &&
            $this->_connection->get(self::HOST . '/') &&
            preg_match(
                '#value="([^"]+)" name="UltraCopyAndPasteTextField"#',
                $this->_connection->post(
                    self::HOST . self::SHORTEN_URL,
                    http_build_query(array(
                        'AdTypePopUp'      => 'No Ad',
                        'LongURLTextField' => &$url,
                        'WOSubmitAction'   => 'shrinkLink',
                        'longURL.x'        => rand(20, 30),
                        'longURL.y'        => rand(10, 20),
                    ))
                ),
                $m
            )
        )
            ? self::filter($m[1])
            : false;
    }
}
