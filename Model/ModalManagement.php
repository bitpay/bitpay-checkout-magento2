<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Magento\Framework\App\ResourceConnection;
use Bitpay\BPCheckout\Api\ModalManagementInterface;

class ModalManagement implements ModalManagementInterface
{
    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function postModal()
    {
        #database
        $resource = $this->resourceConnection;
        $connection    = $resource->getConnection();
        $table_name    = $resource->getTableName('bitpay_transactions');
        #json ipn
        //phpcs:ignore
        $data = json_decode(file_get_contents("php://input"), true);
    }
}
