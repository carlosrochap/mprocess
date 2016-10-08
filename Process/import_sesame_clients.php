<?php

require_once 'init.inc.php';


$log = new Log_File();
$log->is_verbose = true;

$db = Db_Factory::factory('sesame', $config);
$tbl = strtolower($config['project']['name']) . '_clients';
$stmt = "INSERT INTO `{$tbl}` (`client`) VALUES ";
while ($s = trim(fgets(STDIN))) {
    $log->info("Importing {$s}xxxx");
    $a = array();
    for ($i = 9999; 0 <= $i; $i--) {
        $a[] = "'{$s}" . str_pad($i, 4, '0', STR_PAD_LEFT) . "'";
    }
    if (!$db->query("{$stmt} (" . implode('), (', $a) . ')')) {
        $log->error("Import failed: {$db->errno} {$db->error}");
    }
}
