<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Model\Invoice\Invoice;
use BitPaySDK\Model\Invoice\Refund;
use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\BitpayRefundRepository;
use Bitpay\BPCheckout\Model\BitPayRefundOnline;
use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;
use Magento\Directory\Model\PriceCurrency;
use PHPUnit\Framework\TestCase;

class BitPayRefundOnlineTest extends TestCase
{
    private const ORDER_ID = '123213';
    private const INVOICE_ID = '123132132131';
    private const BASE_ORDER_REFUND = 13.00;
    private const ORDER_BASE_TOTAL_REFUNDED = "0";
    private const CREDIT_MEMO_BASE_GRAND_TOTAL = "13.00";
    private const REFUND_ID = "1112";
    private const REFUND_AMOUNT = 13.00;
    private const INVOICE_CURRENCY = 'USD';

    public function testDoNotBitPayRefundForNonBitPayPaymentMethod(): void
    {
        // given
        $bitPayClient = $this->getBitPayClientMock();
        $priceCurrency = $this->getPriceCurrencyMock();
        $bitPayInvoiceRepository = $this->getBitPayInvoiceRepositoryMock();
        $bitPayRefundRepository = $this->getBitPayRefundRepositoryMock();
        $logger = $this->getLoggerMock();
        $creditMemo = $this->getCreditMemoMock();
        $payment = $this->getPaymentMock();
        $order = $this->getOrderMock();

        $class = new BitPayRefundOnline(
            $bitPayClient,
            $priceCurrency,
            $bitPayInvoiceRepository,
            $bitPayRefundRepository,
            $logger
        );

        $creditMemo->expects(self::once())->method('getOrder')->willReturn($order);
        $order->expects(self::once())->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn('anotherMethod');

        // when
        $class->execute($creditMemo);

        // then
        $bitPayClient->expects(self::never())->method('initialize');
    }

    public function testDoNotBitPayRefundForMissingBitPayInvoiceData(): void
    {
        // given
        $bitPayClient = $this->getBitPayClientMock();
        $priceCurrency = $this->getPriceCurrencyMock();
        $bitPayInvoiceRepository = $this->getBitPayInvoiceRepositoryMock();
        $bitPayRefundRepository = $this->getBitPayRefundRepositoryMock();
        $logger = $this->getLoggerMock();
        $creditMemo = $this->getCreditMemoMock();
        $payment = $this->getPaymentMock();
        $order = $this->getOrderMock();

        $class = new BitPayRefundOnline(
            $bitPayClient,
            $priceCurrency,
            $bitPayInvoiceRepository,
            $bitPayRefundRepository,
            $logger
        );

        $creditMemo->expects(self::once())->method('getOrder')->willReturn($order);
        $order->expects(self::once())->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $bitPayInvoiceRepository->expects(self::once())->method('getByOrderId')->with(self::ORDER_ID)->willReturn(null);

        // when
        $class->execute($creditMemo);

        // then
        $bitPayClient->expects(self::never())->method('initialize');
    }

    public function testDoRefund(): void
    {
        // given
        $bitPayClient = $this->getBitPayClientMock();
        $priceCurrency = $this->getPriceCurrencyMock();
        $bitPayInvoiceRepository = $this->getBitPayInvoiceRepositoryMock();
        $bitPayRefundRepository = $this->getBitPayRefundRepositoryMock();
        $logger = $this->getLoggerMock();
        $creditMemo = $this->getCreditMemoMock();
        $payment = $this->getPaymentMock();
        $order = $this->getOrderMock();
        $bitPaySdkClient = $this->getBitPaySdkClientMock();
        $invoice = $this->getInvoiceMock();
        $refund = $this->getRefundMock();

        $class = new BitPayRefundOnline(
            $bitPayClient,
            $priceCurrency,
            $bitPayInvoiceRepository,
            $bitPayRefundRepository,
            $logger
        );

        // then
        $creditMemo->expects(self::exactly(2))->method('getOrder')->willReturn($order);
        $order->expects(self::exactly(2))->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $bitPayInvoiceRepository->expects(self::once())->method('getByOrderId')->with(self::ORDER_ID)->willReturn([
            'invoice_id' => self::INVOICE_ID
        ]);
        $bitPayClient->expects(self::once())->method('initialize')->willReturn($bitPaySdkClient);
        $bitPaySdkClient->expects(self::once())->method('getInvoice')->with(self::INVOICE_ID)->willReturn($invoice);
        $bitPaySdkClient->expects(self::once())->method('createRefund')
            ->with(self::INVOICE_ID, self::BASE_ORDER_REFUND, self::INVOICE_CURRENCY)->willReturn($refund);
        $bitPayRefundRepository->expects(self::once())->method('add')
            ->with(self::ORDER_ID, self::REFUND_ID, self::REFUND_AMOUNT);
        $payment->expects(self::once())->method('setData')
            ->with('message', "A refund request of 13 was sent for Bitpay Invoice 1112");

        // when
        $class->execute($creditMemo);
    }

