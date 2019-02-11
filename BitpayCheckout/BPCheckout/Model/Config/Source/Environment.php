<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace BitpayCheckout\BPCheckout\Model\Config\Source;


/**
 * Environment Model
 */
class Environment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            'test' => 'Test',
            'prod' => 'Production',
        ];

    }
}
