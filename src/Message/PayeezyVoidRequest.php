<?php
/**
 * First Data Payeezy Void Request
 */

namespace Omnipay\FiservArg\Message;

/**
 * First Data Payeezy Void Request
 */
class PayeezyVoidRequest extends PayeezyRefundRequest
{
    protected $action = self::TRAN_TAGGEDVOID;
}
