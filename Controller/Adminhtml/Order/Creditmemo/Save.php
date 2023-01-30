<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Controller\Adminhtml\Order\Creditmemo;

use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\BitpayRefundRepository;
use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Magento\Backend\App\Action;
use Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save as CreditmemoSave;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Directory\Model\PriceCurrency;

class Save extends CreditmemoSave
{
    protected $bitpayClient;

    /**
     * @var PriceCurrency $priceCurrency
     */
    protected $priceCurrency;

    protected $bitpayInvoiceRepository;

    protected $bitpayRefundRepository;

    /**
     * @var SalesData
     */
    private $salesData;

    public function __construct(
        Action\Context $context,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader,
        CreditmemoSender $creditmemoSender,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        Client $bitpayClient,
        PriceCurrency $priceCurrency,
        BitpayInvoiceRepository $bitpayInvoiceRepository,
        BitpayRefundRepository $bitpayRefundRepository,
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
    }

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
            $this->createBitpayRefund($creditmemo);
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
     * @param bool|\Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return void
     * @throws \BitPaySDK\Exceptions\BitPayException
     * @throws \BitPaySDK\Exceptions\RefundCreationException
     */
    protected function createBitpayRefund($creditmemo): void
    {
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
            }
        }
    }
}
