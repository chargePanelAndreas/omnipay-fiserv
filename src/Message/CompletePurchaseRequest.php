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
        if (!empty($this->httpRequest->request->get('notification_hash'))) {
            $order = ['chargetotal', 'currency', 'txndatetime', 'storename', 'approval_code'];
            $theirHash = (string) $this->httpRequest->request->get('notification_hash');
        } else {
            $order = ['approval_code', 'chargetotal', 'currency', 'txndatetime', 'storename'];
            $theirHash = (string) $this->httpRequest->request->get('response_hash');
        }

        $ourHash   = $this->createResponseHash($this->httpRequest->request->all(), $order);

        if ($theirHash !== $ourHash) {
            throw new InvalidResponseException("Callback hash does not match expected value $ourHash");
        }
    }

    public function sendData($data): ResponseInterface
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    public function createResponseHash(array $data, array $order): string
    {
        $this->validate('storeId', 'sharedSecret');

        $data['storename'] = $this->getStoreId();

        return $this->createHash($data, $order);
    }
}
