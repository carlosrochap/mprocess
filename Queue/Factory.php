<?php

abstract class Queue_Factory
{
    static public function get_subfactory_class($type)
    {
        $a = explode('_', __CLASS__);
        array_splice($a, count($a) - 1, 0, array(ucfirst($type)));
        return implode('_', $a);
    }

    static public function factory($type, $key, Log_Interface $log=null)
    {
        return call_user_func(array(
            self::get_subfactory_class($type),
            'factory'
        ), $key, $log);
    }

    static public function close($type, $key)
    {
        return call_user_func(array(
            self::get_subfactory_class($type),
            'close'
        ),  $key);
    }
}
