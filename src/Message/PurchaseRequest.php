<?php
/**
 * Fiserv Argentina Connect Purchase Request
 */

namespace Omnipay\FiservArg\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\ResponseInterface;

/**
 * Fiserv Argentina Connect Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    protected string $liveEndpoint = 'https://www.ipg-online.com/connect/gateway/processing';
    protected string $testEndpoint = 'https://test.ipg-online.com/connect/gateway/processing';

    protected function getDateTime(): string
    {
        return date("Y:m:d-H:i:s");
    }

    /**
     * Set Store ID
     *
     * Calls to the Connect Gateway API are secured with a store ID and
     * shared secret.
     *
     * @return PurchaseRequest provides a fluent interface
     */
    public function setStoreId($value): self
    {
        return $this->setParameter('storeId', $value);
    }

    /**
     * Get Store ID
     *
     * Calls to the Connect Gateway API are secured with a store ID and
     * shared secret.
     *
     * @return string
     */
    public function getStoreId(): string
    {
        return (string) $this->getParameter('storeId');
    }

    /**
     * Set Shared Secret
     *
     * Calls to the Connect Gateway API are secured with a store ID and
     * shared secret.
     *
     * @return PurchaseRequest provides a fluent interface
     */
    public function setSharedSecret($value): self
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getSharedSecret(): string
    {
        return (string) $this->getParameter('sharedSecret');
    }

    public function setHostedDataId($value): self
    {
        return $this->setParameter('hostedDataId', $value);
    }

    public function getHostedDataId(): string
    {
        return (string) $this->getParameter('hostedDataId');
    }

    public function setCustomerId($value): self
    {
        return $this->setParameter('customerId', $value);
    }

    public function getCustomerId(): string
    {
        return (string) $this->getParameter('customerId');
    }

    public function setTransactionNotificationURL(string $url): self
    {
        return $this->setParameter('transactionNotificationURL', $url);
    }

    public function getTransactionNotificationURL(): string
    {
        return (string) $this->getParameter('transactionNotificationURL');
    }

    public function getData(): array
    {
        $this->validate('amount');
        $cardDataExists = !empty($this->getCard());

        $data = [
            'txntype' => 'sale',
            'timezone' => date_default_timezone_get(),
            'txndatetime' => $this->getDateTime(),
            'hash_algorithm' => 'HMACSHA256',
            'storename' => $this->getStoreId(),
            'mode' => 'payonly',
            'paymentMethod' => $this->getParameter('paymentMethod'),
            'chargetotal' => $this->getAmount(),
            'currency' => $this->getCurrencyNumeric(),
            'responseSuccessURL' => $this->getParameter('returnUrl'),
            'responseFailURL' => $this->getParameter('returnUrl'),
            'oid' => $this->getParameter('transactionId'),
            'taxRefundIndicator' => $this->getParameter('taxRefundIndicator'),
            'transactionNotificationURL' => $this->getTransactionNotificationURL(),
            'customerid' => $this->getCustomerId(),
            'hosteddataid' => $this->getHostedDataId(),
            'full_bypass' => $cardDataExists,
        ];

        if ($cardDataExists) {
            $this->validateCardData();

            $data['cardnumber'] = $this->getCard()->getNumber();
            $data['cvm']        = $this->getCard()->getCvv();
            $data['expmonth']   = $this->getCard()->getExpiryDate('m');
            $data['expyear']    = $this->getCard()->getExpiryDate('y');
        }

        $data['hashExtended'] = $this->createExtendedHash($data);

        return $data;
    }

    private function validateCardData()
    {
        // If no hosted data, or a number is passed, validate the whole card
        if (empty($this->getHostedDataId()) || ! is_null($this->getCard()->getNumber())) {
            $this->getCard()->validate();
        } elseif (is_null($this->getCard()->getCvv())) {
            // Else we only require the cvv when using hosted data
            throw new InvalidCreditCardException("The CVV parameter is required when using hosteddataid");
        }
    }

    public function createExtendedHash($data): string
    {
        $order = array_keys($data);
        sort($order);

        return $this->createHash($data, $order);
    }

    protected function createHash(array $data, array $order): string
    {
        $valuesToHash = array_map(function ($paramName) use ($data) {
            return $data[$paramName];
        }, $order);

        $stringToHash = implode('|', array_filter($valuesToHash));

        $hash = hash_hmac('sha256', $stringToHash, $this->getSharedSecret(), true);

        return base64_encode($hash);
    }

    public function getCurrencyNumeric(): ?string
    {
        return str_pad(parent::getCurrencyNumeric(), 3, '0', STR_PAD_LEFT);
    }

    public function sendData($data): ResponseInterface
    {
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}
