<?php
/**
 * Imports profiles
 *
 * @package Process
 */

/**
 * Required essential setup
 */
require_once 'init.inc.php';


$log = Log_Factory::factory(@$config['log']['logger']);
$log->is_verbose = (bool)@$config['project']['test'];

$project_name = ucfirst($config['project']['name']);
try {
    $user_details =
        Pool_Factory::factory("{$project_name}_UserInfo", $log, $config);
} catch (BadMethodCallException $e) {
    $user_details = Pool_Factory::factory('UserInfo', $log, $config);
}
$profiles =
    Pool_Factory::factory("{$project_name}_Profile", $log, $config);
if (!$cnt = max(0, (int)@$argv[1])) {
    $cnt = $user_details->get_size();
}
while ($cnt && ($profile = $user_details->get())) {
    if ($profiles->add($profile)) {
        $cnt--;
    }
}
