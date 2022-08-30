<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bitpay\BPCheckout\Model;



/**
 * Pay In Store payment method model
 */
class BPCheckout extends \Magento\Payment\Model\Method\AbstractMethod
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
