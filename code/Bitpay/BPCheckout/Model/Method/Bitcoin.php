<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Model\Method;

use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Bitcoin payment method
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Bitcoin extends AbstractMethod
{
    
    const CODE = 'bitpay';

    protected $_code                        = self::CODE;
    protected $_formBlockType               = 'Bitpay\Core\Block\Form\Bitpay';
    protected $_infoBlockType               = 'Bitpay\Core\Block\Info';

    protected $_isGateway                   = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = false;
    protected $_canUseInternal              = false;
    protected $_isInitializeNeeded          = false;
    protected $_canFetchTransactionInfo     = false;
    protected $_canManagerRecurringProfiles = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canVoid                     = false;


    protected $_debugReplacePrivateDataKeys = array();

    protected static $_redirectUrl;

    /**
     * @return \Bitpay\Core\Helper\Data
     */
    protected function getHelper()
    {
    	return ObjectManager::getInstance()->get('\Bitpay\Core\Helper\Data');
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Exception
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (false === isset($payment) || false === isset($amount) || true === empty($payment) || true === empty($amount)) {
            $message = $this->getHelper()->logError('Missing payment or amount parameters.', __METHOD__);

            throw new \Exception($message);
        }

        $this->getHelper()->logInfo('Authorizing new order.', __METHOD__);

        /* @var $session \Magento\Checkout\Model\Session */
        /* @var $payment \Magento\Sales\Model\Order\Payment */

        $bitPayFactory  = $this->getHelper()->getBitPayFactory();
        $order          = $payment->getOrder();
        $invoice        = $bitPayFactory->createInvoiceFromOrder($order);

        try {
            $bitPayInvoice = $this->getHelper()->getBitPayService()->getClient()->createInvoice($invoice);

        } catch (\Exception $e) {
            $message = $this->getHelper()->logError('Could not authorize transaction due: ' . $e->getMessage(), __METHOD__);
            
            //display min invoice value error    
            if(strpos($e->getMessage(), 'Invoice price must be') !== FALSE)
            {
		throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }

            throw new \Exception($message);
        }

        self::$_redirectUrl = ($this->getHelper()->isFullScreen())
            ? $bitPayInvoice->getUrl()
            : $bitPayInvoice->getUrl().'&view=iframe';
        
        $this->getHelper()->logInfo('BitPay Invoice created. Invoice URL: ' . $bitPayInvoice->getUrl(), __METHOD__);
        
        if ( ! $bitPayInvoice) {
            $message = $this->getHelper()->logError('Missing or empty $invoice parameter.', __METHOD__);

            throw new \Exception($message);
        };

        /* @var $invoiceModel \Bitpay\Core\Model\Invoice */
        $invoiceModel = ObjectManager::getInstance()->create('Bitpay\Core\Model\Invoice');
        $invoiceModel->setData(array(
            'id'                => $invoice->getId(),
            'url'               => $invoice->getUrl(),
            'pos_data'          => $invoice->getPosData(),
            'status'            => $invoice->getStatus(),
            'btc_price'         => $invoice->getBtcPrice(),
            'price'             => $invoice->getPrice(),
            'currency'          => $invoice->getCurrency()->getCode(),
            'order_id'          => $invoice->getOrderId(),
            'invoice_time'      => intval($invoice->getInvoiceTime()->getTimestamp() / 1000),
            'expiration_time'   => intval($invoice->getExpirationTime()->getTimestamp() / 1000),
            'current_time'      => intval($invoice->getCurrentTime()->getTimestamp() / 1000),
            'btc_paid'          => $invoice->getBtcPaid(),
            'rate'              => $invoice->getRate(),
            'exception_status'  => !empty($invoice->getExceptionStatus()) ? $invoice->getExceptionStatus() : null,
            'quote_id'          => $order->getQuoteId(),
            'increment_id'      => $order->getIncrementId(),
        ));

        $invoiceModel->save();

        $debugLine = sprintf('The "bitpay_invoices" table has been updated updated for BitPay Invoice ID %s.', $bitPayInvoice->getId());

        $this->getHelper()->logInfo($debugLine, __METHOD__);

        return $this;
    }

    /**
     * This makes sure that the merchant has setup the extension correctly
     * and if they have not, it will not show up on the checkout.
     *
     * @see Mage_Payment_Model_Method_Abstract::canUseCheckout()
     * @return bool
     */
    public function canUseCheckout()
    {
        $token = $this->getHelper()->getToken();

        if ( ! $token) {
            /**
             * Merchant must goto their account and create a pairing code to
             * enter in.
             */
            $this->getHelper()->logError('There was an error retrieving the token store param from the database or this Magento store does not have a BitPay token.');

            return false;
        }

        $this->getHelper()->logInfo('Token obtained from storage successfully.');

        return true;
    }

    /**
     * Fetch an invoice from BitPay
     *
     * @param $id
     * @return \Bitpay\Invoice|\Bitpay\InvoiceInterface
     * @throws \Exception
     */
    public function fetchInvoice($id)
    {
        if (false === isset($id) || true === empty($id)) {
            $message = $this->getHelper()->logError('Missing or invalid id parameter.', __METHOD__);
            throw new \Exception($message);
        } else {
            $this->getHelper()->logInfo('Function called with id ' . $id, __METHOD__);
        }

        $client  = $this->getHelper()->getBitPayService()->getClient();
        $invoice = $client->getInvoice($id);

        if (false === isset($invoice) || true === empty($invoice)) {
            $message = $this->getHelper()->logError('Could not retrieve invoice from BitPay.', __METHOD__);
            throw new \Exception($message);
        } else {
            $this->getHelper()->logInfo('Successfully retrieved invoice id ' . $id . ' from BitPay.', __METHOD__);
        }

        return $invoice;
    }

    /**
     * This is called when a user clicks the `Place Order` button
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $this->getHelper()->logInfo('The $_redirectUrl variable value is ' . self::$_redirectUrl);

        return self::$_redirectUrl;
    }
    
}
