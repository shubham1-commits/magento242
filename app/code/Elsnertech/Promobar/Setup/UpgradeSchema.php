<?php

namespace Elsnertech\Promobar\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

/**
 * Upgrades DB schema for a module
 *
 * @param SchemaSetupInterface $setup
 * @param ModuleContextInterface $context
 * @return void
 */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.0.8', '<')) {
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'category', [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'comment'  => 'category'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'layout', [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'comment'  => 'layout'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'product_page', [
                'type'     => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'product_page'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'priority', [
                'type'     => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'priority'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'store_id', [
                'type'     => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'store_id'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'start_date', [
                'type'     => Table::TYPE_DATE,
                'nullable' => true,
                'comment'  => 'start_date'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'end_date', [
                'type'     => Table::TYPE_DATE,
                'nullable' => true,
                'comment'  => 'end_date'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'image', [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'comment'  => 'image'
                ]);
                $setup->getConnection()->addColumn($setup->getTable('elsnertech_promobar'), 'promobar_container', [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'comment'  => 'promobar_container'
                ]);
            
        } else {
            $this->messageManager->addError(__("Please Check the Version"));
        }
        $setup->endSetup();
    }
}
