<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Setup;
 
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class UpgradeSchema implements UpgradeSchemaInterface
{

	public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

		$installer = $setup;

        $installer->startSetup();

		if (version_compare($context->getVersion(), '2.0.1') < 0) {
            /**
			 * IPN Log Table, used to keep track of incoming IPNs
			 * 
			 * Fixes `curent_time` typo
			 */
			$tableName = $installer->getTable('bitpay_ipns');

			if ($installer->getConnection()->isTableExists($tableName) == true) {

				$installer->getConnection()->changeColumn($tableName, 'current_time', 'current_time', array('type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER));
			}
        }

		$installer->endSetup();
	}
}
