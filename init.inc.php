<?php
/**
 * Essential initialization script
 *
 * @package Base
 */

error_reporting(E_ALL);

$lib_dir = realpath('..' . DIRECTORY_SEPARATOR . 'lib');
set_include_path(implode(PATH_SEPARATOR, array(
    str_replace(
        PATH_SEPARATOR . '/usr/local/lib/guillie-php',
        PATH_SEPARATOR,
        get_include_path()
    ),
    realpath('.' . DIRECTORY_SEPARATOR . 'lib'),
    $lib_dir,
    $lib_dir . DIRECTORY_SEPARATOR . 'vendors'
)));
unset($lib_dir);


/**
 * Environment-aware utility class
 */
require_once 'Environment.php';


spl_autoload_register(array('Environment', 'load_class'));

// Load project configuration if found
$fn = 'config.ini';
$config = (file_exists($fn) && is_readable($fn))
    ? parse_ini_file($fn, true)
    : array();
unset($fn);
