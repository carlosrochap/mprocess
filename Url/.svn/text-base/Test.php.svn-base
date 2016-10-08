<?php
/**
 * @package Base
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * URL object tests
 *
 * @package Base
 */
class Url_Test extends PHPUnit_Framework_TestCase
{
    private $_url = 'https://test%3Auser:test%40pass@example.com:8080/a/b%5Bc%5D/d?key=value#frag%2Fment';
    private $_fixture = null;


    /**
     * Sets up test environment
     */
    public function setUp()
    {
        $this->_fixture = new Url();
    }

    /**
     * Tests default values
     */
    public function test_defaults()
    {
        $this->assertEquals(Url::DEFAULT_SCHEME, $this->_fixture->scheme);
        $this->assertFalse($this->_fixture->is_valid);
    }

    /**
     * tests URL parses
     */
    public function test_parsers()
    {
        $this->_fixture = new Url('/a/b/c/?d=e');
        $this->assertFalse($this->_fixture->is_valid);
        $this->assertEquals('/a/b/c/', $this->_fixture->path);
        $this->assertEquals(array('d' => 'e'), $this->_fixture->query);
    }

    /**
     * Tests getters
     */
    public function test_getters()
    {
        $this->_fixture->set($this->_url);

        $this->assertEquals('https', $this->_fixture->scheme);
        $this->assertEquals('test:user', $this->_fixture->user);
        $this->assertEquals('test@pass', $this->_fixture->pass);
        $this->assertEquals('test%3Auser:test%40pass', $this->_fixture->userpass);
        $this->assertEquals('example.com', $this->_fixture->host);
        $this->assertEquals(8080, $this->_fixture->port);
        $this->assertEquals('example.com:8080', $this->_fixture->hostport);
        $this->assertEquals('/a/b%5Bc%5D/d', $this->_fixture->path);
        $this->assertEquals(array('key' => 'value'), $this->_fixture->query);
        $this->assertEquals('frag/ment', $this->_fixture->fragment);

        $this->assertEquals($this->_url, $this->_fixture->get());
    }

    /**
     * Tests setters
     */
    public function test_setters()
    {
        list($user, $pass) = array('test:user', 'test@pass');
        $this->_fixture->user = $user;
        $this->_fixture->pass = $pass;
        $this->assertEquals('test%3Auser:test%40pass', $this->_fixture->userpass);

        $this->_fixture->userpass = 'user:pass';
        $this->assertEquals('user:pass', $this->_fixture->userpass);

        $host = 'example.com:8080';
        $this->_fixture->host = $host;
        $this->assertEquals($host, $this->_fixture->hostport);

        $path = 'a/b/c';
        $this->_fixture->path = $path;
        $this->assertEquals("/{$path}", $this->_fixture->path);
        $this->_fixture->path = "/{$path}";
        $this->assertEquals("/{$path}", $this->_fixture->path);
    }
}
