<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model;

use Magento\Framework\Model\AbstractModel;


class Invoice extends AbstractModel
{

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('Bitpay\Core\Model\ResourceModel\Invoice');
    }
   
}