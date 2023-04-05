<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use BitPaySDK\Model\Invoice\Buyer;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Bitpay\BPCheckout\Logger\Logger;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Invoice
{
    /** @var InvoiceService $invoiceService */
    protected $invoiceService;

    /** @var Logger $logger */
    protected $logger;

    /** @var Transaction $transaction */
    protected $transaction;

    /** @var \Bitpay\BPCheckout\Model\Config $config */
    protected $config;

    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var OrderSender $orderSender */
    protected $orderSender;

    /** @var OrderRepository $orderRepository */
    protected $orderRepository;

    /**
     * IPN code for invoice completed
     */
    public const COMPLETED = 'invoice_completed';

    /**
     * IPN code for invoice confirmed
     */
    public const CONFIRMED = 'invoice_confirmed';

    /**
     * IPN code for invoice pain in full
     */
    public const PAID_IN_FULL = 'invoice_paidInFull';

    /**
     * IPN code for failed invoice to confirm
     */
    public const FAILED_TO_CONFIRM = 'invoice_failedToConfirm';

    /**
     * IPN code for expired invoice
     */
    public const EXPIRED = 'invoice_expired';

    /**
     * IPN code for declined invoice
     */
    public const DECLINED = 'invoice_declined';

    /**
     * IPN code for invoice refunded
     */
    public const REFUND_COMPLETE = 'invoice_refundComplete';

    /**
     * @param InvoiceService $invoiceService
     * @param Logger $logger
     * @param Transaction $transaction
     * @param Config $config
     * @param Session $checkoutSession
     * @param OrderSender $orderSender
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        InvoiceService $invoiceService,
        Logger         $logger,
        Transaction    $transaction,
        Config         $config,
        Session        $checkoutSession,
        OrderSender    $orderSender,
        OrderRepository $orderRepository
    ) {
        $this->invoiceService = $invoiceService;
        $this->logger = $logger;
        $this->transaction = $transaction;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Handle complete invoice status
     *
     * @param Order $order
     * @param BPCItem $item
     * @return void
     */
    public function complete(Order $order, BPCItem $item): void
    {
        $msg = $this->prepareMessage("status has changed to Completed");
        $params = $item->getItemParams();
        $order->addStatusHistoryComment(
            sprintf($msg, $item->getInvoiceEndpoint(), $params['invoiceID'], $params['invoiceID'])
        );
        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);

        $this->orderRepository->save($order);
        $this->createMGInvoice($order);
    }

    /**
     * Handle confirmed invoice status
     *
     * @param Order $order
     * @param string $invoiceStatus
     * @param BPCItem $item
     * @return void
     */
    public function confirmed(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $msg = $this->prepareMessage("processing has been completed");
        $params = $item->getItemParams();
        if ($invoiceStatus !== 'confirmed') {
            return;
        }
        $order->addStatusHistoryComment(
            sprintf($msg, $item->getInvoiceEndpoint(), $params['invoiceID'], $params['invoiceID'])
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

        $this->orderRepository->save($order);
    }

    /**
     * Handle paidInFull invoice status
     *
     * @param Order $order
     * @param string $invoiceStatus
     * @param BPCItem $item
     * @return void
     */
    public function paidInFull(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $msg = $this->prepareMessage("is processing");
        $params = $item->getItemParams();
        if ($invoiceStatus !== 'paid') {
            return;
        }

        $order->addStatusHistoryComment(
            sprintf($msg, $item->getInvoiceEndpoint(), $params['invoiceID'], $params['invoiceID'])
        );
        $order->setState(Order::STATE_NEW, true);
        $order->setStatus(IpnManagement::ORDER_STATUS_PENDING, true);

        $this->orderRepository->save($order);
    }

    /**
     * Handle failedToConfirm invoice status
     *
     * @param Order $order
     * @param string $invoiceStatus
     * @param BPCItem $item
     * @return void
     */
    public function failedToConfirm(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $msg = $this->prepareMessage("has become invalid because of network congestion."
            . " Order will automatically update when the status changes");
        $params = $item->getItemParams();
        if ($invoiceStatus !== 'invalid') {
            return;
        }

        $order->addStatusHistoryComment(
            sprintf($msg, $item->getInvoiceEndpoint(), $params['invoiceID'], $params['invoiceID'])
        );

        $this->orderRepository->save($order);
    }

    /**
     * Handle declined invoice status
     *
     * @param Order $order
     * @param string $invoiceStatus
     * @param BPCItem $item
     * @return void
     */
    public function declined(Order $order, string $invoiceStatus, BPCItem $item): void
    {
        $msg = $this->prepareMessage("has been declined / expired");
        $params = $item->getItemParams();
        if ($invoiceStatus == 'expired' || $invoiceStatus == 'declined') {
            $order->addStatusHistoryComment(
                sprintf($msg, $item->getInvoiceEndpoint(), $params['invoiceID'], $params['invoiceID'])
            );
            if ($this->config->getBitpayCancelMapping() == "cancel") {
                $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
            }
            $this->orderRepository->save($order);
        }
    }

    /**
     * Handle refundComplete invoice status
     *
     * @param Order $order
     * @param BPCItem $item
     * @return void
     */
    public function refundComplete(Order $order, BPCItem $item): void
    {
        $msg = $this->prepareMessage("has been refunded");
        $params = $item->getItemParams();
        $order->addStatusHistoryComment(
            sprintf($msg, $item->getInvoiceEndpoint(), $params['invoiceID'], $params['invoiceID'])
        );
        if ($this->config->getBitpayRefundMapping() == "closed") {
            $order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);
        }

        $this->orderRepository->save($order);
    }

    /**
     * Create invoice in Bitpay
     *
     * @param \BitPaySDK\Client $client
     * @param \Magento\Framework\DataObject $params
     * @return bool|array
     */
    public function BPCCreateInvoice(
        \BitPaySDK\Client $client,
        \Magento\Framework\DataObject $params
    ): \BitPaySDK\Model\Invoice\Invoice {
        $price = (float)$params->getData('price');
        $currency = $params->getData('currency');
        $invoice = new \BitPaySDK\Model\Invoice\Invoice($price, $currency);
        $buyer = new Buyer();
        $buyer->setName($params->getData('buyer')['name']);
        $buyer->setEmail($params->getData('buyer')['email']);
        $invoice->setBuyer($buyer);
        $invoice->setOrderId($params->getData('orderId'));
        $invoice->setRedirectURL($params->getData('redirectURL'));
        $invoice->setNotificationURL($params->getData('notificationURL'));
        $invoice->setCloseURL($params->getData('closeURL'));
        $invoice->setExtendedNotifications($params->getData('extendedNotifications'));

        return $client->createInvoice($invoice);
    }

    /**
     * Check invoice status
     *
     * @param \BitPaySDK\Client $client
     * @param string $invoiceId
     * @return mixed
     */
    public function getBPCCheckInvoiceStatus(\BitPaySDK\Client $client, string $invoiceId)
    {
        $invoice = $client->getInvoice($invoiceId);

        return $invoice->getStatus();
    }

    /**
     * Prepare message for order history comments section
     *
     * @param string $msg
     * @return string
     */
    protected function prepareMessage(string $msg): string
    {
        return "BitPay Invoice <a href = \"http://%s/dashboard/payments/%s\" target = \"_blank\">%s</a> $msg.";
    }

    /**
     * Create magento invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
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
