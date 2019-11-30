<?php

namespace Cibler\Shop\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface {
	
	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
		$installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('wio_cibler');

        if ($installer->getConnection()->isTableExists($tableName) != true) {
        	$query = $installer->getConnection()
	        	->newTable($tableName)
	                ->addColumn(
	                    'id_cibler',
	                    Table::TYPE_INTEGER,
	                    11,
	                    [
	                        'identity' => true,
	                        'unsigned' => true,
	                        'nullable' => false,
	                        'primary' => true
	                    ],
	                    'id cibler'
	                )
	                ->addColumn(
	                    'cookie',
	                    Table::TYPE_TEXT,
	                    255,
	                    ['nullable' => false, 'default' => ''],
	                    'Cookie'
	                )
					
					->addColumn(
	                    'id_cart',
	                    Table::TYPE_SMALLINT,
	                    255,
	                    ['nullable' => false, 'default' => '0'],
	                    'id_cart'
	                )
	               
	                ->setComment('Cibler cooke Id')
	                ->setOption('type', 'InnoDB')
	                ->setOption('charset', 'utf8');
	            $installer->getConnection()->createTable($query);
        }

        $installer->endSetup();
	}
}
