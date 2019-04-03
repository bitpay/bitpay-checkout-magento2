<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */

namespace Bitpay\Core\Controller\Ipn;

use Bitpay\Core\Helper\Data;
use Bitpay\Core\Model\Invoice;
use Bitpay\Core\Model\Ipn;
use Bitpay\Core\Model\Order\Payment;
use Bitpay\Math\BcEngine;
use Bitpay\Math\Math;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;

/**
 * @route bitpay/ipn/
 */
class Index extends Action {

    /**
     * @var Data
     */
	protected $helper;

    /**
     * @var Ipn
     */
	protected $ipn;

    /**
     * @var Order
     */
	protected $order;

    /**
     * @var Invoice
     */
	protected $invoice;

    /**
     * @var Payment
     */
	protected $payment;

    /**
     * Index constructor.
     * @param Context $context
     * @param Data $helper
     * @param Ipn $ipn
     * @param Order $order
     * @param Invoice $invoice
     * @param Payment $payment
     */
	public function __construct(Context $context, Data $helper, Ipn $ipn, Order $order, Invoice $invoice,  Payment $payment) {
		parent::__construct($context);

		$this->helper = $helper;
		$this->ipn = $ipn;
		$this->order = $order;
		$this->invoice = $invoice;
		$this->payment = $payment;
	}

