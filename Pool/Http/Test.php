<?php
/**
 * @package Pool
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * HTTP-based pool for test purposes
 *
 * @package Pool
 * @subpackage Http
 */
class Pool_Http_PhpDocLanguage extends Pool_Http_Abstract
{
    /**
     * Data sources, see {@link Pool_Http_Abstract::$_sources}
     *
     * @var array
     */
    protected $_sources = array('default' => array(
        'url'   => 'http://www.php.net/docs.php',
        'regex' => '#href="/manual/(?P<code>[a-z]{2})/">(?P<name>[^<]+)#'
    ));
}


/**
 * HTTP-based pools tests
 *
 * @package Pool
 * @subpackage Http
 */
class Pool_Http_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test pool fixture
     *
     * @var Pool_Http_PhpDocLanguage
     */
    private $_pool = null;


    /**
     * Sets up test pool
     */
    public function setUp()
    {
        $this->_pool = new Pool_Http_PhpDocLanguage(array());
        $this->_pool->set_connection(new Connection_Curl());
    }

    /**
     * Tests fetching data
     */
    public function test_pool_filling()
    {
        $lang = $this->_pool->get();
        $this->assertTrue(2 == strlen($lang['code']));
        $this->assertTrue(6 <= strlen($lang['name']));
    }
}
