<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bitpay\Core\Block;

use Magento\Framework\App\ObjectManager;

/**
 * Base payment iformation block
 */
class Info extends \Magento\Payment\Block\Info
{

    /**
     * @var string
     */
    protected $_template = 'Bitpay_Core::bitpay/info/default.phtml';

    public function getBitpayInvoiceUrl()
    {
        /* @var $helper \Bitpay\Core\Helper\Data */
        /* @var $invoice \Bitpay\Core\Model\Invoice */
        /* @var $order \Magento\Sales\Model\Order */

        $objectManager  = ObjectManager::getInstance();
        $order          = $this->getInfo()->getOrder();
        $helper         = $objectManager->get('\Bitpay\Core\Helper\Data');
        $invoice        = $objectManager->get('\Bitpay\Core\Model\Invoice');

        if (false === isset($order) || true === empty($order)) {
            $message = $helper->logError('Could not obtain the order.', __METHOD__);
            throw new \Exception($message);
        }

        $incrementId = $order->getIncrementId();

        if (false === isset($incrementId) || true === empty($incrementId)) {
            $message = $helper->logError('Could not obtain the incrementId.', __METHOD__);
            throw new \Exception($message);
        }

        $invoice = $invoice->load($incrementId, 'increment_id');

        if (true === isset($invoice) && false === empty($invoice)) {
            return $invoice->getUrl();
        }
    }
    
}
