<?php
/**
 * Fiserv Argentina Connect Purchase Request
 */

namespace Omnipay\FiservArg\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Fiserv Argentina Connect Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    protected string $liveEndpoint = 'https://www2.ipg-online.com/connect/gateway/processing';
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
     * Possible values:
     * Language Value
     * Chinese (simplified) zh_CN
     * Chinese (traditional) zh_TW
     * Czech cs_CZ
     * Danish da_DK
     * Dutch nl_NL
     * English (USA) en_US
     * English (UK) en_GB
     * Finnish fi_FI
     * French fr_FR
     * German de_DE
     * Greek el_GR
     * Hungarian hu_HU
     * Italian it_IT
     * Japanese ja_JP
     * Norwegian (Bokmål) nb_NO
     * Polish pl_PL
     * Portuguese (Brazil) pt_BR
     * Serbian (Serbia) sr_RS
     * Slovak sk_SK
     * Slovenian sl_SI
     * Spanish (Spain) es_ES
     * Spanish (Mexico) es_MX
     * Swedish sv_SE
     *
     * @param $value
     * @return self
     */
    public function setLanguage(?string $value): self
    {
        if (empty($value)) {
            // Default to English if no language is provided
            $value = 'en_US';
        }
        // Normalize the language code to lowercase, underscore, uppercase like xx_XX
        $value = strtolower(substr($value, 0, 2)) . '_' . strtoupper(substr($value, 3, 2));

        // Validate the language code
        $validLanguages = [
            'zh_CN', 'zh_TW', 'cs_CZ', 'da_DK', 'nl_NL', 'en_US', 'en_GB',
            'fi_FI', 'fr_FR', 'de_DE', 'el_GR', 'hu_HU', 'it_IT', 'ja_JP',
            'nb_NO', 'pl_PL', 'pt_BR', 'sr_RS', 'sk_SK', 'sl_SI',
            'es_ES', 'es_MX', 'sv_SE'
        ];
        if (!in_array($value, $validLanguages)) {
            // Default to English if the provided language is not valid
            $value = 'en_US';
        }
        return $this->setParameter('language', $value);
    }

    public function getLanguage(): string
    {
        return $this->getParameter('language') ?? 'en_US'; // Default to English if not set
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
            'checkoutoption' => 'combinedpage',
            'chargetotal' => $this->getAmount(),
            'currency' => $this->getCurrencyNumeric(),
            'responseSuccessURL' => $this->getReturnUrl(),
            'responseFailURL' => $this->getCancelUrl(),
            'language' => $this->getLanguage(),
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
        $sharedSecret = $this->getSharedSecret();
        $hash = hash_hmac('sha256', $stringToHash, $this->getSharedSecret(), true);

        return base64_encode($hash);
    }

    public function getCurrencyNumeric(): ?string
    {
        return str_pad(parent::getCurrencyNumeric(), 3, '0', STR_PAD_LEFT);
    }

    public function sendData($data): RedirectResponseInterface
{
    $endpoint = $this->getEndpoint();

    // FIXED: Changed to application/x-www-form-urlencoded
    $httpResponse = $this->httpClient->request(
        'POST',
        $endpoint,
        [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        http_build_query($data) // FIXED: Changed from json_encode to http_build_query
    );
    $location = $httpResponse->getHeader('Location')[0] ?? null;
    return $this->createResponse($location);
}

    public function getEndpoint(): string
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    protected function createResponse($location)
    {
        return $this->response = new PurchaseResponse($this,$location);
    }
}
