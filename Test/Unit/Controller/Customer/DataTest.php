<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Controller\Customer;

use Bitpay\BPCheckout\Controller\Customer\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data $customerData
     */
    private $customerData;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $session;

    private $jsonFactory;

    public function setUp(): void
    {
        $this->session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)->disableOriginalConstructor()->getMock();
        $this->customerData = new Data(
            $this->session,
            $this->jsonFactory
        );
    }

    public function testExecute(): void
    {
        $customerData = [
            'billingAddress' => [
                'firstname' => 'test',
                'lastname' => 'test',
                'street' => 'test',
                'city' => 'test',
                'postcode' => '1234',
                'telephone' => 21212121,
                'region_id' => 12
            ]
        ];
        $json = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();
        $this->session->expects($this->once())
            ->method('getData')
            ->with('customer_info')
            ->willReturn($customerData);
        $json->expects($this->once())->method('setData')->with($customerData)->willReturn($json);

        $this->jsonFactory->expects($this->once())->method('create')->willReturn($json);

        $this->assertInstanceOf(Json::class, $this->customerData->execute());
    }

    public function testExecuteNoCustomerData(): void
    {
        $json = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();
        $this->session->expects($this->once())->method('getData')->with('customer_info')->willReturn([]);
        $json->expects($this->once())->method('setData')->with(null)->willReturn($json);

        $this->jsonFactory->expects($this->once())->method('create')->willReturn($json);

        $this->assertInstanceOf(Json::class, $this->customerData->execute());
    }
}
