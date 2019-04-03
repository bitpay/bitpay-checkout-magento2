<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
	
        $installer = $setup;

        $installer->startSetup();

		/**
         * IPN Log Table, used to keep track of incoming IPNs
         */

        $table = $installer->getConnection()->newTable(
            $installer->getTable('bitpay_ipns')
        )
		->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true,'nullable' => false, 'primary' => true],
            'bitpay_ipns'
        )
		->addColumn(
            'invoice_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '200',
            [],
            'Invoice Id'
        )
		->addColumn(
            'url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '400',
            [],
            'URL'
        )
        ->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '20',
            [],
            'Status'
        )
        ->addColumn(
            'btc_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'BTC Price'
        )
        ->addColumn(
            'price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'Price'
        )
        ->addColumn(
            'currency',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '10',
            [],
            'Currency'
        )
        ->addColumn(
            'invoice_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Invoice Time'
        )
        ->addColumn(
            'expiration_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Expiration Time'
        )
        ->addColumn(
            'current_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Current Time'
        )
        ->addColumn(
            'pos_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            [],
            'POS Data'
        )
        ->addColumn(
            'btc_paid',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'BTC Paid'
        )
        ->addColumn(
            'rate',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'Rate'
        )
        ->addColumn(
            'exception_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            [],
            'Exception Status'
        )
		->setComment('Bitpay Core bitpay_ipns')
        ->setOption('type', 'InnoDB')
        ->setOption('charset', 'utf8');
		
		$installer->getConnection()->createTable($table);


        /**
         * Table used to keep track of invoices that have been created. The
         * IPNs that are received are used to update this table.
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('bitpay_invoices')
        )
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            64,
            ['nullable' => false, 'primary' => true, 'auto_increment' => false],
            'bitpay_invoices'
        )
        ->addColumn(
            'quote_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Quote Id'
        )
        ->addColumn(
            'increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Increment Id'
        )
        ->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Updated At'
        )
        ->addColumn(
            'url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '200',
            [],
            'URL'
        )
        ->addColumn(
            'pos_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            [],
            'POS Data'
        )
        ->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '20',
            [],
            'Status'
        )
        ->addColumn(
            'btc_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'BTC Price'
        )
        ->addColumn(
            'btc_due',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'BTC Due'
        )
        ->addColumn(
            'price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'Price'
        )
        ->addColumn(
            'currency',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '10',
            [],
            'Currency'
        )
        ->addColumn(
            'ex_rates',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            [],
            'Ex Rates'
        )
        ->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64',
            [],
            'Order Id'
        )
        ->addColumn(
            'invoice_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Invoice Time'
        )
        ->addColumn(
            'expiration_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Expiration Time'
        )
        ->addColumn(
            'current_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            '11',
            [],
            'Current Time'
        )
        ->addColumn(
            'btc_paid',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'BTC Paid'
        )
        ->addColumn(
            'rate',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '16,8',
            [],
            'Rate'
        )
        ->addColumn(
            'exception_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '255',
            [],
            'Exception Status'
        )
        ->addColumn(
            'token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '164',
            [],
            'Token'
        )    
        ->setComment('Bitpay Core bitpay_invoices')
        ->setOption('type', 'InnoDB')
        ->setOption('charset', 'utf8');
        
        $installer->getConnection()->createTable($table);

        $installer->endSetup();

    }
}
