<?php
namespace Elsnertech\Zohointegration\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
	public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
		$installer = $setup;

		$installer->startSetup();

		if(version_compare($context->getVersion(), '1.7.0', '<')) {
			$installer->getConnection()->addColumn(
	            $installer->getTable('sales_order'),
	            'listed_status',
	            [
	                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
	                'length' => 255,
	                'default' => "Non Listed",
	                'comment' =>'listed_status'
	            ]
        	);
		}

		if(version_compare($context->getVersion(), '2.2.0', '<')) {

			$installer->getConnection()->addColumn(
	            $installer->getTable('catalog_product_entity'),
	            'listed_status',
	            [
	                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
	                'length' => 255,
	                'default' => "Non Listed",
	                'comment' =>'listed_status'
	            ]
        	);
		}


		if(version_compare($context->getVersion(), '2.3.0', '<')) {
			
			$installer->getConnection()->addColumn(
	            $installer->getTable('customer_entity'),
	            'listed_status',
	            [
	                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
	                'length' => 20,
	                'default' => "Non Listed",
	                'comment' =>'listed_status'
	            ]
        	);
		}

		$installer->endSetup();

	}
}