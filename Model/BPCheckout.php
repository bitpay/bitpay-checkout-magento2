<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Pay In Store payment method model
 */
class BPCheckout extends AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'bpcheckout';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
