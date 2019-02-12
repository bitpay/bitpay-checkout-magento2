<?php
namespace BitpayCheckout\BPCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class BPRedirect implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url
    ) {
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

    public function getBaseUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();

    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $path = __DIR__ . '/';

        require_once $path . 'classes/Config.php';
        require_once $path . 'classes/Client.php';
        require_once $path . 'classes/Item.php';
        require_once $path . 'classes/Invoice.php';

        $order_ids = $observer->getEvent()->getOrderIds();
        $order_id = $order_ids[0];
        $order = $this->getOrder($order_id);
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

        $config = (new \Configuration($bitpay_token, $env));

//create an item, should be passed as an object'
        $params = (new \stdClass());
        $params->extension_version = '1.0.0.';
        $params->price = $order['base_grand_total'];
        $params->currency = $order['base_currency_code']; //set as needed

        $bitpay_currency = $this->getStoreConfig('payment/bpcheckout/bitpay_currency');
        switch ($bitpay_currency) {
            default:
            case 1:
                $params->buyerSelectedTransactionCurrency = 1;
                break;
            case 'BTC':
                $params->buyerSelectedTransactionCurrency = 'BTC';
                break;
            case 'BCH':
                $params->buyerSelectedTransactionCurrency = 'BCH';
                break;
        }
        $params->orderId = trim($order_id);

        $params->redirectURL = $this->getBaseUrl() . 'sales/order/view/order_id/' . $order_id . '/';
        #ipn
        #$params->notificationURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true) . 'bitpayipn/index/bitpayipn';

        #$cartFix = Mage::getBaseUrl() . 'cartfix/index/renewcart/orderid/' . $orderId;
        $item = (new \Item($config, $params));

        $invoice = (new \Invoice($item));

        //this creates the invoice with all of the config params from the item
        $invoice->createInvoice();
        $invoiceData = json_decode($invoice->getInvoiceData());

        //now we have to append the invoice transaction id for the callback verification
        $invoiceID = $invoiceData->data->id;
        switch ($modal) {
            case true:
            default:
                break;
            case false:

                $this->_responseFactory->create()->setRedirect($invoice->getInvoiceURL())->sendResponse();
                return $this;

                #return $resultRedirect->setPath($invoice->getInvoiceURL());

                #return;

                break;
        }

    } //end execute function

}
