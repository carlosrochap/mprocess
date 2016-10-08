<?php
/**
 * @package String
 */

/**
 * @package String
 * @subpackage PostalCode
 */
abstract class String_PostalCode_Generator
{
    /**
     * Generates country specific zip code
     *
     * @return null
     */
    static public function generate($country)
    {
        try {
            return @call_user_func(array(
                str_replace('Generator', strtoupper($country), __CLASS__),
                'generate'
            ));
        } catch (BadMethodCallException $e) {
            return false;
        }
    }
}
