<?php
/**
 * @package Email
 */

/**
 * E-mail service providers' factory
 *
 * @package Email
 */
abstract class Email_Factory
{
    /**
     * Providers pool
     *
     * @var array
     */
    static public $_providers = array();


    /**
     * Guesses e-mail provider's names from e-mail address by
     * translating the host part like this: user@yahoo.com -> YahooCom
     *
     * @param string $email
     * @return string
     */
    static public function get_provider_names($email)
    {
        $names = array();
        $i = strrpos($email, '@');
        if (false !== $i) {
            $a = array_map('ucfirst', array_map('strtolower', explode(
                '.',
                substr($email, $i + 1)
            )));
            for ($i = count($a); $i; $i--) {
                $names[] = implode('', array_slice($a, 0, $i));
            }
        } else {
            $names[] = ucfirst($email);
        }
        return $names;
    }

    /**
     * Returns specific e-mail provider instance, cached one if exists
     *
     * @param string $name Provider's name or e-mail address to guess one by
     *                     {@link ::get_provider_names()}
     * @param Log_Interface $log Optional logger to use
     * @uses ::get_provider_names() To guess possible provider's names
     * from e-mail
     * @return Email_Abstract
     */
    static public function factory($name, Log_Interface $log=null)
    {
        $names = self::get_provider_names($name);
        foreach ($names as $name) {
            if (!$provider = &self::$_providers[$name]) {
                $class = str_replace('_Factory', "_{$name}", __CLASS__);
                try {
                    $provider = new $class();
                } catch (BadMethodCallException $e) {
                    continue;
                }
            }
            $provider->init();
            if ($log) {
                $provider->log = $log;
            }
            return $provider;
        }
        throw new Email_Exception(
            "Suitable provider for {$name} not found",
            Email_Exception::PROVIDER_NOT_FOUND
        );
    }
}
