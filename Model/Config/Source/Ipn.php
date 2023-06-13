<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * IPN Model
 */
class Ipn implements ArrayInterface
{
    /**
     * Return array of IPN options
     *
     * @return string[]
     */
    public function toOptionArray(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
        ];
    }
}
