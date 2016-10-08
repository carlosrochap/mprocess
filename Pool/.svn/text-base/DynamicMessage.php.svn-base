<?php

class Pool_DynamicMessage extends Pool_Db_Abstract
{
    static protected $_tags_to_escape = array(
        '[SRCID]',
        '[DOMAIN]',
        '[PROFILE]',
        '[USERNAME]',
        '[FULLNAME]',
    );


    protected $_stmts = array(
        'get' => array('db' => 'globo07', 'query' => '
            SELECT `message`
            FROM `gamma_messages`
            WHERE `id` = ?
        '),
    );

    protected $_msgs = array();


    public function init()
    {
        $this->_msgs = array();
        return parent::init();
    }

    public function get($key='')
    {
        $msg = &$this->_msgs[$key];
        if (!$msg) {
            $a = $this->_execute('get', $key);
            if ($a) {
                $msg = new String_Dynamic(str_replace(
                    self::$_tags_to_escape,
                    array_map(
                        'addcslashes',
                        self::$_tags_to_escape,
                        array_pad(array(), count(self::$_tags_to_escape), '[]')
                    ),
                    $a[0]
                ));
            }
        }
        return $msg
            ? str_replace(array('\[', '\]'), array('[', ']'), $msg->get())
            : false;
    }
}