	/**
	 * bitpay's IPN lands here
	 *
	 * @route /bitpay/ipn
	 * @route /bitpay/ipn/index
	 */
	public function execute() {
		if (false === ini_get('allow_url_fopen')) {
			ini_set('allow_url_fopen', true);
		}

		$raw_post_data = file_get_contents('php://input');

		if (false === $raw_post_data) {
			$message = $this->helper->logError('Could not read from the php://input stream or invalid Bitpay IPN received.', __METHOD__);
			throw new \Exception($message);
		}

        $this->helper->logInfo(sprintf('Incoming IPN message from BitPay: $s', json_encode($raw_post_data)), __METHOD__);

		// Magento doesn't seem to have a way to get the Request body
		$ipn = json_decode($raw_post_data);

		if (true === empty($ipn)) {
            $message = $this->helper->logError('Could not decode the JSON payload from BitPay.', __METHOD__);
            throw new \Exception($message);
		}

		if (true === empty($ipn->id) || false === isset($ipn->posData)) {
            $message = $this->helper->logError(sprintf('Did not receive order ID in IPN: %s', $ipn), __METHOD__);
            throw new \Exception($message);
		}

		$ipn->posData       = is_string($ipn->posData) ? json_decode($ipn->posData) : $ipn->posData;
		$ipn->buyerFields   = isset($ipn->buyerFields) ? $ipn->buyerFields : new \stdClass();

        $this->helper->logInfo('Encoded IPN: ' . json_encode($ipn));

        /* @var $ipnModel \Bitpay\Core\Model\Ipn */
        $ipnModel = $this->_objectManager->create('Bitpay\Core\Model\Ipn');
        $ipnModel->setData([
            'invoice_id'        => (string) $ipn->id,
            'url'               => (string) $ipn->url,
            'pos_data'          => json_encode($ipn->posData),
            'status'            => (string) $ipn->status,
            'btc_price'         => $ipn->btcPrice,
            'price'             => $ipn->price,
            'currency'          => (string) $ipn->currency,
            'invoice_time'      => intval($ipn->invoiceTime / 1000),
            'expiration_time'   => intval($ipn->expirationTime / 1000),
            'current_time'      => intval($ipn->currentTime / 1000),
            'btc_paid'          => $ipn->btcPaid,
            'rate'              => $ipn->rate,
            'exception_status'  => $ipn->exceptionStatus,
        ]);

        $ipnModel->save();

        $order = null;

		// Order isn't being created for iframe...
		if (isset($ipn->posData->orderId)) {
			$order = $this->order->loadByIncrementId($ipn->posData->orderId);
		}
		elseif(isset($ipn->posData->quoteId)) {
			$order = $this->order->load($ipn->posData->quoteId, 'quote_id');
		}

		if ( ! $order) {
            $message = $this->helper->logError('Invalid Bitpay IPN received.', __METHOD__);
            throw new \Exception($message);
		}

		$orderId = $order->getId();

		if (! $orderId) {
            $message = $this->helper->logError('Invalid Bitpay IPN received.', __METHOD__);
            throw new \Exception($message);
		}

		/**
		 * Ask BitPay to retreive the invoice so we can make sure the invoices
		 * match up and no one is using an automated tool to post IPN's to merchants
		 * store.
		 */

		/* @var $bitcoin \Bitpay\Core\Model\Method\Bitcoin */
		$bitcoin = ObjectManager::getInstance()->get('Bitpay\Core\Model\Method\Bitcoin');
		$invoice = $bitcoin->fetchInvoice($ipn->id);

		if (! $invoice) {
            $message = $this->helper->logError('Could not retrieve the invoice details for the ipn ID of ' . $ipn->id, __METHOD__);
            throw new \Exception($message);
		}

		// Does the status match?
		/*if ($invoice -> getStatus() != $ipn -> status) {
			$this -> _bitpayHelper -> debugData('[ERROR] In \Bitpay\Core\Controller\Ipn::indexAction(), IPN status and status from BitPay are different. Rejecting this IPN!');
			$this -> throwException('There was an error processing the IPN - statuses are different. Rejecting this IPN!');
		}*/

        Math::setEngine(new BcEngine());

		// Does the price match?
		if ( Math::cmp($invoice->getPrice(), $ipn->price) !== 0) {
            $message = $this->helper->logError('IPN price and invoice price are different. Rejecting this IPN!', __METHOD__);
            throw new \Exception($message);
		}

		// Update the order to notifiy that it has been paid

        $this->helper->logInfo('IPN status: ' . $ipn->status);
		
		if ($ipn->status === 'paid' || $ipn->status === 'confirmed') {
			try{				
			    $payment = $this->payment->setOrder($order);
			}
			catch(\Exception $e){
                $this->helper->logError($e, __METHOD__);
			}

			if ($payment) {
				if ($ipn->status === 'confirmed') {
				    /* @var $invoiceService \Magento\Sales\Model\Service\InvoiceService */
                    $invoiceService = $this->_objectManager->create('Magento\Sales\Model\Service\InvoiceService');
					// Create invoice for this order
					$order_invoice = $invoiceService->prepareInvoice($order);

					// Make sure there is a qty on the invoice
					if (! $order_invoice->getTotalQty()) {
						throw new \Magento\Framework\Exception\LocalizedException(__('You can\'t create an invoice without products.'));
					}

					// Register as invoice item
					$order_invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
					$order_invoice->register();

					// Save the invoice to the order
					$transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')->addObject($order_invoice)->addObject($order_invoice->getOrder());
					$transaction->save();

					$order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $order_invoice -> getId()))->setIsCustomerNotified(true);
				}
				else {
                    $order->addStatusHistoryComment(__('The payment has been received, but the transaction has not been confirmed on the bitcoin network. This will be updated when the transaction has been confirmed.'));
                }

				$order->save();

			} else {
                $message = $this->helper->logError('Could not create a payment object in the Bitpay IPN controller.', __METHOD__);
                throw new \Exception($message);
			}
		}

		// use state as defined by Merchant
		$state = ObjectManager::getInstance()->create('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(sprintf('payment/bitpay/invoice_%s', $invoice->getStatus()));

		if (false === isset($state) || true === empty($state)) {
            $message = $this->helper->logError('Could not retrieve the defined state parameter to update this order in the Bitpay IPN controller.', __METHOD__);
            throw new \Exception($message);
		}

		// Check if status should be updated
		switch ($order->getStatus()) {
			case Order::STATE_CANCELED :
			case Order::STATUS_FRAUD :
			case Order::STATE_CLOSED :
			case Order::STATE_COMPLETE :
			case Order::STATE_HOLDED :
				// Do not Update
				break;
			case Order::STATE_PENDING_PAYMENT :
			case Order::STATE_PROCESSING :
			default :
				$order->addStatusToHistory($state, sprintf(' Incoming IPN status "%s" updated order state to "%s"', $invoice->getStatus(), $state))->save();
				break;
		}
	}

}

