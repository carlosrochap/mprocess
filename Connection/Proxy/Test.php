<?php
/**
 * @package Connection
 */

/**
 * Loads bootstrap script
 */
require_once 'init.inc.php';


/**
 * Proxies handling tests
 *
 * @package Connection
 * @subpackage Proxy
 */
class Connection_Proxy_Test extends PHPUnit_Framework_TestCase
{
    protected $_scheme,
              $_user, $_pass, $_userpass,
              $_host, $_port, $_hostport;


    /**
     * Sets up test proxy object
     */
    public function setUp()
    {
        foreach (array('scheme', 'user', 'pass', 'host') as $k) {
            $this->{"_{$k}"} = dechex(crc32(mt_rand()));
        }
        $this->_port = mt_rand(1, 65535);

        $this->_userpass = "{$this->_user}:{$this->_pass}";
        $this->_hostport = "{$this->_host}:{$this->_port}";
    }

    /**
     * Tests parsing and constructing proxy strings
     */
    public function test_parse_and_export()
    {
        $proxy = "{$this->_scheme}://{$this->_userpass}@{$this->_hostport}";
        $fixture = new Connection_Proxy("{$proxy}/as/df?g=h#jkl");
        $this->assertEquals($proxy, (string)$fixture);
        $this->assertEquals($proxy, $fixture->get());

        $proxy = "{$this->_userpass}@{$this->_hostport}";
        $fixture = new Connection_Proxy("{$proxy}/as/df?g=h#jkl");
        $this->assertEquals(
            Connection_Proxy::DEFAULT_SCHEME . '://' . $proxy,
            (string)$fixture
        );
        $this->assertEquals(
            Connection_Proxy::DEFAULT_SCHEME . '://' . $proxy,
            $fixture->get()
        );
    }

    /**
     * Tests proxy object getters/setters
     */
    public function test_accessors()
    {
        $fixture = new Connection_Proxy();

        $this->assertFalse($fixture->is_valid);
        $this->assertEquals(Connection_Proxy::DEFAULT_SCHEME, $fixture->scheme);

        foreach (array('scheme', 'user', 'pass', 'host', 'port') as $k) {
            $fixture->{$k} = $this->{"_{$k}"};
            $this->assertEquals($this->{"_{$k}"}, $fixture->{$k});
        }

        $this->assertTrue($fixture->is_valid);

        $fixture = new Connection_Proxy($this->_host);
        $this->assertEquals($fixture->host, $this->_host);
    }
}
