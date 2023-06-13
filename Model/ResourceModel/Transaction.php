<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transaction extends AbstractDb
{
    private const TABLE_NAME = 'bitpay_transactions';

    /**
     * Resource initialization
     *
     * @codeCoverageIgnore
     */
    public function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
    }

    /**
     * Add BitPay transaction
     *
     * @param string $incrementId
     * @param string $invoiceID
     * @param string $status
     * @return void
     */
    public function add(string $incrementId, string $invoiceID, string $status): void
    {
        $connection = $this->getConnection();
        $table_name = $connection->getTableName(self::TABLE_NAME);
        $connection->insertForce(
            $table_name,
            ['order_id' => $incrementId, 'transaction_id' => $invoiceID,'transaction_status'=> $status]
        );
    }

    /**
     * Find transaction by order_id and transaction_id
     *
     * @param string $orderId
     * @param string $orderInvoiceId
     * @return array|null
     */
    public function findBy(string $orderId, string $orderInvoiceId): ?array
    {
        $connection = $this->getConnection();
        $tableName = $connection->getTableName(self::TABLE_NAME);

        $sql = $connection->select()
            ->from($tableName)
            ->where('order_id = ?', $orderId)
            ->where('transaction_id = ?', $orderInvoiceId);

        $row = $connection->fetchAll($sql);

        if (!$row) {
            return null;
        }

        return $row;
    }

    /**
     * Update transaction
     *
     * @param string $field
     * @param string $value
     * @param array $where
     * @return void
     */
    public function update(string $field, string $value, array $where): void
    {
        $connection = $this->getConnection();
        $tableName = $connection->getTableName(self::TABLE_NAME);

        $connection->update($tableName, [$field => $value], $where);
    }
}
