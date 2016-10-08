<?php

abstract class Queue_BeanStalk_Factory
{
    static protected $_queues = array();


    static public function factory($key, Log_Interface $log=null, array $config=array())
    {
        $queue = &self::$_queues[$key];
        if (!$queue) {
            $queue = new Queue_BeanStalk(
                strtolower($config['project']['name']),
                $config['queue']['host'],
                $config['queue']['port']
            );
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
