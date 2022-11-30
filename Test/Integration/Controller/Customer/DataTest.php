<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Controller\Customer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\TestFramework\Helper\Bootstrap;

class DataTest extends AbstractController
{
    public function testExecute(): void
    {
        $customerInfo = [
            'billingAddress' => [
                'firstname' => 'test',
                'lastname' => 'test',
                'street' => 'test'
            ],
            'email' => 'test@example.com',
            'incrementId' => '00000000012'
        ];
        $objectManager = Bootstrap::getObjectManager();
        $checkoutSession = $objectManager->get(Session::class);
        $serializer = new Json();
        $checkoutSession->setData(['customer_info' => $customerInfo]);
        $this->dispatch('bitpay-invoice/customer/data');

        $body = $this->getResponse()->getBody();
        $this->assertEquals($serializer->serialize($customerInfo), $body);
    }
}
