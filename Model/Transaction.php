<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Magento\Framework\Model\AbstractModel;
use Bitpay\BPCheckout\Model\ResourceModel\Transaction as TransactionResource;

class Transaction extends AbstractModel
{
    /**
     * Initialize Transaction model
     * @codeCoverageIgnore
     */
    public function _construct()
    {
        $this->_init(TransactionResource::class);
    }
}
