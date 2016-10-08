<?php
/**
 * @package Service
 */

/**
 * URL shorteners' factory
 *
 * @package Service
 * @subpackage UrlShortener
 */
abstract class Service_UrlShortener_Factory
{
    /**
     * URL shorteners pool
     *
     * @var array
     */
    static protected $_shorteners = array();


    /**
     * Returns a list of available URL shorteners
     *
     * @return array
     */
    static public function get_shorteners()
    {
        if (empty(self::$_shorteners)) {
            $names = array_diff(
                array_map(
                    'basename',
                    glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . '*.php')
                ),
                array(
                    'Abstract.php',
                    'Exception.php',
                    'Factory.php',
                    'Interface.php',
                    'Test.php'
                )
            );
            foreach ($names as &$s) {
                self::$_shorteners[str_replace('.php', '', $s)] = null;
            }
        }
        return array_keys(self::$_shorteners);
    }

    /**
     * Returns a (cached) URL shortener
     *
     * @param string $name       URL shortener's name, null for random
     * @param object $connection Connection for the shortener to use
     * @return Service_UrlShortener_Interface
     * @throws Service_UrlShortener_Exception When shortener class not found
     */
    static public function factory($name=null, Connection_Interface $connection=null)
    {
        if (!$name) {
            $names = self::get_shorteners();
            $name = $names[array_rand($names)];
        } else {
            $name = ucfirst($name);
        }
        if (!$shortener = &self::$_shorteners[$name]) {
            $class = str_replace('_Factory', "_{$name}", __CLASS__);
            $shortener = new $class();
        }
        if ($connection) {
            $shortener->set_connection($connection);
        }
        return $shortener;
    }

    /**
     * Shorten an URL using a (cached) URL shortener
     *
     * @param string $url        URL to shorten
     * @param string $name       Optional URL shortener's name, null for random
     * @param object $connection Connection for the shortener to use
     * @return string
     */
    static public function shorten($url, $name=null, Connection_Interface $connection=null)
    {
        return self::factory($name, $connection)->shorten($url);
    }
}
