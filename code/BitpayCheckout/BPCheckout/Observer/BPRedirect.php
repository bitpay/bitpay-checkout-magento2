<?php
namespace BitpayCheckout\BPCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class BPRedirect implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
            $this->_moduleList      = $moduleList;
            $this->_scopeConfig     = $scopeConfig;
            $this->_responseFactory = $responseFactory;
            $this->_url             = $url;

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
        $order         = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($_order_id);
        return $order;

    }

    public function getBaseUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager  = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/app/code/BitpayCheckout/BPCheckout/';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();              // Instance of object manager

        #include our custom BP2 classes
        require_once $path . 'classes/Config.php';
        require_once $path . 'classes/Client.php';
        require_once $path . 'classes/Item.php';
        require_once $path . 'classes/Invoice.php';

        $order_ids = $observer->getEvent()->getOrderIds();
        $order_id  = $order_ids[0];
        $order     = $this->getOrder($order_id);
        #get the environment
        $env          = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
        $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
        if ($env == 'prod'): 
            $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
        endif;

        #get the ux type
        $modal = false;
        if ($this->getStoreConfig('payment/bpcheckout/bitpay_ux') == 'modal'): 
            $modal = true;
        endif;

        $config = (new \Configuration($bitpay_token, $env));

        //create an item, should be passed as an object'
        $params                    = (new \stdClass());
        $params->extension_version = $this->getExtensionVersion();
        $params->price             = $order['base_grand_total'];
        $params->currency          = $order['base_currency_code'];  //set as needed
       

        #buyer email
        $bitpay_capture_email = $this->getStoreConfig('payment/bpcheckout/bitpay_capture_email');
        if($bitpay_capture_email == 1):
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            if ($customerSession->isLoggedIn()) {
                #$params->buyers_email = $customerSession->getCustomer()->getEmail();
                    $buyerInfo = (new \stdClass());
                    $buyerInfo->name = $customerSession->getCustomer()->getName();
                    $buyerInfo->email =$customerSession->getCustomer()->getEmail();
                    $params->buyer = $buyerInfo;
            }
        endif;


        $params->orderId = trim($order_id);

        $params->redirectURL = $this->getBaseUrl() . 'sales/order/view/order_id/' . $order_id . '/';
        #ipn
        $params->notificationURL = $this->getBaseUrl() . 'rest/V1/bitpaycheckout-bpcheckout/ipn';

        #cartfix for modal
        $params->cartFix = $this->getBaseUrl() . 'cartfix/cartfix?order_id='.$order_id;

        $item    = (new \Item($config, $params));
        $invoice = (new \Invoice($item));

        //this creates the invoice with all of the config params from the item
        $invoice->createInvoice();
        $invoiceData = json_decode($invoice->getInvoiceData());

        //now we have to append the invoice transaction id for the callback verification
        $invoiceID = $invoiceData->data->id;

        #insert into the database
        #database
       
        $resource      = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection    = $resource->getConnection();
        $table_name    = $resource->getTableName('bitpay_transactions');

        $sql = "INSERT INTO $table_name (order_id,transaction_id,transaction_status) VALUES ('" . $order_id . "','" . $invoiceID . "','new')";

        $connection->query($sql);
        error_log(print_r($params,true));
        switch ($modal) {
            case true: 
            $modal_obj                  = (new \stdClass());
            $modal_obj->redirectURL     = $params->redirectURL;
            $modal_obj->notificationURL = $params->notificationURL;
            $modal_obj->cartFix         = $params->cartFix;
            $modal_obj->invoiceID       = $invoiceID;
            setcookie("invoicedata",json_encode($modal_obj),time() + (86400 * 30), "/");
            $this->_responseFactory->create()->setRedirect($params->redirectURL.'?modal')->sendResponse();
                return $this;
            break;
            case false  : 
                 default: 
                $this->_responseFactory->create()->setRedirect($invoice->getInvoiceURL())->sendResponse();
                return $this;

            break;
        }

    } //end execute function
    public function getExtensionVersion()
    {
        $moduleCode = 'BitpayCheckout_BPCheckout'; #Edit here with your Namespace_Module
        $moduleInfo = $this->_moduleList->getOne($moduleCode);
        return 'BitPay_Checkout_Magento2_'.$moduleInfo['setup_version'];

    }

}
