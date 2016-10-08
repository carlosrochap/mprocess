<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Break
 */
class Actor_Break extends Actor_Http_Abstract
{
    const HOST = 'http://www.break.com';


    /**
     * @see Actor_Http_Abstract::login()
     */
    public function login($user_id, $pass)
    {
        $this->get(self::HOST);
        $this->_dump('homepage.html');
        $this->user_id = $user_id;
        return true;
    }
}
