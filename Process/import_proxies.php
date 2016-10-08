<?php
/**
 * Imports proxies from arbitrary source
 *
 * @package Process
 */

/**
 * Bootstrap script
 */
require_once 'init.inc.php';


$log = Log_Factory::factory(@$config['log']['logger']);
$log->is_verbose = (bool)@$config['project']['test'];

try {
    $pool = Pool_Factory::factory(
        ucfirst($config['project']['name']) . '_Proxy',
        $log,
        $config
    );
} catch (BadMethodCallException $e) {
    $log->error('Failed creating a proxies pool');
    exit(-1);
}
while ($host = trim(fgets(STDIN))) {
    $pool->add($host);
}
