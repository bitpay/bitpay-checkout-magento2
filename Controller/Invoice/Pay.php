<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Controller\Invoice;

use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\TransactionRepository;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;

class Pay implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Manager
     */
    protected Manager $messageManager;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var TransactionRepository
     */
    protected TransactionRepository $transactionRepository;

    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @param RequestInterface $request
     * @param Manager $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionRepository $transactionRepository
     * @param Session $checkoutSession
     * @param Client $client
     * @param Config $config
     */
    public function __construct(
        RequestInterface $request,
        Manager $messageManager,
        RedirectFactory $resultRedirectFactory,
        OrderRepositoryInterface $orderRepository,
        TransactionRepository $transactionRepository,
        Session $checkoutSession,
        Client $client,
        Config $config,
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->checkoutSession = $checkoutSession;
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Get checkout customer info
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $orderId = $this->request->getParam('order_id', null);
        $invoiceId = $this->request->getParam('invoice_id', null);
        
        try {
            if (!$orderId || !$invoiceId || $this->config->getBitpayCheckoutSuccess() !== 'standard'
                || $this->config->getBitpayInvoiceCloseHandling() !== 'keep_order') {
                throw new LocalizedException(new Phrase('Invalid request!'));
            }

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);
            $incrementId = trim((string) $order->getIncrementId());
            if (!$this->isLastOrderInSession($incrementId)) {
                throw new LocalizedException(new Phrase('Invalid request!'));
            }

            if (!$order->canInvoice()) {
                throw new LocalizedException(new Phrase('Order already paid!'));
            }

            if (!$this->transactionRepository->findBy($incrementId, (string) $invoiceId)) {
                throw new LocalizedException(new Phrase('Invalid request!'));
            }

            $client = $this->client->initialize();
            $invoice = $client->getInvoice($invoiceId);
            if (trim((string) $invoice->getOrderId()) !== $incrementId) {
                throw new LocalizedException(new Phrase('Invalid request!'));
            }

            $invoiceStatus = $invoice->getStatus();
            if ($invoiceStatus === 'paid' || $invoiceStatus === 'confirmed' || $invoiceStatus === 'complete') {
                throw new LocalizedException(new Phrase('The invoice has already been paid!'));
            } elseif ($invoiceStatus === 'expired') {
                throw new LocalizedException(new Phrase('The invoice has expired!'));
            } elseif ($invoiceStatus !== 'new') {
                throw new LocalizedException(new Phrase('The invoice is invalid or expired!'));
            }

            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId())
                    ->setLastQuoteId($order->getQuoteId())
                    ->setLastOrderId($order->getEntityId());
                
            return $this->resultRedirectFactory->create()->setUrl($invoice->getUrl());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        } catch (\Error $error) {
            $this->messageManager->addErrorMessage('Invalid request!');

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }

    /**
     * Checks that the order is the last order placed in the current checkout session
     *
     * @param string $incrementId
     * @return bool
     */
    private function isLastOrderInSession(string $incrementId): bool
    {
        $lastRealOrderId = (string) $this->checkoutSession->getData('last_real_order_id');

        return $incrementId !== '' && $lastRealOrderId === $incrementId;
    }
}
