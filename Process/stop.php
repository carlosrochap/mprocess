<?php
/**
 * Stops master/slaves processes batch
 *
 * @package Process
 */

/**
 * Essential setup
 */
require_once 'init.inc.php';


function get_queue_key_from_file($fn)
{
    $key = false;
    if ($f = @fopen($fn, 'r')) {
        $s = fread($f, 1024);
        if ($s && preg_match('#Msg queue (\d+)#', $s, $m)) {
            $key = (int)$m[1];
        }
        fclose($f);
    }
    return $key;
}


try {
    Environment::check_extensions('sysvmsg');
} catch (Exception $e) {
    fputs(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$log = Log_Factory::factory(@$config['log']['logger']);

$queue_keys = array();

foreach ((
    (1 < $argc)
        ? @getopt('hvq:m:f:', array(
            'help',
            'verbose',
            'queue:',
            'module:',
            'log-file:'
          ))
        : array('help' => true)
) as $k => $v) {
    if (!is_array($v)) {
        $v = array($v);
    }

    switch ($k) {
    case 'h':
    case 'help':
        fputs(STDOUT, "Usage: php {$argv[0]} [options]

Options:
-h  --help     This help.
-v  --verbose  Be more verbose about what's going on.

At least one of the following options is requred:
-q {key}     --queue={key}      Message queue key.
-m {module}  --module={module}  Module name.
-f {file}    --log-file={file}  Log file name.\n");
        exit();

    case 'v':
    case 'verbose':
        $log->is_verbose = true;
        break;

    case 'm':
    case 'module':
        foreach ($v as $s) {
            foreach (glob('log' . DIRECTORY_SEPARATOR . $s . '*') as $fn) {
                if ($key = get_queue_key_from_file($fn)) {
                    $queue_keys[] = $key;
                }
            }
        }
        break;

    case 'f':
    case 'log-file':
        foreach ($v as $fn) {
            if ($key = get_queue_key_from_file($fn)) {
                $queue_keys[] = $key;
            }
        }
        break;

    case 'q':
    case 'queue':
        foreach ($v as $key) {
            $key = max(0, (int)$key);
            if ($key) {
                $queue_keys[] = $key;
            }
        }
        break;
    }
}

if (!count($queue_keys)) {
    $log->error('Message queue key(s) required');
    exit(1);
}

$pid = Environment::get_pid();
foreach ($queue_keys as &$key) {
    $log->info("Msg queue {$key}");
    $queue = Queue_Factory::factory('SystemV', $key);
    if (!$queue->is_valid()) {
        $log->error("Failed opening message queue {$key}");
    } else if (!$queue->send(1, array(
        'action' => 'stop',
        'data'   => null,
        'from'   => Environment::get_pid(),
    ))) {
        $log->error("Failed sending stop order via queue {$key}");
    }
}
