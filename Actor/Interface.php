<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Interface
 */
interface Actor_Interface
{
    /**
     * Logs in
     *
     * @param mixed  $user_id Project specific user ID or username
     * @param string $pass    Account's password
     * @return bool
     * @throws Actor_Exception When credentials are invalid,
     *                         when proxy's banned,
     *                         when account's suspended
     */
    public function login($user_id, $pass);

    /**
     * Logs out
     */
    public function logout();
}
