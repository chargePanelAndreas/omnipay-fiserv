<?php
/**
 * First Data Connect Purchase Response
 */

namespace Omnipay\FiservArg\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * First Data Connect Purchase Response
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{

    public function __construct(RequestInterface $request, private ?string $location)
    {
        $this->request = $request;
        //$this->data = json_decode($data, true);
    }
    
    public function isSuccessful()
    {
        if ($this->location === null) {
            return false;
        }
        return strpos($this->location, 'validationError') === false;
    }

    public function isRedirect()
    {
        return true;
    }

    public function getRedirectUrl()
    {
        // FIXME: This makes no sense.
        return $this->location;
    }

    public function getRedirectMethod()
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        return $this->data;
    }
}
