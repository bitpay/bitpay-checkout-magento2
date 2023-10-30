<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Observer;

use Bitpay\BPCheckout\Model\BitPayRefundOnline;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Observer\BitPayPaymentRefund;
use PHPUnit\Framework\TestCase;

class BitPayPaymentRefundTest extends TestCase
{
    public function testDoNotDoAnyActionForMissingCreditMemo(): void
    {
        // given
        $request = $this->getRequestMock();
        $bitPayRefundOnline = $this->getBitPayRefundOnlineMock();
        $observer = $this->getObserverMock();

        $class = new BitPayPaymentRefund($request, $bitPayRefundOnline);

        $observer->expects(self::once())->method('getData')->with('creditmemo')->willReturn(null);

        // then
        $bitPayRefundOnline->expects(self::never())->method('execute');

        // when
        $class->execute($observer);
    }

    public function testDoNotDoAnyActionForNonBitpayPaymentMethod(): void
    {
        // given
        $request = $this->getRequestMock();
        $bitPayRefundOnline = $this->getBitPayRefundOnlineMock();

        $observer = $this->getObserverMock();
        $creditMemo = $this->getCreditMemoMock();
        $order = $this->getOrderMock();
        $payment = $this->getPaymentMock();

        $class = new BitPayPaymentRefund($request, $bitPayRefundOnline);

        $observer->expects(self::once())->method('getData')->with('creditmemo')->willReturn($creditMemo);
        $creditMemo->expects(self::once())->method('getOrder')->willReturn($order);
        $order->expects(self::once())->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn('anotherMethod');

        // then
        $bitPayRefundOnline->expects(self::never())->method('execute');

        // when
        $class->execute($observer);
    }

    public function testDoNotDoAnyActionForOfflineRefund(): void
    {
        // given
        $request = $this->getRequestMock();
        $bitPayRefundOnline = $this->getBitPayRefundOnlineMock();

        $observer = $this->getObserverMock();
        $creditMemo = $this->getCreditMemoMock();
        $order = $this->getOrderMock();
        $payment = $this->getPaymentMock();

        $class = new BitPayPaymentRefund($request, $bitPayRefundOnline);

        $observer->expects(self::once())->method('getData')->with('creditmemo')->willReturn($creditMemo);
        $creditMemo->expects(self::once())->method('getOrder')->willReturn($order);
        $order->expects(self::once())->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $request->expects(self::once())->method('getPost')->with('creditmemo')->willReturn([
            'do_offline' => '1'
        ]);

        // then
        $bitPayRefundOnline->expects(self::never())->method('execute');

        // when
        $class->execute($observer);
    }

    public function testInvokeBitPayRefundOnline(): void
    {
        // given
        $request = $this->getRequestMock();
        $bitPayRefundOnline = $this->getBitPayRefundOnlineMock();

        $observer = $this->getObserverMock();
        $creditMemo = $this->getCreditMemoMock();
        $order = $this->getOrderMock();
        $payment = $this->getPaymentMock();

        $class = new BitPayPaymentRefund($request, $bitPayRefundOnline);

        $observer->expects(self::once())->method('getData')->with('creditmemo')->willReturn($creditMemo);
        $creditMemo->expects(self::once())->method('getOrder')->willReturn($order);
        $order->expects(self::once())->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $request->expects(self::once())->method('getPost')->with('creditmemo')->willReturn([
            'do_offline' => '0'
        ]);

        // then
        $bitPayRefundOnline->expects(self::once())->method('execute');

        // when
        $class->execute($observer);
    }

    private function getRequestMock(): \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\App\RequestInterface
    {
        $methods = \array_merge(
            \get_class_methods(\Magento\Framework\App\RequestInterface::class),
            ['getPost']
        );

        return $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return (BitPayRefundOnline&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getBitPayRefundOnlineMock(): BitPayRefundOnline|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(BitPayRefundOnline::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @return (\Magento\Framework\Event\Observer&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getObserverMock(): \PHPUnit\Framework\MockObject\MockObject|\Magento\Framework\Event\Observer
    {
        return $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @return (\Magento\Sales\Model\Order\Creditmemo&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getCreditMemoMock(): \PHPUnit\Framework\MockObject\MockObject|\Magento\Sales\Model\Order\Creditmemo
    {
        return $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Magento\Sales\Model\Order
     */
    private function getOrderMock(): \PHPUnit\Framework\MockObject\MockObject|\Magento\Sales\Model\Order
    {
        return $this->getMockBuilder(\Magento\Sales\Model\Order::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getPaymentMock(): \Magento\Sales\Model\Order\Payment|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)->disableOriginalConstructor()->getMock();
    }
}
