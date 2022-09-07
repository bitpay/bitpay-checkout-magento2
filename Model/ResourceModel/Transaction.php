<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transaction extends AbstractDb
{
    private const TABLE_NAME = 'bitpay_transactions';

    /**
     * @codeCoverageIgnore
     */
    public function _construct()
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }

    public function add(string $incrementId, string $invoiceID, string $status): void
    {
        $connection = $this->getConnection();
        $table_name = $connection->getTableName(self::TABLE_NAME);
        $connection->insertForce(
            $table_name,
            ['order_id' => $incrementId, 'transaction_id' => $invoiceID,'transaction_status'=> $status]
        );
    }
}
