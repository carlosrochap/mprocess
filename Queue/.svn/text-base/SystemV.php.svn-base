<?php

class Queue_SystemV extends Queue_Abstract
{
    const MAX_MESSAGE_SIZE = 32768;


    protected $_queue = null;


    public function __construct($key)
    {
        if (!defined('MSG_EAGAIN')) {
            define('MSG_EAGAIN', 0x0b);
        }
        $this->_queue = msg_get_queue($key);
        if (!$this->_queue) {
            throw new Queue_Exception(
                "Message queue {$key} not found",
                Queue_Exception::INVALID_ARGUMENT
            );
        }
    }

    public function close()
    {
        if ($this->_queue) {
            msg_remove_queue($this->_queue);
        }
    }

    public function is_valid()
    {
        if ($this->_queue) {
            $a = msg_stat_queue($this->_queue);
            return (bool)$a['msg_qbytes'];
        } else {
            return false;
        }
    }

    public function pack($msg)
    {
        return serialize($msg);
    }

    public function unpack($msg)
    {
        return unserialize($msg);
    }

    public function send($recipient, $payload=null)
    {
        if (!$this->_queue) {
            throw new Queue_Exception(
                'No queues to use',
                Queue_Exception::IO_ERROR
            );
        }

        $this->log($payload, Log_Abstract::LEVEL_DEBUG);
        $msg = $this->pack($payload);
        $errno = 0;
        while (!@msg_send(
            $this->_queue,
            $recipient,
            $msg,
            false,
            false,
            $errno
        )) {
            if (MSG_EAGAIN != $errno) {
                return false;
            }
            sleep(1);
        }
        return true;
    }

    public function receive($recipient, $block=true)
    {
        if (!$this->_queue) {
            throw new Queue_Exception(
                'No queues to use',
                Queue_Exception::IO_ERROR
            );
        }

        $msg = null;
        $msgtype = $errno = 0;
        return @msg_receive(
            $this->_queue,
            $recipient,
            $msgtype, 
            self::MAX_MESSAGE_SIZE,
            $msg,
            false,
            ($block ? 0 : MSG_IPC_NOWAIT),
            $errno
        )
            ? $this->unpack($msg)
            : null;
    }
}
