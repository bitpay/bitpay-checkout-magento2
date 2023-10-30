<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use BitPaySDK\Exceptions\BitPayException;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\BitpayRefundRepository;
use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Magento\Directory\Model\PriceCurrency;

class BitPayRefundOnline
{
    protected Client $bitpayClient;
    protected PriceCurrency $priceCurrency;
    protected BitpayInvoiceRepository $bitpayInvoiceRepository;
    protected BitpayRefundRepository $bitpayRefundRepository;
    protected Logger $logger;

    public function __construct(
        Client $bitpayClient,
        PriceCurrency $priceCurrency,
        BitpayInvoiceRepository $bitpayInvoiceRepository,
        BitpayRefundRepository $bitpayRefundRepository,
        Logger $logger,
    ) {
        $this->bitpayClient = $bitpayClient;
        $this->priceCurrency = $priceCurrency;
        $this->bitpayInvoiceRepository = $bitpayInvoiceRepository;
        $this->bitpayRefundRepository = $bitpayRefundRepository;
        $this->logger = $logger;
    }

    public function execute(\Magento\Sales\Model\Order\Creditmemo $creditMemo): void
    {
        $order = $creditMemo->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return;
        }

        $orderId = $order->getId();
        $bitPayInvoiceData = $this->bitpayInvoiceRepository->getByOrderId($orderId);
        if (!$bitPayInvoiceData) {
            return;
        }

        $baseOrderRefund = $this->priceCurrency->round(
            $creditMemo->getOrder()->getBaseTotalRefunded() + $creditMemo->getBaseGrandTotal()
        );
        $client = $this->bitpayClient->initialize();
        $invoiceId = $bitPayInvoiceData['invoice_id'];
        $bitPayInvoice = $client->getInvoice($invoiceId);
        $currency = $bitPayInvoice->getCurrency();
        try {
            $refund = $client->createRefund($invoiceId, $baseOrderRefund, $currency);
        } catch (BitPayException $e) {
            $this->handleRefundCreationException($e);
        }
        $this->bitpayRefundRepository->add($orderId, $refund->getId(), $refund->getAmount());

        $amount = $this->priceCurrency->format($refund->getAmount());
        $message = "A refund request of {$amount} was sent for Bitpay Invoice {$refund->getId()}";
        $order->getPayment()->setData('message', $message);
    }

    private function handleRefundCreationException(BitPayException $e): void
    {
        $apiCode = $e->getApiCode();
        $this->logger->error($e->getMessage());

        $message = match ($apiCode) {
            "010207" => __('A Credit Memo cannot be created until Payment is Confirmed.'),
            "010000" => __('Only full refunds can be processed before the Payment is Completed'),
            default => __($e->getMessage()),
        };

        throw new \Magento\Framework\Exception\LocalizedException($message);
    }
}
