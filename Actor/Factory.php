<?php
/**
 * @package Actor
 */

/**
 * Actors' factory & cache
 *
 * @package Actor
 */
abstract class Actor_Factory
{
    /**
     * Actors cache
     *
     * @var array
     */
    static protected $_actors = array();


    /**
     * Fetches a (cached) actor instance
     *
     * @param string        $name Actor's name
     * @param Log_Interface $log  Logger
     * @return Actor_Abstract
     */
    static public function factory($name, Log_Interface $log=null)
    {
        $name = ucfirst(strtr($name, '\/:', '___'));
        if (!$actor = &self::$_actors[$name]) {
            $class = str_replace('_Factory', "_{$name}", __CLASS__);
            $actor = new $class();
        }
        if ($log) {
            $actor->set_log($log);
        }
        return $actor;
    }
}
