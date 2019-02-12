<?php

namespace BitpayCheckout\BPCheckout\Model;

use Magento\Sales\Model\Order;

class IpnManagement implements \BitpayCheckout\BPCheckout\Api\IpnManagementInterface
{

    /**
     * {@inheritdoc}
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->_moduleList = $moduleList;

        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;

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
        $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($_order_id);

        return $order;

    }
    public function postIpn($param)
    {

        #database
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $table_name = $resource->getTableName('bitpay_transactions');
        #json ipn
        $data = json_decode(file_get_contents("php://input"), true);
        $orderid = $data['orderId'];
        $order_status = $data['status'];
        $order_invoice = $data['id'];

        #is it in the lookup table
        $sql = "SELECT * FROM $table_name WHERE order_id = '$orderid' AND transaction_id = '$order_invoice' ";

        $result = $connection->query($sql);
        $row = $result->fetch();
        if ($row):
            $path = $_SERVER['DOCUMENT_ROOT'] . '/app/code/BitpayCheckout/BPCheckout/';

            #include our custom BP2 classes
            require_once $path . 'classes/Config.php';
            require_once $path . 'classes/Client.php';
            require_once $path . 'classes/Item.php';
            require_once $path . 'classes/Invoice.php';

            #verify the ipn
            $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
            $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
            if ($env == 'prod'):
                $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
            endif;
            $config = (new \Configuration($bitpay_token, $env));
            $params = (new \stdClass());

            $params->invoiceID = $order_invoice;
            $params->extension_version = $this->getExtensionVersion();

            $item = (new \Item($config, $params));
            $invoice = (new \Invoice($item));

            $orderStatus = json_decode($invoice->checkInvoiceStatus($order_invoice));
            $invoice_status = $orderStatus->data->status;
            $update_sql = "UPDATE $table_name SET transaction_status = '$invoice_status' WHERE order_id = '$orderid' AND transaction_id = '$order_invoice'";
            $update_result = $connection->query($update_sql);

            $order = $this->getOrder($orderid);
            #now update the order
            switch ($invoice_status) {
                case 'complete':

                    $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> processing has been completed.',
                        Order::STATE_COMPLETE);
                    $order->save();
                    return true;
                    break;

                case 'confirmed':

                    $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> is now processing.',
                        Order::STATE_PROCESSING);
                    $order->save();
                    return true;
                    break;

                case 'paid':
                default:

                    $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> is now processing.',
                        Order::STATE_PROCESSING);

                    return true;

                    break;

                case 'invalid':

                    $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.',
                        Order::STATE_PENDING_PAYMENT);
                    $order->save();
                    return true;
                    break;

                case 'expired':

                    $order->addStatusHistoryComment('BitPay Invoice <a href = "http://' . $item->endpoint . '/dashboard/payments/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has expired, order has been canceled.',
                        Order::STATE_CANCELED);
                    $order->save();
                    return true;
                    break;
            }

        endif;

        die();

    }
    public function getExtensionVersion()
    {
        $moduleCode = 'MagePsycho_Easypathhints'; #Edit here with your Namespace_Module
        $moduleInfo = $this->_moduleList->getOne($moduleCode);
        return $moduleInfo['setup_version'];

        return 'BitPay Checkout - ' . $moduleInfo['setup_version'];
    }
}
