<?php
namespace Bitpay\BPCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;


class BPRedirect implements ObserverInterface
{
    protected $checkoutSession;
    protected $resultRedirect;
    protected $url;
    protected $coreRegistry;
    protected $_redirect;
    protected $_response;
    public $orderRepository;
    protected $_invoiceService;
    protected $_transaction;

    public $apiToken;
    public $network;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $this->coreRegistry = $registry;
        $this->_moduleList = $moduleList;
        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->resultRedirect = $result;
        $this->_actionFlag = $actionFlag;
        $this->_redirect = $redirect;
        $this->_response = $response;
        $this->orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }

    function BPC_Configuration($token,$network){
        $this->apiToken = $token;
        if($network == 'test' || $network == null):
            $this->network = $this->BPC_getApiHostDev();
        else:
            $this->network = $this->BPC_getApiHostProd();
        endif;
        $config = (new \stdClass());
        $config->network = $network;
        $config->token = $token;
        return $config;
        
    }

    function BPC_getAPIToken() {
         #verify the ipn
         $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
         $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
         if ($env == 'prod'):
             $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
         endif;
         $this->apiToken = $bitpay_token;
        return $this->apiToken;
    }
    
    function BPC_getNetwork() {
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
    
    public function BPC_getInvoiceURL(){
        return $this->network.'/invoices';
    }
    
    public function BPC_Item($config,$item_params){
      
        $_item = (new \stdClass());
        $_item->token =$config->token;
        $_item->endpoint =  $config->network;
        $_item->item_params = $item_params;
       
        if($_item->endpoint == 'test'){
            $_item->invoice_endpoint = 'test.bitpay.com';
          
        }else{
            $_item->invoice_endpoint = 'bitpay.com';
        }
        
        
        return $_item;
    }
    function BPC_getItem(){
        $this->invoice_endpoint = $this->endpoint.'/invoices';
        $this->buyer_transaction_endpoint = $this->endpoint.'/invoiceData/setBuyerSelectedTransactionCurrency';
        $this->item_params->token = $this->token;
        return ($this->item_params);
     }

     public function BPC_Invoice($item){
        $this->item = $item;
        return $item;
        
       
         
     }

     public function BPC_checkInvoiceStatus($orderID,$item)
     {
      
          
         $post_fields = ($item->item_params);
           
        
        
 
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, 'https://' . $item->invoice_endpoint . '/invoices/' . $post_fields->invoiceID);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $result = curl_exec($ch);
         curl_close($ch);
         
         return $result;
     }

     public function BPC_createInvoice($item)
    {
        $item->item_params->token = $item->token;
        $post_fields = json_encode($item->item_params);

        $pluginInfo = $item->item_params->extension_version;
        $request_headers = array();
        $request_headers[] = 'X-BitPay-Plugin-Info: ' . $pluginInfo;
        $request_headers[] = 'Content-Type: application/json';
      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $item->invoice_endpoint.'/invoices');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

      

        curl_close($ch);
        return ($result);

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
        $controller = $observer->getControllerAction();
        $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $level = 1;

        $order_ids = $observer->getEvent()->getOrderIds();
        $order_id = $order_ids[0];
        $order = $this->getOrder($order_id);
        $order_id_long = $order->getIncrementId();

        if ($order->getPayment()->getMethodInstance()->getCode() == 'bpcheckout') {
            #set to pending and override magento coding
            $order->setState('new', true);
            $order->setStatus('pending', true);

            $order->save();



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

            $config = $this->BPC_Configuration($bitpay_token,$env);

            //create an item, should be passed as an object'
            $params = (new \stdClass());
            $params->extension_version = $this->getExtensionVersion();
            $params->price = $order['base_grand_total'];
            $params->currency = $order['base_currency_code']; //set as needed

            #buyer email
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');

            $buyerInfo = (new \stdClass());
            $guest_login = true;
            if ($customerSession->isLoggedIn()) {
                $guest_login = false;
                $buyerInfo->name = $customerSession->getCustomer()->getName();
                $buyerInfo->email = $customerSession->getCustomer()->getEmail();

            } else {
                $buyerInfo->name = $order->getBillingAddress()->getFirstName() . ' ' . $order->getBillingAddress()->getLastName();
                $buyerInfo->email = $order->getCustomerEmail();
            }
            $params->buyer = $buyerInfo;

            $params->orderId = trim($order_id_long);

            #ipn

            if ($guest_login) { #user is a guest
            #leave alone
            if ($modal == false):
                    #this will send them back to the order/returns page to lookup
                    $params->redirectURL = $this->getBaseUrl() . 'sales/guest/form';
                    #set some info for guest checkout
                    setcookie('oar_order_id', $order_id_long, time() + (86400 * 30), "/"); // 86400 = 1 day
                    setcookie('oar_billing_lastname', $order->getBillingAddress()->getLastName(), time() + (86400 * 30), "/"); // 86400 = 1 day
                    setcookie('oar_email', $order->getCustomerEmail(), time() + (86400 * 30), "/"); // 86400 = 1 day

                else:
                    $params->redirectURL = $this->getBaseUrl() . 'checkout/onepage/success/';
                endif;
            } else {
                $params->redirectURL = $this->getBaseUrl() . 'sales/order/view/order_id/' . $order_id . '/';
            }

            $params->notificationURL = $this->getBaseUrl() . 'rest/V1/bitpay-bpcheckout/ipn';
            $params->extendedNotifications = true;
            $params->acceptanceWindow = 1200000;

            $item = $this->BPC_Item( $config,$params);
           
            

            //this creates the invoice with all of the config params from the item
            $invoice = $this->BPC_createInvoice($item);
            $invoiceData = json_decode($invoice);
            
         
            

            //now we have to append the invoice transaction id for the callback verification
            $invoiceID = $invoiceData->data->id;

            #insert into the database
            #database

            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $table_name = $resource->getTableName('bitpay_transactions');


            $connection->insertForce(
                $table_name,
                ['order_id' => $order_id_long, 'transaction_id' => $invoiceID,'transaction_status'=>'new']
            );
    
            switch ($modal) {
                case true:
                case 1:
                    $modal_obj = (new \stdClass());
                    $modal_obj->redirectURL = $params->redirectURL;
                    $modal_obj->notificationURL = $params->notificationURL;
                    $modal_obj->invoiceID = $invoiceID;
                    setcookie("env", $env, time() + (86400 * 30), "/");
                    setcookie("invoicedata", json_encode($modal_obj), time() + (86400 * 30), "/");
                    setcookie("modal", 1, time() + (86400 * 30), "/");

                    break;
                case false:
                default:

                    $this->_redirect->redirect($this->_response, $invoiceData->data->url);
                    break;
            }
        }
    } //end execute function
    public function getExtensionVersion()
    {
        return 'Bitpay_BPCheckout_Magento2_3.3.2001';

    }

}
