<?php

namespace Bitpay\BPCheckout\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\ChangeQuoteControlInterface;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use \Bitpay\BPCheckout\Model\AccessChangeQuoteControl;
use Bitpay\BPCheckout\Logger\Logger;

class IpnManagement   implements \Bitpay\BPCheckout\Api\IpnManagementInterface  
{
    protected $_invoiceService;
    protected $_transaction;

    public $apiToken;
    public $network;

    public $quoteFactory;
    protected $formKey;
    protected $product;
    protected $_responseFactory;
    protected $_url;
    protected $orderSender;

    protected $_checkoutSession;
    protected $_quoteFactory;
    private $_orderInterface;
    protected $coreRegistry;

    private $_resourceConnection;

    private $logger;

    const ORDER_STATUS_PENDING = 'pending';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection,

        Context $context,
        QuoteFactory $quoteFactory,
        ProductFactory $product,
        PageFactory $resultPageFactory,
        Logger $logger
         ) {
        $this->coreRegistry = $registry;

        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;

        $this->_quoteFactory = $quoteFactory;
        $this->_orderInterface = $orderInterface;
        $this->product = $product;
        $this->customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderSender = $orderSender;
        $this->_resourceConnection = $resourceConnection;
        $this->logger = $logger;

    }
    public function BPC_Configuration($token, $network)
    {
        $this->apiToken = $token;
        if ($network == 'test' || $network == null):
            $this->network = $this->BPC_getApiHostDev();
        else:
            $this->network = $this->BPC_getApiHostProd();
        endif;
        $config = (new \stdClass());
        $config->network = $network;
        $config->token = $token;
        return $config;

    }

    public function BPC_getAPIToken()
    {
        #verify the ipn
        $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
        $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
        if ($env == 'prod'):
            $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
        endif;
        $this->apiToken = $bitpay_token;
        return $this->apiToken;
    }

    public function BPC_getNetwork()
    {
        return $this->network;
    }

    public function BPC_getApiHostDev()
    {
        return 'test.bitpay.com';
    }

    public function BPC_getApiHostProd()
    {
        return 'bitpay.com';
    }

    public function BPC_getApiPort()
    {
        return 443;
    }

    public function BPC_getInvoiceURL()
    {
        return $this->network . '/invoices';
    }
    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue(
            $_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;

    }
    public function BPC_Item($config, $item_params)
    {

        $_item = (new \stdClass());
        $_item->token = $config->token;
        $_item->endpoint = $config->network;
        $_item->item_params = $item_params;

        if ($_item->endpoint == 'test') {
            $_item->invoice_endpoint = 'test.bitpay.com';

        } else {
            $_item->invoice_endpoint = 'bitpay.com';
        }
        return $_item;
    }
    public function BPC_getItem()
    {
        $this->invoice_endpoint = $this->endpoint . '/invoices';
        $this->buyer_transaction_endpoint = $this->endpoint . '/invoiceData/setBuyerSelectedTransactionCurrency';
        $this->item_params->token = $this->token;
        return ($this->item_params);
    }

    public function BPC_Invoice($item)
    {
        $this->item = $item;
        return $item;
    }

    public function BPC_checkInvoiceStatus($orderID, $item, $token)
    {
        $post_fields = ($item->item_params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $item->invoice_endpoint . '/invoices/' . $post_fields->invoiceID . '?token=' . $token);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function BPC_createInvoice()
    {

        $post_fields = json_encode($this->item->item_params);

        $pluginInfo = $this->item->item_params->extension_version;
        $request_headers = array();
        $request_headers[] = 'X-BitPay-Plugin-Info: ' . $pluginInfo;
        $request_headers[] = 'Content-Type: application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->item->invoice_endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        $this->invoiceData = $result;

        curl_close($ch);

    }

    public function BPC_getInvoiceData()
    {
        return $this->invoiceData;
    }

    public function BPC_getInvoiceDataURL()
    {
        $data = json_decode($this->invoiceData);
        return $data->data->url;
    }

    public function getOrder($_order_id)
    {
       
        $order = $this->_orderInterface->loadByIncrementId($_order_id);
        return $order;
    }

    public function postClose()
    {
        $_checkoutSession = $this->_checkoutSession;
        $_quoteFactory = $this->_quoteFactory;
        $orderID = $_GET['orderID'];
        $order = $this->getOrder($orderID);
        $orderData = $order->getData();
        $quoteID = $orderData['quote_id'];
        
        $quote = $_quoteFactory->create()->loadByIdWithoutStore($quoteID);
        if ($quote->getId()) {
            $registry =$this->coreRegistry;
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $_checkoutSession->replaceQuote($quote);
            $RedirectUrl = $this->_url->getUrl('checkout/cart');
            $registry->register('isSecureArea', 'true');
            $order->delete();
            $registry->unregister('isSecureArea');
            $this->_responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
            die();
    }

    }
    public function postIpn()
    {
        

        try {
            #database
            $resource = $this->_resourceConnection;
            $connection = $resource->getConnection();
            $table_name = $resource->getTableName('bitpay_transactions');
            #json ipn
            $all_data = json_decode(file_get_contents("php://input"), true);
            $data = $all_data['data'];
            $event = $all_data['event'];

            $orderid = $data['orderId'];
            $order_status = $data['status'];
            $order_invoice = $data['id'];

            #is it in the lookup table
            $sql = $connection->select()
                ->from($table_name)
                ->where('order_id = ?', $orderid)
                ->where('transaction_id = ?', $order_invoice);
                $order = $this->getOrder($orderid);

            $row = $connection->fetchAll($sql);

            if ($row):

                #verify the ipn
                $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
                $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
                if ($env == 'prod'):
                    $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
                endif;

                $bitpay_ipn_mapping = $this->getStoreConfig('payment/bpcheckout/bitpay_ipn_mapping');
                $bitpay_refund_mapping = $this->getStoreConfig('payment/bpcheckout/bitpay_refund_mapping');
                $bitpay_cancel_mapping = $this->getStoreConfig('payment/bpcheckout/bitpay_cancel_mapping');

                $config = $this->BPC_Configuration($bitpay_token, $env);

                $params = (new \stdClass());

                $params->invoiceID = $order_invoice;
                $params->extension_version = $this->getExtensionVersion();

                $item = $this->BPC_Item($config, $params);

                $invoice = $this->BPC_Invoice($item);
                $orderStatus = json_decode($this->BPC_checkInvoiceStatus($order_invoice, $item, $bitpay_token));

                $invoice_status = $orderStatus->data->status;

                $update_data = array('transaction_status' => $invoice_status);
                $update_where = array(
                    'order_id = ?' => $orderid,
                    'transaction_id = ?' => $order_invoice,
                );

                $connection->update($table_name, $update_data, $update_where);
                $order = $this->getOrder($orderid);
                #now update the order
                switch ($event['name']) {

                    case 'invoice_completed':
                        if ($invoice_status == 'complete'):
                            $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->invoice_endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> status has changed to Completed.');
                            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                            $order->save();
                            $this->createMGInvoice($order);
                            return true;
                        endif;
                        break;

                    case 'invoice_confirmed':
                        #pending or processing from plugin settings
                        if ($invoice_status == 'confirmed'):
                            $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->invoice_endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> processing has been completed.');
                            if ($bitpay_ipn_mapping != 'processing'):
                                $order->setState(Order::STATE_NEW, true);
                                $order->setStatus(self::ORDER_STATUS_PENDING, true);
                            else:
                                $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                                $this->createMGInvoice($order);
                            endif;
                            $order->setCanSendNewEmailFlag(true);
                            $this->_checkoutSession->setForceOrderMailSentOnSuccess(true);
                            $this->_orderSender->send($order, true);
                            $order->save();
                            return true;
                        endif;
                        break;

                    case 'invoice_paidInFull':
                        #STATE_PENDING
                        if ($invoice_status == 'paid'):
                            $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->invoice_endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> is processing.');
                            $order->setState(Order::STATE_NEW, true);
                            $order->setStatus(self::ORDER_STATUS_PENDING, true);
                            $order->save();
                            return true;
                        endif;
                        break;

                    case 'invoice_failedToConfirm':
                        if ($invoice_status == 'invalid'):
                            $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->invoice_endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.');
                            $order->save();
                            return true;
                        endif;
                        break;

                    case 'invoice_expired':
                    case 'invoice_declined':
                        if ($invoice_status == 'expired' || $invoice_status == 'declined'):
                            $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->invoice_endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has been declined / expired.');
                            if ($bitpay_cancel_mapping == "cancel"):
                                $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
                            endif;
                            $order->save();
                            return true;
                        endif;
                        break;

                    case 'invoice_refundComplete':
                        #load the order to update
                        $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has been refunded.');
                        if ($bitpay_refund_mapping == "closed"):
                            $order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);
                        endif;
                        $order->save();
                        return true;
                        break;
                }

            endif;

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
    public function createMGInvoice($order)
    {
        try {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
    public function getExtensionVersion()
    {
        return 'Bitpay_BPCheckout_Magento2_6.13.0';
    }
}
