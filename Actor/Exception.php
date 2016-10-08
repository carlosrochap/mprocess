<?php
/**
 * @package Actor
 */

/**
 * @package Actor
 * @subpackage Exception
 */
class Actor_Exception extends Base_Exception
{
    const PROXY_BANNED = 0x00010001;

    const INVALID_CREDENTIALS       = 0x00010002;
    const ACCOUNT_NOT_CONFIRMED     = 0x00010003;
    const ACCOUNT_ALREADY_CONFIRMED = 0x00010004;
    const ACCOUNT_SUSPENDED         = 0x00010005;

    const RECIPIENT_NOT_FOUND       = 0x00010006;
    const RECIPIENT_SUSPENDED       = 0x00010007;
    const RECIPIENT_PROTECTED       = 0x00010008;
    const RECIPIENT_NOT_INVITED     = 0x00010009;
    const RECIPIENT_ALREADY_INVITED = 0x00010010;
    const RECIPIENT_NOT_MUTUAL      = 0x00010011;

    const MESSAGE_BLACKLISTED = 0x00010012;

    const LIMIT_REACHED = 0x00010013;
}
