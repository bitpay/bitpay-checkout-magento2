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
class Capture implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            '1' => 'Yes',
            '0' => 'No',
        ];
    }
}
