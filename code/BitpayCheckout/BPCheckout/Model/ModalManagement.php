<?php


namespace BitpayCheckout\BPCheckout\Model;

class ModalManagement implements \BitpayCheckout\BPCheckout\Api\ModalManagementInterface
{

    /**
     * {@inheritdoc}
     */
    public function postModal()
    {
       #database
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();              // Instance of object manager
        $resource      = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection    = $resource->getConnection();
        $table_name    = $resource->getTableName('bitpay_transactions');
        #json ipn
        $data = json_decode(file_get_contents("php://input"), true);
       #return $data
       
       die();
    }
}