    public function testThrowLocalizedExceptionForBitPayRefundExceptionWithSpecificMessage(): void
    {
        // given
        $bitPayClient = $this->getBitPayClientMock();
        $priceCurrency = $this->getPriceCurrencyMock();
        $bitPayInvoiceRepository = $this->getBitPayInvoiceRepositoryMock();
        $bitPayRefundRepository = $this->getBitPayRefundRepositoryMock();
        $logger = $this->getLoggerMock();
        $creditMemo = $this->getCreditMemoMock();
        $payment = $this->getPaymentMock();
        $order = $this->getOrderMock();
        $bitPaySdkClient = $this->getBitPaySdkClientMock();
        $invoice = $this->getInvoiceMock();
        $errorMessage = 'error message from API';
        $exception = new BitPayException($errorMessage, 100, null, '010207');

        $class = new BitPayRefundOnline(
            $bitPayClient,
            $priceCurrency,
            $bitPayInvoiceRepository,
            $bitPayRefundRepository,
            $logger
        );

        // then
        $creditMemo->expects(self::exactly(2))->method('getOrder')->willReturn($order);
        $order->expects(self::exactly(1))->method('getPayment')->willReturn($payment);
        $payment->expects(self::once())->method('getMethod')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $bitPayInvoiceRepository->expects(self::once())->method('getByOrderId')->with(self::ORDER_ID)->willReturn([
            'invoice_id' => self::INVOICE_ID
        ]);
        $bitPayClient->expects(self::once())->method('initialize')->willReturn($bitPaySdkClient);
        $bitPaySdkClient->expects(self::once())->method('getInvoice')->with(self::INVOICE_ID)->willReturn($invoice);

        $bitPaySdkClient->expects(self::once())->method('createRefund')
            ->with(self::INVOICE_ID, self::BASE_ORDER_REFUND, self::INVOICE_CURRENCY)->will(self::throwException($exception));
        $bitPayRefundRepository->expects(self::never())->method('add');
        $payment->expects(self::never())->method('setData');

        $logger->expects(self::once())->method('error')->with($errorMessage);
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('A Credit Memo cannot be created until Payment is Confirmed.');

        // when
        $class->execute($creditMemo);
    }

    private function getBitPayClientMock(): Client|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
    }

    private function getPriceCurrencyMock(): PriceCurrency|\PHPUnit\Framework\MockObject\MockObject
    {
        $priceCurrency = $this->getMockBuilder(PriceCurrency::class)->disableOriginalConstructor()->getMock();
        $priceCurrency->method('round')->with(
            13.00
        )->willReturn(self::BASE_ORDER_REFUND);
        $priceCurrency->method('format')->with(self::REFUND_AMOUNT)->willReturn(self::REFUND_AMOUNT);

        return $priceCurrency;
    }

    private function getBitPayInvoiceRepositoryMock(): BitpayInvoiceRepository|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(BitpayInvoiceRepository::class)->disableOriginalConstructor()->getMock();
    }

    private function getBitPayRefundRepositoryMock(): BitpayRefundRepository|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(BitpayRefundRepository::class)->disableOriginalConstructor()->getMock();
    }

    private function getLoggerMock(): Logger|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
    }

    private function getCreditMemoMock(): \Magento\Sales\Model\Order\Creditmemo|\PHPUnit\Framework\MockObject\MockObject
    {
        $creditMemo = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->disableOriginalConstructor()->getMock();
        $creditMemo->method('getBaseGrandTotal')->willReturn(self::CREDIT_MEMO_BASE_GRAND_TOTAL);

        return $creditMemo;
    }

    private function getOrderMock(): \PHPUnit\Framework\MockObject\MockObject|\Magento\Sales\Model\Order
    {
        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getId')->willReturn(self::ORDER_ID);
        $order->method('getBaseTotalRefunded')->willReturn(self::ORDER_BASE_TOTAL_REFUNDED);

        return $order;
    }

    private function getPaymentMock(): \Magento\Sales\Model\Order\Payment|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return (\BitPaySDK\Client&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getBitPaySdkClientMock(): \PHPUnit\Framework\MockObject\MockObject|\BitPaySDK\Client
    {
        return $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return (Invoice&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getInvoiceMock(): \PHPUnit\Framework\MockObject\MockObject|Invoice
    {
        $invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $invoice->method('getCurrency')->willReturn(self::INVOICE_CURRENCY);

        return $invoice;
    }

    /**
     * @return (Refund&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getRefundMock(): \PHPUnit\Framework\MockObject\MockObject|Refund
    {
        $refund = $this->getMockBuilder(Refund::class)->disableOriginalConstructor()->getMock();
        $refund->method('getId')->willReturn(self::REFUND_ID);
        $refund->method('getAmount')->willReturn(self::REFUND_AMOUNT);

        return $refund;
    }
}
