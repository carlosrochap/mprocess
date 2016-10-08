<?php
/**
 * @package String
 */

/**
 * Loads bootstrap script
 */
require_once 'init.inc.php';


/**
 * Dynamic strings test unit
 *
 * @package String
 * @subpackage Dynamic
 */
class String_Dynamic_Test extends PHPUnit_Framework_TestCase
{
    protected $_test_string = "[let's|let us] test \[th\|is\] [dynamic|] string";
    protected $_fixture;


    /**
     * Sets up test dynamic string
     */
    protected function setUp()
    {
        $this->_fixture = new String_Dynamic($this->_test_string);
    }


    /**
     * Tests composing strings
     */
    public function test_generating()
    {
        for ($i = 8; $i; $i--) {
            $s = $this->_fixture->get();
            $l = strlen($s);
            $this->assertTrue(26 <= $l && 37 >= $l);
        }
    }
}
