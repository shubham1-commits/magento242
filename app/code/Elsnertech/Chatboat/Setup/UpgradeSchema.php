<?php
namespace Elsnertech\Chatboat\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
 
class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $categories = $setup->getTable('customerchat');
            if ($setup->getConnection()->isTableExists($categories) != true) {
                $tableCategories = $setup->getConnection()
                    ->newTable($categories)
                     ->addColumn(
                         'id',
                         \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                         null,
                         ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                         'id'
                     )
                ->addColumn(
                    'customerid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => 1],
                    'customerid'
                )->addColumn(
                    'chat',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'chat'
                )
                ->setComment('Magebay Categories ')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
                $setup->getConnection()->createTable($tableCategories);
            }
        }

        if(version_compare($context->getVersion(), '1.0.6', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('customerchat'),
                'sender',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '124',
                    'comment' => 'sender'
                ]
            );
        }

        if(version_compare($context->getVersion(), '1.0.7', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('customerchat'),
                'receiver',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '1400',
                    'comment' => 'receiver'
                ]
            );
        }

        if(version_compare($context->getVersion(), '1.0.8', '<')) {
            $setup->getConnection()->addColumn(
                $setup->getTable('customerchat'),
                'created_at',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '1400',
                    'comment' => 'created_at'
                ]
            );
        }

        if(version_compare($context->getVersion(), '1.3.0', '<')) {
            $setup->getConnection()->dropColumn($setup->getTable('customerchat'), 'sender');
            $setup->getConnection()->dropColumn($setup->getTable('customerchat'), 'receiver');
            $setup->getConnection()->addColumn(
                $setup->getTable('customerchat'),
                'message',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => '2300',
                    'comment' => 'message'
                ]
            );
        }

        $setup->endSetup();
    
    }
}
