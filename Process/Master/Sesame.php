<?php

abstract class Process_Master_Sesame extends Process_Master_Abstract
{
    protected function _fetch_message()
    {
        $msg = array(
            'domain'  => $this->get_pool('Domain')->get(),
            'subject' => $this->get_pool('Message')->get('subject'),
        );
        $msg['message'] = str_replace(
            '[PROFILE]',
            $msg['domain'],
            isset($this->_config['msg']['id'])
                ? $this->get_pool('DynamicMessage')->get($this->_config['msg']['id'])
                : $this->get_pool('Message')->get()
        );
        return $msg;
    }


    protected function _handle_module_send_message($pid)
    {
        $msg = array_merge($this->_fetch_message(), array(
            'recipient' => $this->get_pool('Recipient')->get(),
            'proxy'     => $this->get_pool('Proxy')->get(),
        ));
        if (!$msg['recipient']) {
            $this->log('No recipients to message',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        } else if (!$msg['proxy']) {
            $this->log('No proxies to use',
                       Log_Abstract::LEVEL_ERROR);
            return false;
        }
        return $msg;
    }


    protected function _handle_action_disable_recipient($recipient)
    {
        $this->log("Disabling recipient {$recipient}");
        return $this->get_pool('Recipient')->disable($recipient);
    }

    protected function _handle_action_add_message($recipient)
    {
        $this->log("Adding a message to {$recipient}");
        return $this->get_pool('Message')->add($recipient);
    }

    protected function _handle_action_get_name(array $payload, $pid)
    {
        $this->log("Fetching random {$payload['type']} name of {$payload['nationality']} {$payload['gender']}");
        return $this->_send(
            'get_name',
            $this->get_pool(ucfirst($payload['type']) . 'Name')->get(
                $payload['gender'],
                $payload['nationality']
            ),
            $pid
        );
    }
}
