<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Observer;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Observer\BPEmail;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\MethodInterface;
use Bitpay\BPCheckout\Logger\Logger;
use PHPUnit\Framework\TestCase;

class BPEmailTest extends TestCase
{
    /**
     * @var BPEmail $bpEmail
     */
    private $bpEmail;

    /**
     * @var Logger|MockObject $logger
     */
    private $logger;

    /**
     * @var Config|MockObject $config
     */
    private $config;

    public function setUp(): void
    {
        $this->logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->bpEmail = new BPEmail($this->logger, $this->config);
    }

    public function testExecute(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $this->prepareExecute($observer);

        $this->bpEmail->execute($observer);
    }

    public function testStopNewOrderEmailErrException(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $excpeption = new \ErrorException();
        $this->prepareExecute($observer, $excpeption);

        $this->bpEmail->execute($observer);
    }

    public function testStopNewOrderEmailException(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $excpeption = new \Exception();
        $this->prepareExecute($observer, $excpeption);

        $this->bpEmail->execute($observer);
    }

    public function testStopNewOrderEmailError(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $excpeption = new \Error();
        $this->prepareExecute($observer, $excpeption);

        $this->bpEmail->execute($observer);
    }

    public function testExecuteErrorException(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $exception = new \ErrorException();
        $this->testExecuteHandleException($observer, $exception);

        $this->bpEmail->execute($observer);
    }

    public function testExecuteException(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $exception = new \Exception();
        $this->testExecuteHandleException($observer, $exception);

        $this->bpEmail->execute($observer);
    }

    public function testExecuteError(): void
    {
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $exception = new \Error();
        $this->testExecuteHandleException($observer, $exception);

        $this->bpEmail->execute($observer);
    }

    /**
     * @param @param \Exception|\Error $exception
     * @return void
     */
    private function testExecuteHandleException(MockObject $observer, $exception): void
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $payment = $this->getMockBuilder(Order\Payment::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder(Event::class)->addMethods(['getOrder'])->disableOriginalConstructor()->getMock();

        $order->expects($this->once())->method('getPayment')->willReturn($payment);
        $payment->expects($this->once())->method('getMethodInstance')->willThrowException($exception);
        $event->expects($this->once())->method('getOrder')->willReturn($order);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
    }

    /**
     * @param MockObject $observer
     * @param \Exception|\Error|null $exception
     * @return void
     */
    private function prepareExecute(MockObject $observer, $exception = null): void
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $method = $this->getMockBuilder(MethodInterface::class)->disableOriginalConstructor()->getMock();
        $payment = $this->getMockBuilder(Order\Payment::class)->disableOriginalConstructor()->getMock();
        $method->expects($this->once())->method('getCode')->willReturn('bpcheckout');
        $order->expects($this->once())->method('getPayment')->willReturn($payment);
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $event = $this->getMockBuilder(Event::class)->addMethods(['getOrder'])->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getOrder')->willReturn($order);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);

        if ($exception) {
            $order->expects($this->once())->method('save')->willThrowException($exception);
        }

    }
}
