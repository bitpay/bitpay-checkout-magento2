<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Api\IpnManagementInterface;
use Bitpay\BPCheckout\Exception\IPNValidationException;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Bitpay\BPCheckout\Model\Ipn\Validator;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;

class IpnManagement implements IpnManagementInterface
{
    /** @var ResponseFactory $responseFactory */
    protected $responseFactory;

    /** @var UrlInterface $url */
    protected $url;

    /** @var Session $checkoutSession */
    protected $checkoutSession;

    /** @var QuoteFactory $quoteFactory */
    protected $quoteFactory;

    /** @var OrderInterface $orderInterface */
    protected $orderInterface;

    /** @var Registry $coreRegistry */
    protected $coreRegistry;

    /** @var Logger $logger */
    protected $logger;

    /** @var Config $config */
    protected $config;

    /** @var Json $serializer */
    protected $serializer;

    /** @var TransactionRepository $transactionRepository */
    protected $transactionRepository;

    /** @var Invoice $invoice */
    protected $invoice;

    /** @var Request $request */
    protected $request;

    /** @var Client $client */
    protected $client;

    /** @var Response $response */
    protected $response;

    public const ORDER_STATUS_PENDING = 'pending';

    /**
     * @param ResponseFactory $responseFactory
     * @param UrlInterface $url
     * @param Registry $registry
     * @param Session $checkoutSession
     * @param OrderInterface $orderInterface
     * @param QuoteFactory $quoteFactory
     * @param Logger $logger
     * @param Config $config
     * @param Json $serializer
     * @param TransactionRepository $transactionRepository
     * @param Invoice $invoice
     * @param Request $request
     * @param Client $client
     * @param Response $response
     */
    public function __construct(
        ResponseFactory $responseFactory,
        UrlInterface $url,
        Registry $registry,
        Session $checkoutSession,
        OrderInterface $orderInterface,
        QuoteFactory $quoteFactory,
        Logger $logger,
        Config $config,
        Json $serializer,
        TransactionRepository $transactionRepository,
        Invoice $invoice,
        Request $request,
        Client $client,
        Response $response
    ) {
        $this->coreRegistry = $registry;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->quoteFactory = $quoteFactory;
        $this->orderInterface = $orderInterface;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->transactionRepository = $transactionRepository;
        $this->invoice = $invoice;
        $this->request = $request;
        $this->client = $client;
        $this->response = $response;
    }

    /**
     * Handle close invoice and redirect to cart
     *
     * @return string|void
     */
    public function postClose()
    {
        $redirectUrl = $this->url->getUrl('checkout/cart', ['_query' => 'reload=1']);
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->responseFactory->create();
        try {
            $orderID = $this->request->getParam('orderID', null);
            $order = $this->orderInterface->loadByIncrementId($orderID);
            $orderData = $order->getData();
            $quoteID = $orderData['quote_id'];
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($quoteID);
            if ($quote->getId()) {
                $quote->setIsActive(1)->setReservedOrderId(null)->save();
                $this->checkoutSession->replaceQuote($quote);
                $this->coreRegistry->register('isSecureArea', 'true');
                $order->delete();
                $this->coreRegistry->unregister('isSecureArea');
                $response->setRedirect($redirectUrl)->sendResponse();

                return;
            }

            $response->setRedirect($redirectUrl)->sendResponse();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $response->setRedirect($redirectUrl)->sendResponse();
        }
    }

    /**
     * Handle Instant Payment Notification
     *
     * @return string|void
     */
    public function postIpn()
    {
        try {
            $allData = $this->serializer->unserialize($this->request->getContent());
            $data = $allData['data'];
            $event = $allData['event'];
            $orderId = $data['orderId'];
            $orderInvoiceId = $data['id'];
            $row = $this->transactionRepository->findBy($orderId, $orderInvoiceId);
            $client = $this->client->initialize();
            $invoice = $client->getInvoice($orderInvoiceId);
            $ipnValidator = new Validator($invoice, $data);
            if (!empty($ipnValidator->getErrors())) {
                throw new IPNValidationException(implode(', ', $ipnValidator->getErrors()));
            }

            if (!$row) {
                return;
            }

            $env = $this->config->getBitpayEnv();
            $bitpayToken = $this->config->getToken();
            $item = new BPCItem(
                $bitpayToken,
                new DataObject(['invoiceID' => $orderInvoiceId, 'extension_version' => Config::EXTENSION_VERSION]),
                $env
            );

            $invoiceStatus = $this->invoice->getBPCCheckInvoiceStatus($client, $orderInvoiceId);
            $updateWhere = ['order_id = ?' => $orderId, 'transaction_id = ?' => $orderInvoiceId];
            $this->transactionRepository->update('transaction_status', $invoiceStatus, $updateWhere);
            $order = $this->orderInterface->loadByIncrementId($orderId);
            switch ($event['name']) {
                case Invoice::COMPLETED:
                    if ($invoiceStatus == 'complete') {
                        $this->invoice->complete($order, $item);
                    }
                    break;

                case Invoice::CONFIRMED:
                    $this->invoice->confirmed($order, $invoiceStatus, $item);
                    break;

                case Invoice::PAID_IN_FULL:
                    #STATE_PENDING
                    $this->invoice->paidInFull($order, $invoiceStatus, $item);
                    break;

                case Invoice::FAILED_TO_CONFIRM:
                    $this->invoice->failedToConfirm($order, $invoiceStatus, $item);
                    break;

                case Invoice::EXPIRED:
                case Invoice::DECLINED:
                    $this->invoice->declined($order, $invoiceStatus, $item);
                    break;

                case Invoice::REFUND_COMPLETE:
                    #load the order to update
                    $this->invoice->refundComplete($order, $item);
                    break;
            }
        } catch (\Exception $e) {
            $this->response->addMessage($e->getMessage(), 500);
            $this->logger->error($e->getMessage());
        }
    }
}
