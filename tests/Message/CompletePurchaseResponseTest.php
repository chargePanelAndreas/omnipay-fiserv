<?php

namespace Omnipay\FiservArg\Message;

use Omnipay\Tests\TestCase;

class CompletePurchaseResponseTest extends TestCase
{
    public function testCompletePurchaseSuccess()
    {
        $response = new CompletePurchaseResponse(
            $this->getMockRequest(),
            array(
                'chargetotal' => '110.00',
                'response_hash' => '0nM0t9K6QV3Z+zEaQoVHZuNFPD+FZ/fD0kcdML4Tw3o=',
                'status' => 'APROBADO',
                'oid' => 'abc123456',
                'txndatetime' => '2013:09:27-16:06:26',
                'approval_code' => 'Y:136432:0013649958:PPXM:0015'
            )
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('abc123456', $response->getTransactionId());
        $this->assertSame('APROBADO', $response->getMessage());
    }

    public function testCompletePurchaseFailure()
    {
        $response = new CompletePurchaseResponse(
            $this->getMockRequest(),
            array(
                'chargetotal' => '110.00',
                'response_hash' => 'P+j1yR9obVqpTqzWBgU3c50u800rRXTedHs6VSlMR5Y=',
                'status' => 'DECLINED',
                'oid' => 'abc1234',
                'txndatetime' => '2013:09:27-16:00:19',
                'approval_code' => 'N:05:DECLINED'
            )
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('abc1234', $response->getTransactionId());
        $this->assertSame('DECLINED', $response->getMessage());
    }
}
