<?php
/**
 * First Data Connect Complete Purchase Response
 */

namespace Omnipay\FiservArg\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * First Data Connect Complete Purchase Response
 */
class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        return isset($this->data['status']) && $this->data['status'] == 'APROBADO';
    }

    public function getTransactionId(): ?string
    {
        return $this->data['oid'] ?? null;
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['refnumber'] ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->data['status'] ?? null;
    }
}
