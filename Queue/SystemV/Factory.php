<?php

abstract class Queue_SystemV_Factory
{
    static protected $_queues = array();


    static public function factory($key, Log_Interface $log=null)
    {
        $queue = &self::$_queues[$key];
        if (!$queue) {
            $queue = new Queue_SystemV($key);
        }
        if ($log) {
            $queue->log = $log;
        }
        return $queue;
    }

    static public function close($key)
    {
        self::factory($key)->close();
        unset(self::$_queues[$key]);
        return true;
    }
}
