<?php
namespace Bitpay\BPCheckout\Model;

use BitPaySDK\Model\Invoice\Invoice as BitPayInvoice;
use Bitpay\BPCheckout\Helper\ReturnHash;
use Bitpay\BPCheckout\Logger\Logger;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class BPRedirect
{
    protected Session $checkoutSession;
    protected OrderInterface $orderInterface;
    protected TransactionRepository $transactionRepository;
    protected Config $config;
    protected Invoice $invoice;
    protected Manager $messageManager;
    protected Registry $registry;
    protected UrlInterface $url;
    protected Logger $logger;
    protected ResultFactory $resultFactory;
    protected Client $client;
    protected OrderRepository $orderRepository;
    protected BitpayInvoiceRepository $bitpayInvoiceRepository;
    protected ReturnHash $returnHashHelper;
    protected EncryptorInterface $encryptor;

    /**
     * @param Session $checkoutSession
     * @param OrderInterface $orderInterface
     * @param \Bitpay\BPCheckout\Model\Config $config
     * @param \Bitpay\BPCheckout\Model\TransactionRepository $transactionRepository
     * @param \Bitpay\BPCheckout\Model\Invoice $invoice
     * @param Manager $messageManager
     * @param Registry $registry
     * @param UrlInterface $url
     * @param Logger $logger
     * @param ResultFactory $resultFactory
     * @param Client $client
     * @param OrderRepository $orderRepository
     * @param BitpayInvoiceRepository $bitpayInvoiceRepository
     * @param ReturnHash $returnHashHelper
     * @param EncryptorInterface $encryptor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $checkoutSession,
        OrderInterface $orderInterface,
        Config $config,
        TransactionRepository $transactionRepository,
        Invoice $invoice,
        Manager $messageManager,
        Registry $registry,
        UrlInterface $url,
        Logger $logger,
        ResultFactory $resultFactory,
        Client $client,
        OrderRepository $orderRepository,
        BitpayInvoiceRepository $bitpayInvoiceRepository,
        ReturnHash $returnHashHelper,
        EncryptorInterface $encryptor,
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderInterface = $orderInterface;
        $this->config = $config;
        $this->transactionRepository = $transactionRepository;
        $this->invoice = $invoice;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->url = $url;
        $this->logger = $logger;
        $this->resultFactory = $resultFactory;
        $this->client = $client;
        $this->orderRepository = $orderRepository;
        $this->bitpayInvoiceRepository = $bitpayInvoiceRepository;
        $this->returnHashHelper = $returnHashHelper;
        $this->encryptor = $encryptor;
    }

    /**
     * Create bitpay invoice after order creation during redirect to success page
     *
     * @param ResultInterface $defaultResult
     * @param string|null $returnId
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException|\Exception
     */
    public function execute(ResultInterface $defaultResult, ?string $returnId = null): ResultInterface
    {
        $orderId = $this->checkoutSession->getData('last_order_id');
        if (!$orderId) {
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
                ->setUrl($this->url->getUrl('checkout/cart'));
        }

        $order = $this->orderInterface->load($orderId);
        $incrementId = $order->getIncrementId();
        if (!$incrementId) {
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
                ->setUrl($this->url->getUrl('checkout/cart'));
        }
        if ($order->getPayment()->getMethodInstance()->getCode() !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return $defaultResult;
        }

        $isStandardCheckoutSuccess = $this->config->getBitpayCheckoutSuccess() === 'standard';

        if ($isStandardCheckoutSuccess && $returnId) {
            $returnHash = $this->returnHashHelper->generate($order);
            if (!$this->returnHashHelper->isValid($returnId, $order)) {
                $this->checkoutSession->clearHelperData();

                return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
                    ->setUrl($this->url->getUrl('checkout/cart'));
            }

            return $defaultResult;
        }

        try {
            $returnHash = $this->returnHashHelper->generate($order);
            $baseUrl = $this->config->getBaseUrl();
            $order = $this->setToPendingAndOverrideMagentoStatus($order);
            $redirectUrl = $this->url->getUrl('bitpay-invoice', ['_query' => ['order_id' => $incrementId]]);
            if ($isStandardCheckoutSuccess) {
                $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $redirectUrl = $this->url->getUrl('checkout/onepage/success', [
                    '_query' => ['return_id' => $returnHash]
                ]);
            }
            $params = $this->getParams($order, $incrementId, $redirectUrl, $baseUrl);
            $billingAddressData = $order->getBillingAddress()->getData();
            $this->setSessionCustomerData($billingAddressData, $order->getCustomerEmail(), $incrementId);
            $client = $this->client->initialize();
            /**
             * @var BitPayInvoice
             */
            $invoice = $this->invoice->BPCCreateInvoice($client, $params);
            $invoiceID = $invoice->getId();
            $order = $this->orderRepository->save($order);
            $this->bitpayInvoiceRepository->add(
                $order->getId(),
                $invoiceID,
                $invoice->getExpirationTime(),
                $invoice->getAcceptanceWindow(),
                $this->encryptor->encrypt($this->config->getToken())
            );
            $this->transactionRepository->add($incrementId, $invoiceID, 'new');
        
            return $this->resultFactory->create(
                \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
            )
            ->setUrl($invoice->getUrl());
        } catch (\Exception $exception) {
            return $this->deleteOrderAndRedirectToCart($exception, $order);
        } catch (\Error $error) {
            return $this->deleteOrderAndRedirectToCart($error, $order);
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
     * @return OrderInterface
     * @throws \Exception
     */
    private function setToPendingAndOverrideMagentoStatus(OrderInterface $order): OrderInterface
    {
        $order->setState('new');
        $order_status = $this->config->getBPCheckoutOrderStatus();
        $order_status = !isset($order_status) ? 'pending' : $order_status;
        $order->setStatus($order_status);

        return $order;
    }

    /**
     * Prepare params for invoice creation
     *
     * @param OrderInterface $order
     * @param string|null $incrementId
     * @param string $redirectUrl
     * @param string $baseUrl
     * @return DataObject
     */
    private function getParams(
        OrderInterface $order,
        ?string $incrementId,
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
            'redirectURL' => $redirectUrl,
            'notificationURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/close?orderID=' . $incrementId,
            'extendedNotifications' => true
        ]);
    }

    /**
     * Delete order and redirect to cart when error
     *
     * @param \Exception|\Error $exception
     * @param OrderInterface $order
     * @return ResultInterface
     * @throws \Exception
     */
    private function deleteOrderAndRedirectToCart($exception, OrderInterface $order): ResultInterface
    {
        $this->checkoutSession->clearHelperData();
        $this->logger->error($exception->getMessage());
        $this->registry->register('isSecureArea', 'true');
        $order->delete();
        $this->registry->unregister('isSecureArea');
        $this->messageManager->addErrorMessage('We are unable to place your Order at this time');

        /**
         * @var Redirect
         * */
        $redirect = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        );

        return $redirect->setUrl($this->url->getUrl('checkout/cart'));
    }
}
