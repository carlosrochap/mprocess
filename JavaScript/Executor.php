<?php
/**
 * @package JavaScript
 */

/**
 * @package JavaScript
 * @subpackage Executor
 */
class JavaScript_Executor
{
    /**
     * Command to run a JavaScript interpreter, must support pipes and stdin as input
     *
     * @var string
     */
    const CMD = '/usr/local/bin/js';


    /**
     * Executes an arbitrary JavaScript script file using interpreter defined at
     * {@self ::CMD} and optional arguments
     *
     * @param string $fn   Script file name
     * @param array  $args Optional script arguments
     * @return string|false
     */
    static public function execute($fn, array $args=array())
    {
        $pipes = null;
        $proc = proc_open(self::CMD . ' ' . escapeshellarg($fn), array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        ), $pipes, null);
        if (is_resource($proc)) {
            foreach ($args as &$arg) {
                fwrite($pipes[0], "{$arg}\n");
            }
            fclose($pipes[0]);
            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            if (!proc_close($proc)) {
                return rtrim($result);
            }
        }
        return false;
    }
}
