<?php

abstract class Process_Slave_Sesame extends Process_Slave_Abstract
{
    protected function _get_name($type='first', $gender=null, $nationality=null)
    {
        return $this->_send_and_receive('get_name', array(
            'type'        => &$type, 
            'gender'      => &$gender,
            'nationality' => &$nationality,
        ));
    }


    protected function _handle_action_send_message(array $msg)
    {
        $this->init();
        $this->set_proxy($msg['proxy']);

        $actor = $this->get_actor('Messenger');
        $actor->init();

        try {
            if ($actor->send(
                $msg['recipient'],
                $msg['message'],
                $msg['subject']
            )) {
                $this->_send('add_message', $msg['recipient']);
            }
        } catch (Actor_Exception $e) {
            $this->log($e, Log_Abstract::LEVEL_ERROR);
            if (in_array($e->getCode(), array(
                Actor_Exception::RECIPIENT_NOT_FOUND,
                Actor_Exception::RECIPIENT_PROTECTED,
                Actor_Exception::RECIPIENT_SUSPENDED
            ))) {
                $this->_send('disable_recipient', $msg['recipient']);
            }
        }

        return true;
    }


    public function get_first_name()
    {
        $args = func_get_args();
        return call_user_func_array(
            array($this, '_get_name'),
            array_merge(array('first'), $args)
        );
    }

    public function get_last_name()
    {
        $args = func_get_args();
        return call_user_func_array(
            array($this, '_get_name'),
            array_merge(array('last'), $args)
        );
    }
}
