<?php
namespace BitpayCheckout\BPCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class BPRedirect implements ObserverInterface
{
    protected $checkoutSession;
    protected $resultRedirect;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\ResultFactory $result
    ) {
        $this->_moduleList = $moduleList;
        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->resultRedirect = $result;

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

    public function getBaseUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager

        
        $path = $_SERVER['DOCUMENT_ROOT'] . '/app/code/BitpayCheckout/BPCheckout/';
        include $path . 'BitPayLib/BPC_Client.php';
        include $path . 'BitPayLib/BPC_Configuration.php';
        include $path . 'BitPayLib/BPC_Invoice.php';
        include $path . 'BitPayLib/BPC_Item.php';

          

        $order_ids = $observer->getEvent()->getOrderIds();
        $order_id = $order_ids[0];
        $order = $this->getOrder($order_id);
        $order_id_long = $order->getIncrementId();
        if ($order->getPayment()->getMethodInstance()->getCode() == 'bpcheckout') {

            #get the environment
            $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
            $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
            if ($env == 'prod'):
                $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
            endif;

            #get the ux type
            $modal = false;
            if ($this->getStoreConfig('payment/bpcheckout/bitpay_ux') == 'modal'):
                $modal = true;
            endif;
            $config = (new \BPC_Configuration($bitpay_token, $env));

            //create an item, should be passed as an object'
            $params = (new \stdClass());
            $params->extension_version = $this->getExtensionVersion();
            $params->price = $order['base_grand_total'];
            $params->currency = $order['base_currency_code']; //set as needed

            #buyer email
            $bitpay_capture_email = $this->getStoreConfig('payment/bpcheckout/bitpay_capture_email');
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            if ($customerSession->isLoggedIn()) {
                if ($bitpay_capture_email == 1):
                    $buyerInfo = (new \stdClass());
                    $buyerInfo->name = $customerSession->getCustomer()->getName();
                    $buyerInfo->email = $customerSession->getCustomer()->getEmail();
                    $params->buyer = $buyerInfo;
                endif;
            }

            $params->orderId = trim($order_id_long);

            #ipn

            if (!$customerSession->isLoggedIn()) {
                #leave alone
                $params->redirectURL = $this->getBaseUrl() . 'checkout/onepage/success/';
                if ($modal == false):
                    $params->redirectURL .= '?bp=1';
                endif;
            }
            if ($customerSession->isLoggedIn()) {
                $params->redirectURL = $this->getBaseUrl() . 'sales/order/view/order_id/' . $order_id . '/';
                if ($modal == false):
                endif;
            }
            $params->notificationURL = $this->getBaseUrl() . 'rest/V1/bitpaycheckout-bpcheckout/ipn';
            $params->extendedNotifications = true;

            #cartfix for modal
            $params->cartFix = $this->getBaseUrl() . 'cartfix/cartfix?order_id=' . $order_id;
            #error_log(print_r($params,true));
            $item = (new \BPC_Item($config, $params));
            $invoice = (new \BPC_Invoice($item));

            //this creates the invoice with all of the config params from the item
            $invoice->BPC_createInvoice();
            $invoiceData = json_decode($invoice->BPC_getInvoiceData());

            //now we have to append the invoice transaction id for the callback verification
            $invoiceID = $invoiceData->data->id;

            #insert into the database
            #database

            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $table_name = $resource->getTableName('bitpay_transactions');

            $sql = "INSERT INTO $table_name (order_id,transaction_id,transaction_status) VALUES ('" . $order_id_long . "','" . $invoiceID . "','new')";

            $connection->query($sql);
            switch ($modal) {
                case true:
                case 1:
                    $modal_obj = (new \stdClass());
                    $modal_obj->redirectURL = $params->redirectURL;
                    $modal_obj->notificationURL = $params->notificationURL;
                    $modal_obj->cartFix = $params->cartFix;
                    $modal_obj->invoiceID = $invoiceID;
                    setcookie("env", $env, time() + (86400 * 30), "/");
                    setcookie("invoicedata", json_encode($modal_obj), time() + (86400 * 30), "/");
                    setcookie("modal", 1, time() + (86400 * 30), "/");

                    break;
                case false:
                default:
                    $this->_responseFactory->create()->setRedirect($invoice->BPC_getInvoiceURL())->sendResponse();
                    return $this;

                    break;
            }
        }
    } //end execute function
    public function getExtensionVersion()
    {
        $moduleCode = 'BitpayCheckout_BPCheckout'; #Edit here with your Namespace_Module
        $moduleInfo = $this->_moduleList->getOne($moduleCode);
        return 'BitPay_Checkout_Magento2_' . $moduleInfo['setup_version'];

    }

}
