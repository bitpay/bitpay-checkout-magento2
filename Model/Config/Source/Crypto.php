<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Crypto Model
 */
class Crypto implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'BTC', 'label' => __('BTC')],
            ['value' => 'BCH', 'label' => __('BCH')]
        ];
    }
}
