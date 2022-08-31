<?php
declare(strict_types=1);
namespace Bitpay\BPCheckout\Model;

class ModalManagement implements \Bitpay\BPCheckout\Api\ModalManagementInterface
{
    private $_resourceConnection;

    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
    {
        $this->_resourceConnection = $resourceConnection;
    }
    /**
     * {@inheritdoc}
     */
    public function postModal()
    {
       #database
        $resource = $this->_resourceConnection;
        $connection    = $resource->getConnection();
        $table_name    = $resource->getTableName('bitpay_transactions');
        #json ipn
        $data = json_decode(file_get_contents("php://input"), true);
    }
}
