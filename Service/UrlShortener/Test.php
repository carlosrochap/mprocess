<?php
/**
 * @package Service
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * Url shorteners test unit
 *
 * @package Service
 * @subpackage UrlShortener
 */
class Service_UrlShortener_Test extends PHPUnit_Framework_TestCase
{
    const URL = 'http://example.com/';


    protected $_short_urls = array(
        'AdVu'          => 'http://ad.vu/4jft',
        'ClLk'          => false,
        //'CliGs'         => 'http://cli.gs/HqUWaB',
        'DoiopCom'      => false,
        //'IdekNet'       => 'http://idek.net/1Kdw',
        'IsGd'          => false,
        'IxLt'          => false,
        'MooUrlCom'     => 'http://moourl.com/ymk8r',
        'NotLongCom'    => false,
        'SnIm'          => false,
        'SnipUrlCom'    => false,
        'TinyCc'        => false,
        'TinyUrlCom'    => 'http://tinyurl.com/kotu',
        'TwitterUrlNet' => 'http://twitterurl.net//74p',
        //'ZiMa'          => 'http://zi.ma/f0af4b',
    );


    /**
     * Tests shortening
     */
    public function test_shortening()
    {
        $conn = new Connection_Curl();
        $conn->is_verbose = true;
        foreach ($this->_short_urls as $shortener => $short_url) {
            $conn->init();
            $s = Service_UrlShortener_Factory::shorten(
                self::URL,
                $shortener,
                $conn
            );
            $msg = "{$shortener} has failed, got " . serialize($s);
            if ($short_url) {
                $this->assertEquals($short_url, $s, $msg);
            } else {
                $this->assertTrue((bool)$s, $msg);
            }
        }
    }
}
