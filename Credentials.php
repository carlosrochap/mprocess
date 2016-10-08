<?php
/**
 * @package Base
 */

/**
 * Simple credentials storage, just a collection limited to a few fields
 *
 * @property string $user
 * @property string $pass
 * @property bool   $is_valid Credentials validness flag to be set by
 *                            whoever used the credentials stored
 *
 * @package Base
 */
class Credentials extends Container
{
    /**
     * Created necessary fields
     */
    public function __construct()
    {
        $this->user = $this->pass = '';
    }
}
