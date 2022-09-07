<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\ResourceModel\Transaction as TransactionResource;

class TransactionRepository
{
    private $resourceTransaction;

    public function __construct(TransactionResource $resourceTransaction)
    {
        $this->resourceTransaction = $resourceTransaction;
    }

    public function add(string $incrementId, string $invoiceID, string $status): void
    {
        $this->resourceTransaction->add($incrementId, $invoiceID, $status);
    }
}
