<?php
/**
 * @package Service
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


/**
 * DBC service text
 *
 * @package Service
 * @subpackage DeathByCaptcha
 */
class Service_DeathByCaptcha_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests DBC queue stats loading
     */
    public function test_queue_stats_loading()
    {
        $stats = Service_DeathByCaptcha::get_queue_stats();
        $this->assertTrue(!empty($stats) && is_array($stats));
        $this->assertTrue(0 < $stats['queue']['per_minute']);
    }

    /**
     * Tests DBC queue stats caching
     */
    public function test_queue_stats_caching()
    {
        $stats = Service_DeathByCaptcha::get_queue_stats();
        sleep(Service_DeathByCaptcha::TIMEOUT - 10);
        $this->assertEquals($stats, Service_DeathByCaptcha::get_queue_stats());
    }
}
