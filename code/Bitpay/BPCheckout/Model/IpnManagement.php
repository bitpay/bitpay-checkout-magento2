<?php

namespace Bitpay\BPCheckout\Model;

use Magento\Sales\Model\Order;

class IpnManagement implements \Bitpay\BPCheckout\Api\IpnManagementInterface
{

    protected $_invoiceService;
    protected $_transaction;
    public $orderRepository;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $this->_moduleList = $moduleList;

        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }
    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue(
            $_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;

    }

    public function getOrder($_order_id)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->loadByIncrementId($_order_id);

        return $order;

    }
    public function postIpn()
    {

        #database
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
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
       # $sql = "SELECT * FROM $table_name WHERE order_id = '$orderid' AND transaction_id = '$order_invoice' ";
       $sql = $connection->select()
                                        ->from($table_name)
                                        ->where('order_id = ?', $orderid)
                                        ->where('transaction_id = ?', $order_invoice);

        $row = $connection->fetchAll($sql);
        
        #$row = $result->fetch();
        if ($row):

            $level = 1;

            include dirname(__DIR__, $level) . "/BitPayLib/BPC_Client.php";
            include dirname(__DIR__, $level) . "/BitPayLib/BPC_Configuration.php";
            include dirname(__DIR__, $level) . "/BitPayLib/BPC_Invoice.php";
            include dirname(__DIR__, $level) . "/BitPayLib/BPC_Item.php";

            #verify the ipn
            $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
            $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
            if ($env == 'prod'):
                $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
            endif;
            $bitpay_ipn_mapping = $this->getStoreConfig('payment/bpcheckout/bitpay_ipn_mapping');

            $config = (new \Bitpay\BPCheckout\BitPayLib\BPC_Configuration($bitpay_token, $env));
            $params = (new \stdClass());

            $params->invoiceID = $order_invoice;
            $params->extension_version = $this->getExtensionVersion();
            $item = (new \Bitpay\BPCheckout\BitPayLib\BPC_Item($config, $params));
            $invoice = (new \Bitpay\BPCheckout\BitPayLib\BPC_Invoice($item));
          
            $orderStatus = json_decode($invoice->BPC_checkInvoiceStatus($order_invoice));
           
            $invoice_status = $orderStatus->data->status;
            
            $update_sql = "UPDATE $table_name SET transaction_status = '$invoice_status' WHERE order_id = '$orderid' AND transaction_id = '$order_invoice'";
          
            $connection->query($sql);
            $update_result = $connection->query($update_sql);

            $order = $this->getOrder($orderid);
            #now update the order
            switch ($event['name']) {

                case 'invoice_completed':
                    if ($invoice_status == 'complete'):

                        $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> status has changed to Completed.');
                        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                        $order->save();

                        $this->createMGInvoice($order);

                        return true;
                    endif;
                    break;

                case 'invoice_confirmed':
                    #pending or processing from plugin settings
                    if ($invoice_status == 'confirmed'):
                        $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> processing has been completed.');
                        if ($bitpay_ipn_mapping != 'processing'):
                            #$order->setState(Order::STATE_NEW)->setStatus(Order::STATE_NEW);
                            $order->setState('new', true);
                            $order->setStatus('pending', true);
                        else:
                            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                            $this->createMGInvoice($order);
                        endif;

                        $order->save();
                        return true;
                    endif;
                    break;

                case 'invoice_paidInFull':
                    #STATE_PENDING
                    if ($invoice_status == 'paid'):

                        $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> is processing.');
                        $order->setState('new', true);
                        $order->setStatus('pending', true);
                        $order->save();
                        return true;
                    endif;
                    break;

                case 'invoice_failedToConfirm':
                    if ($invoice_status == 'invalid'):
                        $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.');
                        $order->save();
                        return true;
                    endif;
                    break;

                case 'invoice_expired':
                    if ($invoice_status == 'expired'):
                        $order->delete();

                        return true;
                    endif;
                    break;

                case 'invoice_refundComplete':
                    #load the order to update

                    $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has been refunded.');
                    $order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);

                    $order->save();

                    return true;
                    break;
            }

        endif;
    }
    public function createMGInvoice($order)
    {
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->save();
        $transactionSave = $this->_transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );
        $transactionSave->save();
    }
    public function getExtensionVersion()
    {
        return 'Bitpay_BPCheckout_Magento2_3.1.1911.0';
    }
}
