<?php
/**
 * @package Db
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * DSN handling tests
 *
 * @package Db
 * @subpackage Dsn
 */
class Db_Dsn_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test DSN string
     *
     * @var string
     */
    private $_dsn = 'mysql://testuser:testpass@72.10.166.29:3309/gamma/username';

    /**
     * Test fixture
     *
     * @var Db_Dsn
     */
    private $_fixture = null;


    /**
     * Creates a test fixture
     */
    public function setUp()
    {
        $this->_fixture = new Db_Dsn($this->_dsn);
    }

    /**
     * Tests exporting parsed valus to DSN string
     */
    public function test_export()
    {
        $this->assertEquals($this->_dsn, $this->_fixture->get());
    }

    /**
     * Tests various {@link Db_Dsn} getters
     */
    public function test_getters()
    {
        $this->assertEquals('mysql', $this->_fixture->scheme);
        $this->assertEquals('gamma', $this->_fixture->dbname);
        $this->assertEquals('username', $this->_fixture->tblname);

        $this->assertEquals('72.10.166.29', $this->_fixture->host);
        $this->assertEquals(3309, $this->_fixture->port);
        $this->assertEquals('72.10.166.29:3309', $this->_fixture->hostport);

        $this->assertEquals('testuser', $this->_fixture->user);
        $this->assertEquals('testpass', $this->_fixture->pass);
        $this->assertEquals('testuser:testpass', $this->_fixture->userpass);
    }

    /**
     * Tests various {@link Db_Dsn} setters
     */
    public function test_setters()
    {
        $host = '72.10.166.22';
        $this->_fixture->host = $host;
        $this->assertEquals($host, $this->_fixture->host);

        $this->_fixture->port = Db_Abstract::DEFAULT_PORT;
        $this->assertEquals(
            Db_Abstract::DEFAULT_PORT,
            $this->_fixture->port
        );

        $scheme = 'psql';
        $this->_fixture->scheme = $scheme;
        $this->assertEquals($scheme, $this->_fixture->scheme);
    }

    /**
     * Tests minimal DSN strings
     */
    public function test_short_dsn()
    {
        $dsn = new Db_Dsn();
        $this->assertEquals(Db_Abstract::DEFAULT_SCHEME, $dsn->scheme);
        $this->assertEquals(Db_Abstract::DEFAULT_HOST, $dsn->host);
        $this->assertEquals(Db_Abstract::DEFAULT_PORT, $dsn->port);
    }
}
