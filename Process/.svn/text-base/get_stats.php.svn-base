<?php
/**
 * @package Process
 */

/**
 * Required bootstrap script
 */
require_once 'init.inc.php';


$log = Log_Factory::factory(@$config['log']['logger']);
$log->is_verbose = (bool)@$config['project']['test'];

$pool = Pool_Factory::factory(
    ucfirst($config['project']['name']) . '_Stats',
    $log,
    $config
);
while ($stats = $pool->get()) {
    if ($stats['data']) {
        echo strtr(ucfirst($stats['name']), '_', ' ') . ': ' .
             implode('/', array_values($stats['data'])) . ' ' .
             implode('/', array_keys($stats['data'])) . "\n";
    }
}
