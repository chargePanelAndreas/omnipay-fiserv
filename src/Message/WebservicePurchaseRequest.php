<?php
/**
 * First Data Webservice Purchase Request
 */
namespace Omnipay\FiservArg\Message;

/**
 * First Data Webservice Purchase Request
 *
 * ### Example
 *
 * <code>
 * // Create a gateway for the First Data Webservice Gateway
 * // (routes to GatewayFactory::create)
 * $gateway = Omnipay::create('FirstData_Webservice');
 *
 * // Initialise the gateway
 * $gateway->initialize(array(
 *     'sslCertificate'    => 'WS9999999._.1.pem',
 *     'sslKey'            => 'WS9999999._.1.key',
 *     'sslKeyPassword'    => 'sslKEYpassWORD',
 *     'userName'          => 'WS9999999._.1',
 *     'password'          => 'passWORD',
 *     'testMode'          => true,
 * ));
 *
 * // Create a credit card object
 * $card = new CreditCard(array(
 *     'firstName'            => 'Example',
 *     'lastName'             => 'Customer',
 *     'number'               => '4222222222222222',
 *     'expiryMonth'          => '01',
 *     'expiryYear'           => '2020',
 *     'cvv'                  => '123',
 *     'email'                => 'customer@example.com',
 *     'billingAddress1'      => '1 Scrubby Creek Road',
 *     'billingCountry'       => 'AU',
 *     'billingCity'          => 'Scrubby Creek',
 *     'billingPostcode'      => '4999',
 *     'billingState'         => 'QLD',
 * ));
 *
 * // Do a purchase transaction on the gateway
 * $transaction = $gateway->purchase(array(
 *     'accountId'                 => '12345',
 *     'amount'                    => '10.00',
 *     'transactionId'             => 12345,
 *     'clientIp'                  => $_SERVER['REMOTE_ADDR'],
 *     'card'                      => $card,
 * ));
 * $response = $transaction->send();
 * if ($response->isSuccessful()) {
 *     echo "Purchase transaction was successful!\n";
 *     $sale_id = $response->getTransactionReference();
 *     echo "Transaction reference = " . $sale_id . "\n";
 * }
 * </code>
 */
class WebservicePurchaseRequest extends WebserviceAbstractRequest
{
    /** @var string XML template for the purchase request */
    protected $xmlTemplate = '
<ipgapi:IPGApiOrderRequest xmlns:v1="http://ipg-online.com/ipgapi/schemas/v1" 
    xmlns:ipgapi="http://ipg-online.com/ipgapi/schemas/ipgapi">
    <v1:Transaction>
        <v1:CreditCardTxType>
            <v1:StoreId>%store_id%</v1:StoreId>
            <v1:Type>%txn_type%</v1:Type>
        </v1:CreditCardTxType>
        <v1:CreditCardData>
            <v1:CardCodeValue>%cvd_code%</v1:CardCodeValue>
        </v1:CreditCardData>
        <v1:Payment>
            <v1:HostedDataID>%hosted_data_id%</v1:HostedDataID>
            <v1:ChargeTotal>%amount%</v1:ChargeTotal>
            <v1:Currency>%currency%</v1:Currency>
        </v1:Payment>
        <v1:TransactionDetails>
            <v1:OrderId>%reference_no%</v1:OrderId>
        </v1:TransactionDetails>
    </v1:Transaction>
</ipgapi:IPGApiOrderRequest>
';

    /** @var string Transaction type */
    protected $txn_type = 'sale';

    /**
     * Get the request accountId
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->getParameter('accountId');
    }

    /**
     * Set the request accountId
     *
     * @param string $value
     * @return WebserviceAbstractRequest provides a fluent interface.
     */
    public function setAccountId($value)
    {
        return $this->setParameter('accountId', $value);
    }

    public function getData()
    {
        $data = parent::getData();

        $this->validate('amount', 'card', 'transactionId', 'storeId', 'hostedDataId', 'currency');

        $data['txn_type']           = $this->txn_type;
        $data['amount']             = $this->getAmount();
        $data['reference_no']       = $this->getTransactionId();
        $data['store_id']           = $this->getStoreId();
        $data['hosted_data_id']     = $this->getHostedDataId();
        $data['currency']           = $this->getCurrencyNumeric();
        $data['cvd_code']           = $this->getCard()->getCvv();

        return $data;
    }
}
