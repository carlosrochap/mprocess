<?php

abstract class Pool_Sesame_Recipient extends Pool_Sesame_Abstract
{
    protected $_stmts = array(
        'unlock_recipients' => '
            UPDATE `[PROJECT]_clients`
            SET `locked` = 0
            WHERE `locked` = ?
        ',
        'lock_recipients' => '
            UPDATE `[PROJECT]_clients`
            SET `locked` = ?
            WHERE `locked` = 0 AND
                  `is_messaged` = 0 AND
                  `is_invalid` = 0
            LIMIT ?
        ',
        'get_locked_recipients' => '
            SELECT `client`
            FROM `[PROJECT]_clients`
            WHERE `locked` = ?
        ',
        'disable' => '
            UPDATE `[PROJECT]_clients`
            SET `locked` = 0,
                `is_messaged` = 0,
                `is_invalid` = 1,
                `sent_at` = NOW()
            WHERE `client` = ?
        ',
        'add' => '
            INSERT INTO `[PROJECT]_clients`
            SET `client` = ?
        ',
    );


    protected function _fill_recipient($size)
    {
        if ($this->_config['project']['test'] && isset(
            $this->_config['msg']['test_recipient']
        )) {
            return array($this->_config['msg']['test_recipient']);
        }

        $this->unlock_recipients($this->_pid);
        $this->lock_recipients($this->_pid, $size);
        return $this->get_locked_recipients($this->_pid);
    }


    public function close()
    {
        $this->unlock_recipients($this->_pid);
        return parent::close();
    }
}
