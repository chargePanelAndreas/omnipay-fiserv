<?php
/**
 * First Data Payeezy Capture Request
 */

namespace Omnipay\FiservArg\Message;

/**
 * First Data Payeezy Capture Request
 */
class PayeezyCaptureRequest extends PayeezyRefundRequest
{
    protected $action = self::TRAN_TAGGEDPREAUTHCOMPLETE;
}
