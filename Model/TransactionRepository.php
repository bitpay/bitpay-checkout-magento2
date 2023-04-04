<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\ResourceModel\Transaction as TransactionResource;

class TransactionRepository
{
    /** @var TransactionResource $resourceTransaction */
    private $resourceTransaction;

    /**
     * @param TransactionResource $resourceTransaction
     */
    public function __construct(TransactionResource $resourceTransaction)
    {
        $this->resourceTransaction = $resourceTransaction;
    }

    /**
     * Add Transaction
     *
     * @param string $incrementId
     * @param string $invoiceID
     * @param string $status
     * @return void
     */
    public function add(string $incrementId, string $invoiceID, string $status): void
    {
        $this->resourceTransaction->add($incrementId, $invoiceID, $status);
    }

    /**
     * Find Transaction by order_id and transaction_id
     *
     * @param string $orderId
     * @param string $orderInvoiceId
     * @return array|null
     */
    public function findBy(string $orderId, string $orderInvoiceId): ?array
    {
        return $this->resourceTransaction->findBy($orderId, $orderInvoiceId);
    }

    /**
     * Update Transaction
     *
     * @param string $field
     * @param string $value
     * @param array $where
     * @return void
     */
    public function update(string $field, string $value, array $where): void
    {
        $this->resourceTransaction->update($field, $value, $where);
    }
}
