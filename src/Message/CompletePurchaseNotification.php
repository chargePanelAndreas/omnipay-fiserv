<?php
/**
 * First Data Connect Complete Purchase Request
 */

namespace Omnipay\FiservArg\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\NotificationInterface;


/**
 * First Data Connect Complete Purchase Request
 */
class CompletePurchaseNotification extends AbstractRequest implements NotificationInterface
{
    /**
     * @var string|null
     */
    private $message;
    private ?string $eventType;
    private bool $success = false;

    function getTransactionStatus()
    {
        $status = $this->httpRequest->request->get('status');
        return match ($status) {
            'APPROVED' => NotificationInterface::STATUS_COMPLETED,
            'APROBADO' => NotificationInterface::STATUS_COMPLETED,
            'DECLINED' => NotificationInterface::STATUS_FAILED,
            'RECHAZADO' => NotificationInterface::STATUS_FAILED,
            default => NotificationInterface::STATUS_PENDING
        };
    }

    function getMessage()
    {
        return $this->message;
    }

    function getCode()
    {
        if($this->success)
        {
            return 200;
        }
        else
        {
            return 400;
        }
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

    public function sendData($data): self
    {
        return $this;
    }

    public function isSuccessful()
    {
        $status = $this->httpRequest->request->get('status');
        if ($status !== 'APPROVED' && $status !== 'APROBADO') {
            return false;
        }
        return true;
    }

    public function createResponseHash(array $data, array $order): string
    {
        $this->validate('storeId', 'sharedSecret');

        $data['storename'] = $this->getStoreId();

        return $this->createHash($data, $order);
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

    public function getSharedSecret(): string
    {
        return (string) $this->getParameter('sharedSecret');
    }

    public function getStoreId(): string
    {
        return (string) $this->getParameter('storeId');
    }
}
