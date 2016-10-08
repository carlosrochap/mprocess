<?php
/**
 * @package Log
 */

/**
 * Loads required bootstrap script
 */
require_once 'init.inc.php';


/**
 * Loggers test case
 *
 * @package Log
 */
class Log_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests factory for correct loggers name handling
     */
    public function test_factory()
    {
        foreach (array('file', 'db') as $k) {
            $log = Log_Factory::factory($k);
            $class = "Log_" . ucfirst($k);
            $this->assertTrue($log instanceof $class);
            unset($log);
        }
    }
}
