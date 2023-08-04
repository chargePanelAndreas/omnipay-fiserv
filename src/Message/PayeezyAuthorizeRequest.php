<?php
/**
 * First Data Payeezy Authorize Request
 */

namespace Omnipay\FiservArg\Message;

/**
 * First Data Payeezy Authorize Request
 */
class PayeezyAuthorizeRequest extends PayeezyPurchaseRequest
{
    protected $action = self::TRAN_PREAUTH;
}
