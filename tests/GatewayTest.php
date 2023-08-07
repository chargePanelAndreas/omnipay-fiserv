<?php

namespace Omnipay\FiservArg;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    private array $options;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new ConnectGateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setSharedSecret('96MbdNvxTa');
        $this->gateway->setStoreId('1120540155');

        $this->options = array(
            'amount' => '13.00',
            'returnUrl' => 'https://www.example.com/return',
            'card' => $this->getValidCard(),
            'transactionId' => 'abc123',
            'currency' => 'GBP',
            'customerId' => 54321
        );
    }

    public function testPurchase()
    {
        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertContains('ipg-online.com/connect/gateway/processing', $response->getRedirectUrl());
    }

    public function testCompletePurchaseSuccess()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'chargetotal' => '110.00',
                'response_hash' => '0nM0t9K6QV3Z+zEaQoVHZuNFPD+FZ/fD0kcdML4Tw3o=',
                'status' => 'APROBADO',
                'oid' => 'abc123456',
                'txndatetime' => '2013:09:27-16:06:26',
                'approval_code' => 'Y:136432:0013649958:PPXM:0015'
            )
        );

        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('abc123456', $response->getTransactionId());
        $this->assertSame('APROBADO', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
    }

    public function testCompletePurchaseInvalidCallbackPassword()
    {
        $this->expectException(InvalidResponseException::class);
        $this->getHttpRequest()->request->replace(
            array(
                'chargetotal' => '110.00',
                'response_hash' => 'FAKE',
                'status' => 'APPROVED',
                'oid' => 'abc123456',
                'txndatetime' => '2013:09:27-16:06:26',
                'approval_code' => 'Y:136432:0013649958:PPXM:0015'
            )
        );

        $this->gateway->completePurchase($this->options)->send();
    }

    public function testCompletePurchaseError()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'chargetotal' => '110.00',
                'response_hash' => 'P+j1yR9obVqpTqzWBgU3c50u800rRXTedHs6VSlMR5Y=',
                'status' => 'DECLINED',
                'oid' => 'abc1234',
                'txndatetime' => '2013:09:27-16:00:19',
                'approval_code' => 'N:05:DECLINED'
            )
        );

        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEquals('abc1234', $response->getTransactionId());
        $this->assertSame('DECLINED', $response->getMessage());
    }

    /**
     * testPurchaseWithHostedDataId.
     *
     * Simulates a purchase with "save this card" selected
     */
    public function testPurchaseWithHostedDataId()
    {
        $dataId = rand();
        $this->options['hostedDataId'] = $dataId;

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $requestData = $response->getRedirectData();
        $this->assertEquals($dataId, $requestData['hosteddataid']);
    }

    /**
     * testPurchaseWithHostedDataIdAndWithoutCardFailsWithoutCVV.
     *
     * Simulates paying using a saved card, rather than passing card data
     * This example is checking that an exception occurs if missing the CVV number
     *
     *
     */
    public function testPurchaseWithHostedDataIdAndWithoutCardFailsWithoutCVV()
    {
        $this->expectException(InvalidCreditCardException::class);

        $dataId = rand();
        $this->options['hostedDataId'] = $dataId;
        // Remove number to simulate repeat purchase
        unset($this->options['card']['number']);
        // Also remove required cvv to check for error
        unset($this->options['card']['cvv']);

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $requestData = $response->getRedirectData();
        $this->assertEquals($dataId, $requestData['hosteddataid']);
    }

    /**
     * testPurchaseWithHostedDataIdAndWithoutCard.
     *
     * Simulates paying using a saved card, rather than passing card data
     */
    public function testPurchaseWithHostedDataIdAndWithoutCard()
    {
        $dataId = rand();
        $this->options['hostedDataId'] = $dataId;
        unset($this->options['card']);
        $this->options['card']['cvv'] = 123;

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $requestData = $response->getRedirectData();
        $this->assertEquals($dataId, $requestData['hosteddataid']);
    }

    /**
     * testPurchaseErrorWhenMissingHostedDataIdAndWithoutCardNumber.
     *
     * Simulates neither hosteddataid or card data being passed, should be caught in app.
     *
     *
     */
    public function testPurchaseErrorWhenMissingHostedDataIdAndWithoutCardNumber()
    {
        $this->expectException(InvalidCreditCardException::class);
        unset($this->options['card']);
        $this->options['card']['cvv'] = 123;

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
    }
}
