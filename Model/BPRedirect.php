<?php
namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Logger\Logger;
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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class BPRedirect
{
    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var RedirectInterface $redirect */
    protected $redirect;

    /** @var ResponseInterface $response */
    protected $response;

    /** @var OrderInterface $orderInterface */
    protected $orderInterface;

    /** @var \Bitpay\BPCheckout\Model\TransactionRepository $transactionRepository */
    protected $transactionRepository;

    /** @var \Bitpay\BPCheckout\Model\Config $config */
    protected $config;

    /** @var ResponseFactory $responseFactory */
    protected $responseFactory;

    /** @var \Bitpay\BPCheckout\Model\Invoice $invoice */
    protected $invoice;

    /** @var Manager $messageManager */
    protected $messageManager;

    /** @var Registry $registry */
    protected $registry;

    /** @var UrlInterface $url */
    protected $url;

    /** @var Logger $logger */
    protected $logger;

    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var Client $client */
    protected $client;

    /** @var OrderRepository $orderRepository */
    protected $orderRepository;

    /** @var BitpayInvoiceRepository $bitpayInvoiceRepository */
    protected $bitpayInvoiceRepository;

    /**
     * @param Session $checkoutSession
     * @param RedirectInterface $redirect
     * @param ResponseInterface $response
     * @param OrderInterface $orderInterface
     * @param \Bitpay\BPCheckout\Model\Config $config
     * @param \Bitpay\BPCheckout\Model\TransactionRepository $transactionRepository
     * @param ResponseFactory $responseFactory
     * @param \Bitpay\BPCheckout\Model\Invoice $invoice
     * @param Manager $messageManager
     * @param Registry $registry
     * @param UrlInterface $url
     * @param Logger $logger
     * @param PageFactory $resultPageFactory
     * @param Client $client
     * @param OrderRepository $orderRepository
     * @param BitpayInvoiceRepository $bitpayInvoiceRepository
     */
    public function __construct(
        Session $checkoutSession,
        RedirectInterface $redirect,
        ResponseInterface $response,
        OrderInterface $orderInterface,
        Config $config,
        TransactionRepository $transactionRepository,
        ResponseFactory $responseFactory,
        Invoice $invoice,
        Manager $messageManager,
        Registry $registry,
        UrlInterface $url,
        Logger $logger,
        PageFactory $resultPageFactory,
        Client $client,
        OrderRepository $orderRepository,
        BitpayInvoiceRepository $bitpayInvoiceRepository
    ) {
        $this->checkoutSession = $checkoutSession;
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
        $this->logger = $logger;
        $this->resultPageFactory = $resultPageFactory;
        $this->client = $client;
        $this->orderRepository = $orderRepository;
        $this->bitpayInvoiceRepository = $bitpayInvoiceRepository;
    }

    /**
     * Create bitpay invoice after order creation during redirect to success page
     *
     * @return Page|void
     * @throws LocalizedException
     * @throws NoSuchEntityException|\Exception
     */
    public function execute()
    {
        $orderId = $this->checkoutSession->getData('last_order_id');
        if (!$orderId) {
            $this->response->setRedirect($this->url->getUrl('checkout/cart'))->sendResponse();
            return;
        }

        $order = $this->orderInterface->load($orderId);
        $incrementId = $order->getIncrementId();
        $baseUrl = $this->config->getBaseUrl();
        if ($order->getPayment()->getMethodInstance()->getCode() !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return $this->resultPageFactory->create();
        }

        try {
            $order = $this->setToPendingAndOverrideMagentoStatus($order);
            $modal = $this->config->getBitpayUx() === 'modal';
            $redirectUrl = $baseUrl .'bitpay-invoice/?order_id='. $incrementId;
            $params = $this->getParams($order, $incrementId, $modal, $redirectUrl, $baseUrl);
            $billingAddressData = $order->getBillingAddress()->getData();
            $this->setSessionCustomerData($billingAddressData, $order->getCustomerEmail(), $incrementId);
            $client = $this->client->initialize();
            $invoice = $this->invoice->BPCCreateInvoice($client, $params);
            $invoiceID = $invoice->getId();
            $order = $this->orderRepository->save($order);
            $this->bitpayInvoiceRepository->add(
                $order->getId(),
                $invoiceID,
                $invoice->getExpirationTime(),
                $invoice->getAcceptanceWindow()
            );
            $this->transactionRepository->add($incrementId, $invoiceID, 'new');
        } catch (\Exception $exception) {
            $this->deleteOrderAndRedirectToCart($exception, $order);

            return;
        } catch (\Error $error) {
            $this->deleteOrderAndRedirectToCart($error, $order);

            return;
        }

        switch ($modal) {
            case true:
            case 1:
                #set some info for guest checkout
                $this->setSessionCustomerData($billingAddressData, $order->getCustomerEmail(), $incrementId);
                $RedirectUrl = $baseUrl . 'bitpay-invoice/?invoiceID=' . $invoiceID . '&order_id='
                    . $incrementId . '&m=1';
                $this->responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
                break;
            case false:
            default:
                $this->redirect->redirect($this->response, $invoice->getUrl());
                break;
        }
    }

    /**
     * Sets customer session data
     *
     * @param array $billingAddressData
     * @param string $email
     * @param string $incrementId
     * @return void
     */
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
     * Sets pending order status
     *
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    private function setToPendingAndOverrideMagentoStatus(OrderInterface $order): Order
    {
        $order->setState('new', true);
        $order_status = $this->config->getBPCheckoutOrderStatus();
        $order_status = !isset($order_status) ? 'pending' : $order_status;
        $order->setStatus($order_status, true);

        return $order;
    }

    /**
     * Prepare params for invoice creation
     *
     * @param OrderInterface $order
     * @param string|null $incrementId
     * @param bool $modal
     * @param string $redirectUrl
     * @param string $baseUrl
     * @return DataObject
     */
    private function getParams(
        OrderInterface $order,
        ?string $incrementId,
        bool $modal,
        string $redirectUrl,
        string $baseUrl
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
            'extendedNotifications' => true
        ]);
    }

    /**
     * Delete order and redirect to cart when error
     *
     * @param \Exception $exception
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    private function deleteOrderAndRedirectToCart($exception, OrderInterface $order): void
    {
        $this->logger->error($exception->getMessage());
        $this->registry->register('isSecureArea', 'true');
        $order->delete();
        $this->registry->unregister('isSecureArea');
        $this->messageManager->addErrorMessage('We are unable to place your Order at this time');
        $this->responseFactory->create()->setRedirect($this->url->getUrl('checkout/cart'))->sendResponse();
    }
}
