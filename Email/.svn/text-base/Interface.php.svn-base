<?php
/**
 * @package Email
 */

/**
 * E-mail services providers' interface
 *
 * @package Email
 */
interface Email_Interface
{
    /**
     * Logs into service
     *
     * @param mixed $username
     * @param mixed $pass
     * @return bool
     * @throws Email_Exception When not IO/net related errors occured
     */
    public function login($username, $pass);

    /**
     * Logs out of service
     */
    public function logout();

    /**
     * Returns a message or a part of a message selected by sender and
     * (optionally) subject
     *
     * @param string $from
     * @param string $subj
     * @param string $content_regex Optional regex to parse out some part
     *                              of message
     * @return string|false Message content or a part of message content
     *                      on success
     * @throws Email_Exception When not IO/net related errors occured
     */
    public function get_message($from, $subj=null, $content_regex=null);
}
