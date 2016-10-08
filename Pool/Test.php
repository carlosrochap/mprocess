<?php
/**
 * @package Pool
 */

/**
 * Loads required bootstrap script
 */
require_once 'init.inc.php';


/**
 * Dummy pool to test pool abstract class
 *
 * @package Pool
 */
class Pool_Dummy extends Pool_Abstract
{
    /**
     * Fills test pool with numbers
     */
    protected function _fill_number($max)
    {
        return range(1, $max);
    }
}

/**
 * Dummy file-based pool for test purposes
 *
 * @package Pool
 */
class Pool_File_Dummy extends Pool_File_Abstract
{
    /**
     * @ignore
     */
    public function dump()
    {
        var_dump($this->_pools);
    }
}


/**
 * Generic pools test case
 *
 * @package Pool
 */
class Pool_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests pool getters
     */
    public function test_getter()
    {
        $pool = new Pool_Dummy();

        $this->assertFalse($pool->get('undefined_pool'));
        $this->assertFalse($pool['undefined_pool']);

        $size = rand(5, 15);
        $pool->set_size($size);
        $pool->clear('number');

        $numbers = array();
        for ($i = $size; $i; $i--) {
            $numbers[] = $pool->get('number');
        }
        sort($numbers);
        $this->assertEquals($numbers, range(1, $size));

        $numbers = array();
        for ($i = $size; $i; $i--) {
            $numbers[] = $pool['number'];
        }
        sort($numbers);
        $this->assertEquals($numbers, range(1, $size));
    }

    /**
     * Tests file-based pool
     */
    public function test_file_based_pool()
    {
        chdir(dirname(__FILE__));
        $name = strtolower(basename(__FILE__, '.php'));
        $src = file(
            dirname(__FILE__) . DIRECTORY_SEPARATOR . $name . '.txt',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );
        sort($src);

        $pool = new Pool_File_Dummy();
        $pool->size = count($src);
        for ($i = 2; $i; $i--) {
            $a = array();
            for ($j = $pool->size; $j; $j--) {
                $a[] = $pool->get($name);
            }
            sort($a);
            $this->assertEquals($a, $src);
        }
    }
}
