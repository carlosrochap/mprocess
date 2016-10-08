<?php

abstract class Service_DeathByCaptcha
{
    const TIMEOUT = 90;


    static protected $_co = null;

    static protected $_queue_stats = array();
    static protected $_queue_stats_fetched = 0;


    static public function get_queue_stats()
    {
        if (
            (time() - self::TIMEOUT > self::$_queue_stats_fetched) ||
            empty(self::$_queue_stats)
        ) {
            if (!self::$_co) {
                self::$_co = new Connection_Curl();
            }
            try {
                self::$_queue_stats = json_decode(self::$_co->ajax('http://www.qlinkgroup.com/op/info'), true);
            } catch (Connection_Exception $e) {
                self::$_queue_stats = null;
            }
            self::$_queue_stats_fetched = time();
        }
        return self::$_queue_stats;
    }

    static public function is_busy()
    {
        $a = self::get_queue_stats();
        return ($a && ($a['queue']['per_minute']) / 3 > $a['operators']['logged_in']);
    }
}
