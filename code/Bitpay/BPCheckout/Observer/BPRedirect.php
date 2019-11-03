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

        include dirname(__DIR__, $level) . "/BitPayLib/BPC_Client.php";
        include dirname(__DIR__, $level) . "/BitPayLib/BPC_Configuration.php";
        include dirname(__DIR__, $level) . "/BitPayLib/BPC_Invoice.php";
        include dirname(__DIR__, $level) . "/BitPayLib/BPC_Item.php";

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

            $config = (new \Bitpay\BPCheckout\BitPayLib\BPC_Configuration($bitpay_token, $env));

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

            #cartfix for modal
            $params->cartFix = $this->getBaseUrl() . 'cartfix/cartfix?order_id=' . $order_id;
            $item = (new \Bitpay\BPCheckout\BitPayLib\BPC_Item($config, $params));
            $invoice = (new \Bitpay\BPCheckout\BitPayLib\BPC_Invoice($item));

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
                    $modal_obj->cartFix = $params->cartFix;
                    $modal_obj->invoiceID = $invoiceID;
                    setcookie("env", $env, time() + (86400 * 30), "/");
                    setcookie("invoicedata", json_encode($modal_obj), time() + (86400 * 30), "/");
                    setcookie("modal", 1, time() + (86400 * 30), "/");

                    break;
                case false:
                default:

                    $this->_redirect->redirect($this->_response, $invoice->BPC_getInvoiceURL());
                    break;
            }
        }
    } //end execute function
    public function getExtensionVersion()
    {
        return 'Bitpay_BPCheckout_Magento2_3.1.1911.0';

    }

}
