<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;

class Invoice
{
    private $invoiceService;
    private $logger;
    private $transaction;
    private $config;
    private $checkoutSession;
    private $orderSender;

    public const COMPLETED = 'invoice_completed';
    public const CONFIRMED = 'invoice_confirmed';
    public const PAID_IN_FULL = 'invoice_paidInFull';
    public const FAILED_TO_CONFIRM = 'invoice_failedToConfirm';
    public const EXPIRED = 'invoice_expired';
    public const DECLINED = 'invoice_declined';
    public const REFUND_COMPLETE = 'invoice_refundComplete';

    /**
     * @param InvoiceService $invoiceService
     * @param Logger $logger
     * @param Transaction $transaction
     * @param Config $config
     * @param Session $checkoutSession
     * @param OrderSender $orderSender
     */
    public function __construct(
        InvoiceService $invoiceService,
        Logger         $logger,
        Transaction    $transaction,
        Config         $config,
        Session        $checkoutSession,
        OrderSender    $orderSender
    ) {
        $this->invoiceService = $invoiceService;
        $this->logger = $logger;
        $this->transaction = $transaction;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
    }

    public function complete(Order $order, BPCItem $item): void
    {
        $params = $item->getItemParams();
        $order->addStatusHistoryComment(
            sprintf(
                "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a>
 status has changed to Completed.",
                $item->getInvoiceEndpoint(),
                $params['invoiceID'],
                $params['invoiceID']
            )
        );
        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
        $order->save();
        $this->createMGInvoice($order);
    }

    public function confirmed(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $params = $item->getItemParams();
        if ($invoiceStatus !== 'confirmed') {
            return;
        }
        $order->addStatusHistoryComment(
            sprintf(
                "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a>
 processing has been completed.",
                $item->getInvoiceEndpoint(),
                $params['invoiceID'],
                $params['invoiceID']
            )
        );

        if ($this->config->getBitpayIpnMapping() != 'processing') {
            $order->setState(Order::STATE_NEW, true)->setStatus(IpnManagement::ORDER_STATUS_PENDING, true);
        } else {
            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
            $this->createMGInvoice($order);
        }
        $order->setCanSendNewEmailFlag(true);
        $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
        $this->orderSender->send($order, true);
        $order->save();
    }

    public function paidInFull(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $params = $item->getItemParams();
        if ($invoiceStatus !== 'paid') {
            return;
        }

        $order->addStatusHistoryComment(
            sprintf(
                "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a>
 is processing.",
                $item->getInvoiceEndpoint(),
                $params['invoiceID'],
                $params['invoiceID']
            )
        );
        $order->setState(Order::STATE_NEW, true);
        $order->setStatus(IpnManagement::ORDER_STATUS_PENDING, true);
        $order->save();
    }

    public function failedToConfirm(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $params = $item->getItemParams();
        if ($invoiceStatus !== 'invalid') {
            return;
        }

        $order->addStatusHistoryComment(
            sprintf(
                "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a>
 has become invalid because of network congestion.  Order will automatically update when the status changes.",
                $item->getInvoiceEndpoint(),
                $params['invoiceID'],
                $params['invoiceID']
            )
        );
        $order->save();
    }

    public function declined(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $params = $item->getItemParams();
        if ($invoiceStatus == 'expired' || $invoiceStatus == 'declined') {
            $order->addStatusHistoryComment(
                sprintf(
                    "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a>
 has been declined / expired.",
                    $item->getInvoiceEndpoint(),
                    $params['invoiceID'],
                    $params['invoiceID']
                )
            );
            if ($this->config->getBitpayCancelMapping() == "cancel") {
                $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
            }
            $order->save();
        }
    }

    public function refundComplete(Order $order, BPCItem $item): void
    {
        $params = $item->getItemParams();
        #load the order to update
        $order->addStatusHistoryComment(
            sprintf(
                "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a>
 has been refunded.",
                $item->getInvoiceEndpoint(),
                $params['invoiceID'],
                $params['invoiceID']
            )
        );
        if ($this->config->getBitpayRefundMapping() == "closed") {
            $order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);
        }
        $order->save();
    }

    /**
     * @return bool|string
     */
    public function BPCCreateInvoice(BPCItem $item)
    {
        $post_fields = json_encode($item->getItemParams()->getData());
        $pluginInfo = $item->getItemParams()['extension_version'];
        $request_headers = [];
        $request_headers[] = 'X-BitPay-Plugin-Info: ' . $pluginInfo;
        $request_headers[] = 'Content-Type: application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf("https://%s/invoices", $item->getInvoiceEndpoint()));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        
        curl_close($ch);

        return ($result);
    }

    public function getBPCCheckInvoiceStatus(BPCItem $item)
    {
        $post_fields = $item->getItemParams();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf(
            "https://%s/invoices/%s?token=%s",
            $item->getInvoiceEndpoint(),
            $post_fields['invoiceID'],
            $item->getToken()
        ));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result);

        return $result->data->status;
    }

    private function createMGInvoice($order): void
    {
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
