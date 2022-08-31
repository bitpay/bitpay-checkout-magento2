<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Ux Model
 */
class Ux implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            'redirect' => 'Redirect',
            'modal' => 'Modal',
        ];
    }
}
