<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Observer;

use Bitpay\BPCheckout\Observer\BPPaymentMethodAvailable;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BPPaymentMethodAvailableTest extends TestCase
{
    /**
     * @var BPPaymentMethodAvailable $bpPaymentMethodAvailable
     */
    private $bpPaymentMethodAvailable;

    /**
     * @var Config|MockObject $config
     */
    private $config;

    public function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->bpPaymentMethodAvailable = new BPPaymentMethodAvailable($this->config);
    }

    public function testExecute(): void
    {
        $tokenDev = bin2hex(random_bytes(10));
        $tokenProd = bin2hex(random_bytes(10));
        $env = 'prod';
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder(Event::class)->addMethods(['getMethodInstance', 'getResult'])->disableOriginalConstructor()->getMock();
        $method = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();


        $event->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $method->expects($this->once())->method('getCode')->willReturn('bpcheckout');

        $this->config->expects($this->any())->method('getBitpayDevToken')->willReturn($tokenDev);
        $this->config->expects($this->any())->method('getBitpayProdToken')->willReturn($tokenProd);
        $this->config->expects($this->once())->method('getBitpayEnv')->willReturn($env);

        $this->bpPaymentMethodAvailable->execute($observer);
    }

    public function testExecuteNoToken(): void
    {
        $tokenDev = '';
        $env = 'test';
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder(Event::class)->addMethods(['getMethodInstance', 'getResult'])->disableOriginalConstructor()->getMock();
        $method = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();


        $event->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $event->expects($this->once())->method('getResult')->willReturn(new DataObject(['is_available' => true]));
        $observer->expects($this->any())->method('getEvent')->willReturn($event);
        $method->expects($this->once())->method('getCode')->willReturn('bpcheckout');

        $this->config->expects($this->any())->method('getBitpayDevToken')->willReturn($tokenDev);
        $this->config->expects($this->once())->method('getBitpayEnv')->willReturn($env);

        $this->bpPaymentMethodAvailable->execute($observer);
    }
}
