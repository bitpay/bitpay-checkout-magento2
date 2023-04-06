<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Environment Model
 */
class Environment implements ArrayInterface
{
    /**
     * Return array of Environment options
     *
     * @return string[]
     */
    public function toOptionArray(): array
    {
        return [
            'test' => 'Test',
            'prod' => 'Production',
        ];
    }
}
