<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Invoice resource
 */
class Invoice extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('bitpay_invoices', 'id');
        $this->_isPkAutoIncrement = false;
    }

}
