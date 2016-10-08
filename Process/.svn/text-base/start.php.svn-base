<?php
/**
 * Starts master/slaves process batch
 *
 * @package Process
 */

/**
 * Essential setup
 */
require_once 'init.inc.php';


try {
    Environment::check_extensions(array_merge(array(
        'curl',
        'dom',
        'iconv',
        'json',
        'mbstring',
        'mysqli',
        'sysvmsg',
    ), !empty($config['project']['extensions'])
        ? array_filter(explode(',', $config['project']['extensions']))
        : array()));
} catch (Exception $e) {
    fputs(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$log = Log_Factory::factory(@$config['log']['logger']);

$module = '';
$queue_key = 0;
$slaves_count = 1;

foreach ((
    (1 < $argc)
        ? @getopt('hvdm:c:', array(
            'help',
            'verbose',
            'debug',
            'module:',
            'count:',
          ))
        : array('help' => false)
) as $k => $v) {
    switch ($k) {
    case 'h':
    case 'help':
        fputs(STDOUT, 'Usage: php ' . basename($argv[0]) . " [options]

Options:
-h        --help          This help.
-v        --verbose       Be more verbose about what's going on.
-d        --debug         Debug mode, trace every method call.
-m {name} --module={name} Name of a module to run
-c {n}    --count={n}     Number of slaves to spawn, defaults to {$slaves_count}.\n");
        exit();

    case 'v':
    case 'verbose':
        $log->is_verbose = true;
        break;

    case 'd':
    case 'debug':
        $func = 'xdebug_start_trace';
        if (function_exists($func)) {
            $func();
        } else {
            $log->error("{$func}() not found, tracing disabled");
        }
        break;

    case 'm':
    case 'module':
        $module = (false !== ($i = strpos($v, '.')))
            ? substr($v, 0, $i)
            : $v;
        break;

    case 'c':
    case 'count':
        $slaves_count = max(1, (int)$v);
        break;
    }
} 

if (!$module) {
    $log->error('Module not set');
    exit(2);
}

if (!$queue_key) {
    $queue_key = mt_rand();
}
$log->info("Msg queue {$queue_key}");
$queue = Queue_Factory::factory('SystemV', $queue_key, $log);
if (!$queue->is_valid()) {
    $log->error('Failed to open a message queue');
    exit(3);
}


function start_slave(array $config, Queue_Interface $queue, Log_Interface $log)
{
    $pid = @pcntl_fork();
    if (-1 == $pid) {
        $log->error('fork() failed');
        exit(4);
    } else if (0 < $pid) {
        return $pid;
    } else {
        // Start a new slave process
        try {
            Process_Slave_Factory::factory($config, $queue, $log)->start();
        } catch (Exception $e) {
            $log->error($e);
        }
        exit();
    }
}


while ($slaves_count--) {
    start_slave($config, $queue, $log);
}

// Master process
$master = Process_Master_Factory::factory($config, $queue, $log);
try {
    $master->module = $module;
} catch (Exception $e) {
    $log->error($e);
    $master->module = 'stop';
}

declare (ticks = 1);

function sig_handler($signo)
{
    global $master, $master_pid, $config, $queue, $log;
    if (SIGCHLD == $signo) {
        while ($zombie = Environment::get_zombie()) {
            if ($master->queue->send(1, array(
                'action' => 'gone',
                'data'   => null,
                'from'   => &$zombie,
            )) && ('stop' != $master->module)) {
                //start_slave($config, $queue, $log);
            }
        }
    } else {
        $log->error('Forced exit');
        $slaves = $master->slaves;
        $master->kill();
        Environment::kill($slaves);
    }
}

pcntl_signal(SIGTERM, 'sig_handler');
pcntl_signal(SIGINT, 'sig_handler');
pcntl_signal(SIGCHLD, 'sig_handler');

try {
    $master->start();
} catch (Exception $e) {
    $log->error($e);
}
Queue_Factory::close('SystemV', $queue_key);
