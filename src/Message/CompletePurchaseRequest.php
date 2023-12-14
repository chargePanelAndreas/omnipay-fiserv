<?php
/**
 * First Data Connect Complete Purchase Request
 */

namespace Omnipay\FiservArg\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\ResponseInterface;

/**
 * First Data Connect Complete Purchase Request
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    public function getData(): array
    {
        $this->validateResponseHash();

        return $this->httpRequest->request->all();
    }

    private function validateResponseHash(): void
    {
        $theirHash = (string) ($this->httpRequest->request->get('notification_hash') ?: $this->httpRequest->request->get('response_hash'));
        $ourHash   = $this->createResponseHash($this->httpRequest->request->all());

        if ($theirHash !== $ourHash) {
            throw new InvalidResponseException("Callback hash does not match expected value $ourHash");
        }
    }

    public function sendData($data): ResponseInterface
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    public function createResponseHash(array $data): string
    {
        $this->validate('storeId', 'sharedSecret');

        $data['storename'] = $this->getStoreId();

        $order = [
            'approval_code', 'chargetotal', 'currency', 'txndatetime', 'storename',
        ];

        return $this->createHash($data, $order);
    }
}
