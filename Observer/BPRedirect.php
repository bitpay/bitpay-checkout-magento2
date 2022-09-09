<?php
namespace Bitpay\BPCheckout\Observer;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\TransactionRepository;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\ResponseFactory;

class BPRedirect implements ObserverInterface
{
    protected $checkoutSession;
    protected $redirect;
    protected $response;
    protected $orderInterface;
    protected $transactionRepository;
    protected $config;
    protected $actionFlag;
    protected $responseFactory;
    protected $invoice;
    protected $messageManager;
    protected $registry;
    protected $url;

    public function __construct(
        Session $checkoutSession,
        ActionFlag $actionFlag,
        RedirectInterface $redirect,
        ResponseInterface $response,
        OrderInterface $orderInterface,
        Config $config,
        TransactionRepository $transactionRepository,
        ResponseFactory $responseFactory,
        Invoice $invoice,
        Manager $messageManager,
        Registry $registry,
        UrlInterface $url
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->actionFlag = $actionFlag;
        $this->redirect = $redirect;
        $this->response = $response;
        $this->orderInterface = $orderInterface;
        $this->config = $config;
        $this->transactionRepository = $transactionRepository;
        $this->responseFactory = $responseFactory;
        $this->invoice = $invoice;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->url = $url;
    }

    public function execute(Observer $observer)
    {
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
        $orderId = $this->checkoutSession->getData('last_order_id');
        $order = $this->orderInterface->load($orderId);
        $incrementId = $order->getIncrementId();
        $baseUrl = $this->config->getBaseUrl();
        if ($order->getPayment()->getMethodInstance()->getCode() !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return;
        }

        try {
            #set to pending and override magento coding
            $this->setToPendingAndOverrideMagentoStatus($order);
            #get the environment
            $env = $this->config->getBitpayEnv();
            $bitpayToken = $this->config->getToken();
            $modal = $this->config->getBitpayUx() === 'modal';
            //create an item, should be passed as an object'

            $redirectUrl = $baseUrl .'bitpay-invoice/?order_id='. $incrementId;
            $params = $this->getParams($order, $incrementId, $modal, $redirectUrl, $baseUrl, $bitpayToken);
            $billingAddressData = $order->getBillingAddress()->getData();
            $this->setSessionCustomerData($billingAddressData, $order->getCustomerEmail(), $incrementId);
            $item = new BPCItem($bitpayToken, $params, $env);
            //this creates the invoice with all of the config params from the item
            $invoice = $this->invoice->BPCCreateInvoice($item);
            $invoiceData = json_decode($invoice);
            //now we have to append the invoice transaction id for the callback verification
            $invoiceID = $invoiceData->data->id;
            #insert into the database
            $this->transactionRepository->add($incrementId, $invoiceID, 'new');
        } catch (\Exception $exception) {
            $this->registry->register('isSecureArea', 'true');
            $order->delete();
            $this->registry->unregister('isSecureArea');
            $this->messageManager->addErrorMessage('We are unable to place your Order at this time');
            $this->responseFactory->create()->setRedirect($this->url->getUrl('checkout/cart'))->sendResponse();
            exit;
        }

        switch ($modal) {
            case true:
            case 1:
                #set some info for guest checkout
                $this->setSessionCustomerData($billingAddressData, $order->getCustomerEmail(), $incrementId);
                $RedirectUrl = $baseUrl . 'bitpay-invoice/?invoiceID='.$invoiceID.'&order_id='.$incrementId.'&m=1';
                $this->responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
                break;
            case false:
            default:
                $this->redirect->redirect($this->response, $invoiceData->data->url);
                break;
        }
    } //end execute function

    private function setSessionCustomerData(array $billingAddressData, string $email, string $incrementId): void
    {
        $this->checkoutSession->setCustomerInfo(
            [
                'billingAddress' => $billingAddressData,
                'email' => $email,
                'incrementId' => $incrementId
            ]
        );
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    private function setToPendingAndOverrideMagentoStatus(OrderInterface $order): void
    {
        $order->setState('new', true);
        $order_status = $this->config->getBPCheckoutOrderStatus();
        $order_status = !isset($order_status) ? 'pending' : $order_status;
        $order->setStatus($order_status, true);
        $order->save();
    }

    /**
     * @param OrderInterface $order
     * @param string|null $incrementId
     * @param bool $modal
     * @param string $redirectUrl
     * @param string $baseUrl
     * @param string|null $bitpayToken
     * @return DataObject
     */
    private function getParams(
        OrderInterface $order,
        ?string $incrementId,
        bool $modal,
        string $redirectUrl,
        string $baseUrl,
        ?string $bitpayToken
    ): DataObject {
        $buyerInfo = new DataObject([
            'name' => $order->getBillingAddress()->getFirstName() . ' ' . $order->getBillingAddress()->getLastName(),
            'email' => $order->getCustomerEmail()
        ]);
        return new DataObject([
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => $order['base_grand_total'],
            'currency' => $order['base_currency_code'],
            'buyer' => $buyerInfo->getData(),
            'orderId' => trim($incrementId),
            'redirectURL' => !$modal ? $redirectUrl . "&m=0" : $redirectUrl,
            'notificationURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/close?orderID=' . $incrementId,
            'extendedNotifications' => true,
            'token' => $bitpayToken
        ]);
    }
}
