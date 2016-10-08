<?php
/**
 * @package Base
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * Dummy container for test purposes
 *
 * @package Base
 */
class Container_Dummy extends Container
{
    private $_test = null;


    /**
     * @ignore
     */
    public function set_test($value)
    {
        $this->_test = $value;
        return $this;
    }

    /**
     * @ignore
     */
    public function get_test()
    {
        return $this->_test;
    }

    /**
     * @ignore
     */
    public function remove_test()
    {
        $this->_test = null;
    }
}


/**
 * Basic container objects tests
 *
 * @package Base
 */
class Container_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests accessors
     */
    public function test_accessors()
    {
        $fixture = new Container_Dummy();
        $this->assertEquals(null, $fixture->test);

        $s = 'TeSt';
        $fixture->test = $s;
        $this->assertEquals($s, $fixture->test);

        $s .= $s;
        $fixture['test'] = $s;
        $this->assertEquals($s, $fixture['test']);

        unset($fixture['test']);
        $this->assertEquals(null, $fixture['test']);
    }
}
