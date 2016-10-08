<?php
/**
 * @package Base
 */

/**
 * A stub used when class was not found by the autoloader
 *
 * @package Base
 */
class NonExistent
{
    /**
     * Throws an exception showing the class doesn't exists
     *
     * @throws BadMethodCallException
     */
    public function __construct()
    {
        throw new BadMethodCallException(
            get_class($this) . ' does not exists'
        );
    }
}


/**
 * Environment-related methods and properties
 *
 * @package Base
 */
abstract class Environment
{
    /**
     * System path for temporary files
     *
     * @var string
     */
    static protected $_tmp_dir = '';


    /**
     * Returns OS independed process ID, falls back to zero
     *
     * @return int
     */
    static public function get_pid()
    {
        $func = 'posix_getpid';
        if (function_exists($func)) {
            return $func();
        } else {
            $func = 'win32_ps_stat_proc';
            if (function_exists($func)) {
                $a = $func();
                if ($a) {
                    return $a['pid'];
                }
            }
        }
        return 0;
    }

    /**
     * Checks for zombies, returns one zombie's PID if there are any
     * (so call in loop)
     *
     * @return int|false
     */
    static public function get_zombie()
    {
        $zombie = pcntl_wait($status, WNOHANG);
        return 0 < $zombie
            ? $zombie
            : false;
    }

    static public function kill($pids)
    {
        $killed = array();
        foreach ((is_array($pids) ? $pids : array($pids)) as $pid) {
            if (posix_kill($pid, SIGTERM)) {
                $killed[] = $pid;
            }
        }
        return $killed;
    }

    /**
     * Guesses system path for temporary files: looks for current directory's
     * 'tmp' subdirectory first, then tries sys_get_temp_dir() function and
     * falls back to tempnam() function.
     *
     * @return string
     */
    static public function get_tmp_dir()
    {
        if (!self::$_tmp_dir) {
            self::$_tmp_dir = getcwd() . DIRECTORY_SEPARATOR . 'tmp';
            if (!is_dir(self::$_tmp_dir) || !is_writable(self::$_tmp_dir)) {
                $func = 'sys_get_temp_dir';
                if (function_exists($func)) {
                    self::$_tmp_dir = $func();
                } else {
                    $fn = tempnam(null, mt_rand());
                    self::$_tmp_dir = dirname($fn);
                    @unlink($fn);
                }
            }
        }

        return self::$_tmp_dir;
    }

    /**
     * Constructs a temporary file name with arbitrary suffix
     *
     * @return string
     */
    static public function get_tmp_file_name($suffix)
    {
        return
            self::get_tmp_dir() . DIRECTORY_SEPARATOR .
            self::get_pid() . '.' . basename($suffix);
    }

    /**
     * Converts a space-separated list of values into proper array
     *
     * @param string $list
     * @return array
     */
    static public function get_space_separated_list($list)
    {
        return is_array($list)
            ? $list
            : array_filter(array_map('trim', explode(' ', $list)));
    }

    /**
     * Checks if required specified extensions are loaded
     *
     * @param string|array $extensions Either array or space-separated
     *                                 list of extensions
     * @return bool
     * @uses ::get_space_separated_list()
     * @throws RuntimeException When some extensions are not found
     */
    static public function check_extensions($extensions)
    {
        $extensions = self::get_space_separated_list($extensions);

        $not_loaded = array_diff($extensions, array_filter(
            $extensions,
            'extension_loaded'
        ));
        if (count($not_loaded)) {
            $not_loaded = array_diff($not_loaded, @array_filter(
                $not_loaded,
                'dl'
            ));
            if (count($not_loaded)) {
                throw new RuntimeException(
                    'Following required extensions not found: ' .
                    implode(', ', $not_loaded)
                );
            }
        }

        return true;
    }

    /**
     * Checks if required functions are present
     *
     * @param string|array $functinos Either array or space-separated
     *                                list of functions
     * @return bool
     * @uses ::get_space_separated_list()
     * @throws RuntimeException When some functions are not found
     */
    static public function check_functions($functions)
    {
        $functions = self::get_space_separated_list($functions);

        $not_found = array_diff($functions, array_filter(
            $functions,
            'function_exists'
        ));
        if (count($not_found)) {
            throw new RuntimeException(
                'Following required functions not found: ' .
                implode(', ', $not_found)
            );
        }

        return true;
    }

    /**
     * Automatically loads classes translating underscores to directory
     * separators
     *
     * @param string $class Class name
     * @throws BadMethodCallException When requested class not found
     *                                (thrown by the stub class constructor,
     *                                see {@link NonExistent::__constructor()})
     */
    static public function load_class($class)
    {
        if (!@include(strtr($class, '_', DIRECTORY_SEPARATOR) . '.php')) {
            // Have to define a class to throw an exception
            eval("class {$class} extends NonExistent {}");
        }
    }
}
