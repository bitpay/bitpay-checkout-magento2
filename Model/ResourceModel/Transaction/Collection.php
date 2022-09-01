<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\ResourceModel\Transaction;

use Bitpay\BPCheckout\Model\Transaction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bitpay\BPCheckout\Model\ResourceModel\Transaction as ResourceTransaction;

/**
 * Transaction collection class
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize transaction collection
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(Transaction::class, ResourceTransaction::class);
    }
}
