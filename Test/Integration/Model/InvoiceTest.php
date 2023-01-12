<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Model;

use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Bitpay\BPCheckout\Model\IpnManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var InvoiceService $invoiceService
     */
    private $invoiceService;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var Transaction $transaction
     */
    private $transaction;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var Session $checkoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderSender $orderSender
     */
    private $orderSender;

    /**
     * @var Invoice $invoice
     */
    private $invoice;

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->invoiceService = $this->objectManager->get(InvoiceService::class);
        $this->logger = $this->objectManager->get(Logger::class);
        $this->transaction = $this->objectManager->get(Transaction::class);
        $this->config = $this->objectManager->get(Config::class);
        $this->checkoutSession = $this->objectManager->get(Session::class);
        $this->orderSender = $this->objectManager->get(OrderSender::class);
        $this->invoice = new Invoice(
            $this->invoiceService,
            $this->logger,
            $this->transaction,
            $this->config,
            $this->checkoutSession,
            $this->orderSender
        );
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     */
    public function testPaidInFull(): void
    {
        $params = new DataObject($this->getParams());
        $item = new BPCItem($this->getParams()['token'], $params, 'test');
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order = $order->loadByIncrementId('100000001');

        $this->invoice->paidInFull($order, 'paid', $item);

        $histories = $order->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $comment = $latestHistoryComment->getComment();
        $endpoint = $item->getInvoiceEndpoint();

        $this->assertEquals('new', $order->getState());
        $this->assertEquals(IpnManagement::ORDER_STATUS_PENDING, $order->getStatus());
        $this->assertEquals(IpnManagement::ORDER_STATUS_PENDING, $order->getStatus());
        $this->assertEquals("BitPay Invoice <a href = \"http://{$endpoint}/dashboard/payments/RCYxvSq4djGwuWgcBDaGbT\" target = \"_blank\">RCYxvSq4djGwuWgcBDaGbT</a>
 is processing.", $comment);
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_refund_mapping closed
     */
    public function testRefundComplete(): void
    {
        $params = new DataObject($this->getParams());
        $item = new BPCItem($this->getParams()['token'], $params, 'test');
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order = $order->loadByIncrementId('100000001');
        $endpoint = $item->getInvoiceEndpoint();

        $this->invoice->refundComplete($order, $item);

        $histories = $order->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $comment = $latestHistoryComment->getComment();

        $this->assertEquals("BitPay Invoice <a href = \"http://{$endpoint}/dashboard/payments/RCYxvSq4djGwuWgcBDaGbT\" target = \"_blank\">RCYxvSq4djGwuWgcBDaGbT</a>
 has been refunded.", $comment);
        $this->assertEquals(Order::STATE_CLOSED, $order->getState());
        $this->assertEquals(Order::STATE_CLOSED, $order->getStatus());
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     */
    public function testFailedToConfirm(): void
    {
        $params = new DataObject($this->getParams());
        $item = new BPCItem($this->getParams()['token'], $params, 'test');
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order = $order->loadByIncrementId('100000001');
        $endpoint = $item->getInvoiceEndpoint();

        $this->invoice->failedToConfirm($order, 'invalid', $item);

        $histories = $order->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $comment = $latestHistoryComment->getComment();

        $this->assertEquals("BitPay Invoice <a href = \"http://{$endpoint}/dashboard/payments/RCYxvSq4djGwuWgcBDaGbT\" target = \"_blank\">RCYxvSq4djGwuWgcBDaGbT</a>
 has become invalid because of network congestion.  Order will automatically update when the status changes.", $comment);
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_ipn_mapping processing
     */
    public function testConfirmed(): void
    {
        $params = new DataObject($this->getParams());
        $item = new BPCItem($this->getParams()['token'], $params, 'test');
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order = $order->loadByIncrementId('100000001');
        $endpoint = $item->getInvoiceEndpoint();

        $this->invoice->confirmed($order, 'confirmed', $item);

        $histories = $order->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $comment = $latestHistoryComment->getComment();

        $this->assertEquals("BitPay Invoice <a href = \"http://{$endpoint}/dashboard/payments/RCYxvSq4djGwuWgcBDaGbT\" target = \"_blank\">RCYxvSq4djGwuWgcBDaGbT</a>
 processing has been completed.", $comment);
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getStatus());
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     */
    public function testComplete(): void
    {
        $params = new DataObject($this->getParams());
        $item = new BPCItem($this->getParams()['token'], $params, 'test');
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order = $order->loadByIncrementId('100000001');
        $endpoint = $item->getInvoiceEndpoint();

        $this->invoice->complete($order, $item);

        $histories = $order->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $comment = $latestHistoryComment->getComment();

        $this->assertEquals("BitPay Invoice <a href = \"http://{$endpoint}/dashboard/payments/RCYxvSq4djGwuWgcBDaGbT\" target = \"_blank\">RCYxvSq4djGwuWgcBDaGbT</a>
 status has changed to Completed.", $comment);
        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getStatus());
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_cancel_mapping cancel
     */
    public function testDeclined(): void
    {
        $params = new DataObject($this->getParams());
        $item = new BPCItem($this->getParams()['token'], $params, 'test');
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $order = $order->loadByIncrementId('100000001');
        $endpoint = $item->getInvoiceEndpoint();

        $this->invoice->declined($order, 'declined', $item);

        $histories = $order->getStatusHistories();
        $latestHistoryComment = array_pop($histories);
        $comment = $latestHistoryComment->getComment();

        $this->assertEquals("BitPay Invoice <a href = \"http://{$endpoint}/dashboard/payments/RCYxvSq4djGwuWgcBDaGbT\" target = \"_blank\">RCYxvSq4djGwuWgcBDaGbT</a>
 has been declined / expired.", $comment);
        $this->assertEquals(Order::STATE_CANCELED, $order->getState());
        $this->assertEquals(Order::STATE_CANCELED, $order->getStatus());
    }

    private function getParams(): array
    {
        $baseUrl = $this->config->getBaseUrl();
        return [
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => 23,
            'currency' => 'USD',
            'buyer' => [],
            'orderId' => '00000123231',
            'redirectURL' => $baseUrl . 'bitpay-invoice/?order_id=00000123231',
            'notificationURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/close?orderID=00000123231',
            'extendedNotifications' => true,
            'token' => 'AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy',
            'invoiceID' => 'RCYxvSq4djGwuWgcBDaGbT'
        ];
    }
}
