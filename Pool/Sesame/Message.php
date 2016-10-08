<?php

abstract class Pool_Sesame_Message extends Pool_Sesame_Abstract
{
    protected $_stmts = array(
        'add' => '
            INSERT INTO `[PROJECT]_clients`
            SET `client` = ?,
                `locked` = 0,
                `is_messaged` = 1,
                `is_invalid` = 0,
                `sent_at` = NOW()
            ON DUPLICATE KEY UPDATE
               `locked` = VALUES(`locked`),
               `is_messaged` = VALUES(`is_messaged`),
               `is_invalid` = VALUES(`is_invalid`),
               `sent_at` = VALUES(`sent_at`)
        ',
    );


    public function get($key='message')
    {
        return parent::get($key);
    }
}
