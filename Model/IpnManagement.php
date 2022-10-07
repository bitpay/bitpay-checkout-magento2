<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Api\IpnManagementInterface;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;

class IpnManagement implements IpnManagementInterface
{
    protected $responseFactory;
    protected $url;
    protected $checkoutSession;
    protected $quoteFactory;
    protected $orderInterface;
    protected $coreRegistry;
    protected $logger;
    protected $config;
    protected $serializer;
    protected $transactionRepository;
    protected $invoice;
    protected $request;

    const ORDER_STATUS_PENDING = 'pending';

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
        Request $request
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
    }

    public function postClose()
    {
        $redirectUrl = $this->url->getUrl('checkout/cart');
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
            $this->logger->info($exception->getMessage());
            $response->setRedirect($redirectUrl)->sendResponse();
        }
    }

    public function postIpn()
    {
        try {
            $allData = $this->serializer->unserialize($this->request->getContent());
            $data = $allData['data'];
            $event = $allData['event'];
            $orderId = $data['orderId'];
            $orderInvoiceId = $data['id'];
            $row = $this->transactionRepository->findBy($orderId, $orderInvoiceId);
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
            $invoiceStatus = $this->invoice->getBPCCheckInvoiceStatus($item);
            $updateWhere = ['order_id = ?' => $orderId, 'transaction_id = ?' => $orderInvoiceId,];
            $this->transactionRepository->update('transaction_status', $invoiceStatus, $updateWhere);
            $order = $this->orderInterface->loadByIncrementId($orderId);
            switch ($event['name']) {
                case Invoice::COMPLETED:
                    if ($invoiceStatus == 'complete') {
                        $this->invoice->complete($order, $item);
                        return true;
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
            $this->logger->error($e->getMessage());
        }
    }
}
