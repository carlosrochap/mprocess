<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage MetaCafe
 */
class Actor_MetaCafe extends Actor_Http_Abstract
{
    const HOST = 'http://www.metacafe.com';


    /**
     * @see Actor_Http_Abstract::login()
     */
    public function login($user_id, $pass)
    {
        return false;
    }
}
