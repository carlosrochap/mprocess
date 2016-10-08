<?php

require_once 'vendors/BeanStalk.class.php';

class Queue_BeanStalk extends Queue_Abstract
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 11300;


    protected $_tube_prefix = '';
    protected $_queue = null;


    protected function _put($tube, $payload)
    {
        return BeanQueue::OPERATION_OK == $this->_queue->put(
            2 << 29,
            0,
            3,
            serialize($payload),
            $tube
        );
    }


    public function __construct($tube_prefix, $host=self::DEFAULT_HOST, $port=self::DEFAULT_PORT)
    {
        try {
            $this->_queue = BeanStalk::open(array(
                'servers'     => array("{$host}:{$port}"),
                'auto_unyaml' => false,
            ));
        } catch (Exception $e) {
            throw new Queue_Exception(
                "Failed opening BeanStalk queue on {$host}:{$port}: " . $e->getCode() . ' ' . $e->getMessage(),
                Queue_Exception::INVALID_ARGUMENT
            );
        }
        $this->_tube_prefix = $tube_prefix;
    }

    public function close()
    {
        $this->_tube_prefix = '';
        $this->_queue = null;
    }

    public function is_valid()
    {
        return null !== $this->_queue;
    }

    public function send($recipient, $payload=null)
    {
        if (!$this->is_valid()) {
            throw new Queue_Exception(
                'No queues to use',
                Queue_Exception::IO_ERROR
            );
        }
        $payload['to'] = $recipient;
        return $this->_put($this->_tube_prefix . '.' . ((1 == $recipient)
            ? 'master'
            : 'slaves'), $payload);
    }

    public function receive($recipient, $block=true)
    {
        if (!$this->is_valid()) {
            throw new Queue_Exception(
                'No queues to use',
                Queue_Exception::IO_ERROR
            );
        }
        $payload = null;
        $method = $block ? 'reserve' : 'reserve_with_timeout';
        $tube = $this->_tube_prefix . '.' . ((1 == $recipient)
            ? 'master'
            : 'slaves');
        $this->_queue->watch($tube);
        while ($job = $this->_queue->{$method}()) {
            $payload = unserialize($job->get());
            if ($recipient != $payload['to']) {
                $job->release(2 << 29);
                $payload = null;
            } else {
                $job->delete();
                unset($payload['to']);
                break;
            }
        }
        $this->_queue->ignore($tube, $reply);
        return $payload;
    }
}
