<?php
/**
 * @package Base
 */

/**
 * Base framework exception
 *
 * @package Base
 * @subpackage Exception
 */
class Base_Exception extends Exception
{
    const OK = 0x00000000;

    const INVALID_ARGUMENT = 0x00000001;

    const DB_ERROR      = 0x00000002;
    const IO_ERROR      = 0x00000003;
    const NETWORK_ERROR = 0x00000004;
    const SERVICE_ERROR = 0x00000005;

    const NOT_IMPLEMENTED = 0x00000006;


    public function __toString()
    {
        return
            $this->message . ' (' . get_class($this) . ' ' .
            '0x' . str_pad(dechex($this->code), 8, '0', STR_PAD_LEFT) . ')';
    }
}
