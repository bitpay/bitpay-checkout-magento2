<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bitpay\BPCheckout\Model\Config\Source;


/**
 * Environment Model
 */
class Ux implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            'redirect' => 'Redirect',
            'modal' => 'Modal',
        ];

    }
}
