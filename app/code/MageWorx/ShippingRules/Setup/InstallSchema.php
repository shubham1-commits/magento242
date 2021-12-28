<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * InstallSchema constructor.
     *
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     */
    public function __construct(
        \Magento\Framework\Module\ResourceInterface $moduleResource
    ) {
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /**
         * Create table 'mageworx_shippingrules'
         */
        $table = $setup->getConnection()->newTable(
            $setup->getTable('mageworx_shippingrules')
        )->addColumn(
            'rule_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Rule Id'
        )->addColumn(
            'name',
            Table::TYPE_TEXT,
            255,
            [],
            'Name'
        )->addColumn(
            'description',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Description'
        )->addColumn(
            'from_date',
            Table::TYPE_DATE,
            null,
            ['nullable' => true, 'default' => null],
            'From'
        )->addColumn(
            'to_date',
            Table::TYPE_DATE,
            null,
            ['nullable' => true, 'default' => null],
            'To'
        )->addColumn(
            'days_of_week',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Days of Week (Available On)'
        )->addColumn(
            'is_active',
            Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '0'],
            'Is Active'
        )->addColumn(
            'conditions_serialized',
            Table::TYPE_TEXT,
            '2M',
            [],
            'Conditions Serialized'
        )->addColumn(
            'actions_serialized',
            Table::TYPE_TEXT,
            '2M',
            [],
            'Actions Serialized'
        )->addColumn(
            'stop_rules_processing',
            Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '1'],
            'Stop Rules Processing'
        )->addColumn(
            'shipping_methods',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Shipping Methods'
        )->addColumn(
            'disabled_shipping_methods',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Disabled Shipping Methods'
        )->addColumn(
            'enabled_shipping_methods',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Enabled Shipping Methods'
        )->addColumn(
            'sort_order',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Sort Order (Priority)'
        )->addColumn(
            'action_type',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Action Type'
        )->addColumn(
            'simple_action',
            Table::TYPE_TEXT,
            '64k',
            [],
            'Simple Action'
        )->addColumn(
            'amount',
            Table::TYPE_TEXT,
            '2M',
            []
        )->addColumn(
            'time_from',
            Table::TYPE_INTEGER,
            null,
            [],
            'Time From'
        )->addColumn(
            'time_to',
            Table::TYPE_INTEGER,
            null,
            [],
            'Time To'
        )->addColumn(
            'use_time',
            Table::TYPE_SMALLINT,
            null,
            [],
            'Use or not time flag'
        )->addColumn(
            'time_enabled',
            Table::TYPE_SMALLINT,
            null,
            [],
            'Time Range is enabled or disabled time'
        )->addIndex(
            $setup->getIdxName('mageworx_shippingrules', ['is_active', 'sort_order', 'to_date', 'from_date']),
            ['is_active', 'sort_order', 'to_date', 'from_date']
        )->setComment(
            'Shippingrules'
        );

        $setup->getConnection()->createTable($table);

        $storeTable               = $setup->getTable('store');
        $customerGroupsTable      = $setup->getTable('customer_group');
        $rulesStoresTable         = $setup->getTable('mageworx_shippingrules_store');
        $rulesCustomerGroupsTable = $setup->getTable('mageworx_shippingrules_customer_group');

        /**
         * Create table 'mageworx_shippingrules_store' if not exists. This table will be used instead of
         * column store_ids of main catalog shipping rules table
         */
        $table = $setup->getConnection()->newTable(
            $rulesStoresTable
        )->addColumn(
            'rule_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Rule Id'
        )->addColumn(
            'store_id',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Store View Id'
        )->addIndex(
            $setup->getIdxName('mageworx_shippingrules_store', ['store_id']),
            ['store_id']
        )->addForeignKey(
            $setup->getFkName('mageworx_shippingrules_store', 'rule_id', 'mageworx_shippingrules', 'rule_id'),
            'rule_id',
            $setup->getTable('mageworx_shippingrules'),
            'rule_id',
            Table::ACTION_CASCADE
        )->addForeignKey(
            $setup->getFkName('mageworx_shippingrules_store', 'store_id', 'store', 'store_id'),
            'store_id',
            $storeTable,
            'store_id',
            Table::ACTION_CASCADE
        )->setComment(
            'MageWorx Shipping Rules To Stores Relations'
        );

        $setup->getConnection()->createTable($table);

        /**
         * Create table 'mageworx_shippingrules_customer_group' if not exists. This table will be used instead of
         * column customer_group_ids of main shipping rules table
         */
        $table = $setup->getConnection()->newTable(
            $rulesCustomerGroupsTable
        )->addColumn(
            'rule_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Rule Id'
        )->addColumn(
            'customer_group_id',
            $this->getCustomerGroupColumnType($setup),
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Customer Group Id'
        )->addIndex(
            $setup->getIdxName('mageworx_shippingrules_customer_group', ['customer_group_id']),
            ['customer_group_id']
        )->addForeignKey(
            $setup->getFkName(
                'mageworx_shippingrules_customer_group',
                'rule_id',
                'mageworx_shippingrules',
                'rule_id'
            ),
            'rule_id',
            $setup->getTable('mageworx_shippingrules'),
            'rule_id',
            Table::ACTION_CASCADE
        )->addForeignKey(
            $setup->getFkName(
                'mageworx_shippingrules_customer_group',
                'customer_group_id',
                'customer_group',
                'customer_group_id'
            ),
            'customer_group_id',
            $customerGroupsTable,
            'customer_group_id',
            Table::ACTION_CASCADE
        )->setComment(
            'MageWorx Shipping Rules To Customer Groups Relations'
        );

        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return string
     */
    protected function getCustomerGroupColumnType(SchemaSetupInterface $setup)
    {
        $customerGroupTable  = $setup->getConnection()->describeTable($setup->getTable('customer_group'));
        $customerGroupIdType = $customerGroupTable['customer_group_id']['DATA_TYPE'] == 'int'
            ? Table::TYPE_INTEGER : $customerGroupTable['customer_group_id']['DATA_TYPE'];

        return $customerGroupIdType;
    }
}
