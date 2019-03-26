<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bitpay\BPCheckout\Model\Config\Source;


/**
 * Environment Model
 */
class Capture implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            '1' => 'Yes',
            '0' => 'No',
        ];

    }
}
