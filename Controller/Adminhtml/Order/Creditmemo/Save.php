<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Controller\Adminhtml\Order\Creditmemo;

use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\BitpayRefundRepository;
use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save as CreditmemoSave;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Directory\Model\PriceCurrency;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends CreditmemoSave
{
    protected Client $bitpayClient;
    protected PriceCurrency $priceCurrency;
    protected BitpayInvoiceRepository $bitpayInvoiceRepository;
    protected BitpayRefundRepository $bitpayRefundRepository;
    protected Logger $logger;
    private SalesData $salesData;

    /**
     * @param Context $context
     * @param CreditmemoLoader $creditmemoLoader
     * @param CreditmemoSender $creditmemoSender
     * @param ForwardFactory $resultForwardFactory
     * @param Client $bitpayClient
     * @param PriceCurrency $priceCurrency
     * @param BitpayInvoiceRepository $bitpayInvoiceRepository
     * @param BitpayRefundRepository $bitpayRefundRepository
     * @param Logger $logger
     * @param SalesData|null $salesData
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader,
        CreditmemoSender $creditmemoSender,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        Client $bitpayClient,
        PriceCurrency $priceCurrency,
        BitpayInvoiceRepository $bitpayInvoiceRepository,
        BitpayRefundRepository $bitpayRefundRepository,
        Logger $logger,
        SalesData $salesData = null
    ) {
        parent::__construct(
            $context,
            $creditmemoLoader,
            $creditmemoSender,
            $resultForwardFactory,
            $salesData
        );

        $this->bitpayClient = $bitpayClient;
        $this->priceCurrency = $priceCurrency;
        $this->bitpayInvoiceRepository = $bitpayInvoiceRepository;
        $this->bitpayRefundRepository = $bitpayRefundRepository;
        $this->logger = $logger;
    }

    /**
     * Save creditmemo
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Backend\Model\View\Result\Forward
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPost('creditmemo');

        if (!empty($data['comment_text'])) {
            $this->_getSession()->setCommentText($data['comment_text']);
        }
        try {
            $this->creditmemoLoader->setOrderId($this->getRequest()->getParam('order_id'));
            $this->creditmemoLoader->setCreditmemoId($this->getRequest()->getParam('creditmemo_id'));
            $this->creditmemoLoader->setCreditmemo($this->getRequest()->getParam('creditmemo'));
            $this->creditmemoLoader->setInvoiceId($this->getRequest()->getParam('invoice_id'));
            $creditmemo = $this->creditmemoLoader->load();
            $this->createBitpayRefund($creditmemo, $data);
            if ($creditmemo) {
                if (!$creditmemo->isValidGrandTotal()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The credit memo\'s total must be positive.')
                    );
                }

                if (!empty($data['comment_text'])) {
                    $creditmemo->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );

                    $creditmemo->setCustomerNote($data['comment_text']);
                    $creditmemo->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                }

                if (isset($data['do_offline'])) {
                    //do not allow online refund for Refund to Store Credit
                    if (!$data['do_offline'] && !empty($data['refund_customerbalance_return_enable'])) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Cannot create online refund for Refund to Store Credit.')
                        );
                    }
                }
                $creditmemoManagement = $this->_objectManager->create(
                    \Magento\Sales\Api\CreditmemoManagementInterface::class
                );
                $creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $doOffline = isset($data['do_offline']) ? (bool)$data['do_offline'] : false;
                $creditmemoManagement->refund($creditmemo, $doOffline);

                if (!empty($data['send_email']) && $this->salesData->canSendNewCreditMemoEmail()) {
                    $this->creditmemoSender->send($creditmemo);
                }

                $this->messageManager->addSuccessMessage(__('You created the credit memo.'));
                $this->_getSession()->getCommentText(true);
                $resultRedirect->setPath('sales/order/view', ['order_id' => $creditmemo->getOrderId()]);
                return $resultRedirect;
            } else {
                $resultForward = $this->resultForwardFactory->create();
                $resultForward->forward('noroute');
                return $resultForward;
            }
        } catch (\BitPaySDK\Exceptions\RefundCreationException $refundCreationException) {
            $this->handleRefundCreationException($refundCreationException);
            $resultRedirect->setPath('sales/*/new', ['_current' => true]);

            return $resultRedirect;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_getSession()->setFormData($data);
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->messageManager->addErrorMessage(__('We can\'t save the credit memo right now.'));
        }
        $resultRedirect->setPath('sales/*/new', ['_current' => true]);
        return $resultRedirect;
    }

    /**
     * Create BitPay Refund
     *
     * @param bool|\Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @param array $data
     * @return void
     * @throws \BitPaySDK\Exceptions\BitPayException
     * @throws \BitPaySDK\Exceptions\RefundCreationException
     */
    protected function createBitpayRefund($creditmemo, array $data): void
    {
        $doOffline = isset($data['do_offline']) ? (bool)$data['do_offline'] : false;
        if ($doOffline) {
            return;
        }
        if (!$creditmemo) {
            return;
        }

        $orderId = $this->getRequest()->getParam('order_id');
        $order = $creditmemo->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod == Config::BITPAY_PAYMENT_METHOD_NAME) {
            $bitpayInvoiceData = $this->bitpayInvoiceRepository->getByOrderId($orderId);
            if ($bitpayInvoiceData) {
                $baseOrderRefund = $this->priceCurrency->round(
                    $creditmemo->getOrder()->getBaseTotalRefunded() + $creditmemo->getBaseGrandTotal()
                );
                $client = $this->bitpayClient->initialize();
                $invoiceId = $bitpayInvoiceData['invoice_id'];
                $bitpayInvoice = $client->getInvoice($invoiceId);
                $currency = $bitpayInvoice->getCurrency();
                $refund = $client->createRefund($invoiceId, $baseOrderRefund, $currency);
                $this->bitpayRefundRepository->add($orderId, $refund->getId(), $refund->getAmount());

                $amount = $this->priceCurrency->format($refund->getAmount());
                $message = "A refund request of {$amount} was sent for Bitpay Invoice {$refund->getId()}";
                $order->getPayment()->setData('message', $message);
            }
        }
    }

    /**
     * Handle refund creation exception
     *
     * @param \BitPaySDK\Exceptions\RefundCreationException $refundCreationException
     * @return void
     */
    protected function handleRefundCreationException(
        \BitPaySDK\Exceptions\RefundCreationException $refundCreationException
    ): void {
        $apiCode = $refundCreationException->getApiCode();
        switch ($apiCode) {
            case "010207":
                $this->logger->error($refundCreationException->getMessage());
                $this->messageManager->addErrorMessage(
                    __('A Credit Memo cannot be created until Payment is Confirmed.')
                );
                break;
            case "010000":
                $this->logger->error($refundCreationException->getMessage());
                $this->messageManager->addErrorMessage(
                    __('Only full refunds can be processed before the Payment is Completed')
                );
                break;
            default:
                $this->logger->error($refundCreationException->getMessage());
                $this->messageManager->addErrorMessage(__($refundCreationException->getMessage()));
        }
    }
}
