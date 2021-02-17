<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bitpay\BPCheckout\Model\Config\Source;

/**
 *Refund Status Model
 */
class Refundstatus implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {

        return [
            ['value' => 'closed', 'label' => __('True')],
            ['value' => 'ignore', 'label' => __('False')]
        ];

    }
}
