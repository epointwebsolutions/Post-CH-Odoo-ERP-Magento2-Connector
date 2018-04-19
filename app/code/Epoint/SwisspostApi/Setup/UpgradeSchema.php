<?php

namespace Epoint\SwisspostApi\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<=')) {

            $epointTable = $setup->getTable('epoint_swisspost_entities');

            $setup->getConnection()->addColumn(
                $epointTable,
                'automatic_export',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => 1,
                    'comment' => 'Automatic Export'
                ]
            );
            $setup->getConnection()->addColumn(
                $epointTable,
                'export_tryouts',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Export Tryouts'
                ]
            );
        }

        $setup->endSetup();
    }
}