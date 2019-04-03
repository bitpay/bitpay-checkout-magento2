<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;

/**
 * Ipntab model
 */
class Ipn extends AbstractModel
{

    /**
     * @return void
     */
    public function _construct() {
        $this->_init('Bitpay\Core\Model\ResourceModel\Ipn');
    }

    /**
     * @param string $quoteId
     * @param array  $statuses
     *
     * @return boolean
     */
    function GetStatusReceived($quoteId, array $statuses) {
        if (!$quoteId) {
            return false;
        }

        $objectManager = ObjectManager::getInstance();

        /* @var $helper \Bitpay\Core\Helper\Data */
        $helper = $objectManager->get('\Bitpay\Core\Helper\Data');

        /* @var $order \Magento\Sales\Model\Order */
        $order = $objectManager->get('\Magento\Sales\Model\Order')->load($quoteId, 'quote_id');

        if (!$order) {
            $helper->logError('Order not found for quoteId . ' . $quoteId, __METHOD__);
            return false;
        }

        $orderIncrementId = $order->getIncrementId();

        if (false === isset($orderIncrementId) || true === empty($orderIncrementId)) {
            $helper->logError('OrderId not found for quoteId ' . $orderIncrementId, __METHOD__);
            return false;
        }
        $collection = $objectManager->create('\Bitpay\Core\Model\Ipn')->getCollection();

        /* @var $ipnItem \Bitpay\Core\Model\Ipn */
        foreach ($collection as $ipnItem) {
            $pos = json_decode($ipnItem->getData('pos_data'), true);

            if(!isset($pos['orderId'])){
                continue;
            }

            if ($orderIncrementId == $pos['orderId']) {
                if (in_array($ipnItem->getData('status'), $statuses)) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * @param string $quoteId
     *
     * @return boolean
     */
    function GetQuotePaid($quoteId)
    {
        return $this->GetStatusReceived($quoteId, array('paid', 'confirmed', 'complete'));
    }
   
}