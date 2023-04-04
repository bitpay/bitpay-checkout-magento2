<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BitpayInvoice extends AbstractDb
{
    private const TABLE_NAME = 'bitpay_invoice';

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
     * Add BitPay Invoice data
     *
     * @param string $orderId
     * @param string $invoiceID
     * @param string $expirationTime
     * @param int|null $acceptanceWindow
     * @return void
     */
    public function add(string $orderId, string $invoiceID, string $expirationTime, ?int $acceptanceWindow)
    {
        $connection = $this->getConnection();
        $table_name = $connection->getTableName(self::TABLE_NAME);
        $connection->insert(
            $table_name,
            [
                'order_id' => $orderId,
                'invoice_id' => $invoiceID,
                'expiration_time' => $expirationTime,
                'acceptance_window'=> $acceptanceWindow
            ]
        );
    }

    /**
     * Get invoice by order id
     *
     * @param string $orderId
     * @return array|null
     */
    public function getByOrderId(string $orderId): ?array
    {
        $connection = $this->getConnection();
        $tableName = $connection->getTableName(self::TABLE_NAME);

        $sql = $connection->select()
            ->from($tableName)
            ->where('order_id = ?', $orderId);

        $row = $connection->fetchRow($sql);

        if (!$row) {
            return null;
        }

        return $row;
    }
}
