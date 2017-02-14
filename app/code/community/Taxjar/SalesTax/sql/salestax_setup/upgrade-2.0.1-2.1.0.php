<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

try {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('taxjar/tax_nexus'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true
            ), 'ID')
        ->addColumn('api_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            ), 'API ID')
        ->addColumn('street', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            ), 'Street')
        ->addColumn('city', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            ), 'City')
        ->addColumn('country_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            ), 'Country Id')
        ->addColumn('region', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            ), 'Region')
        ->addColumn('region_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            ), 'Region Id')
        ->addColumn('region_code', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            ), 'Region Code')
        ->addColumn('postcode', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            ), 'Postcode')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            ), 'Creation Time')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            ), 'Update Time')
        ->addIndex($installer->getIdxName('taxjar/tax_nexus', array('country_id')),
            array('country_id'))
        ->addIndex($installer->getIdxName('taxjar/tax_nexus', array('region_id')),
            array('region_id'))
        ->addIndex($installer->getIdxName('taxjar/tax_nexus', array('region_code')),
            array('region_code'))
        ->setComment('TaxJar Nexus Address');
        
    $installer->getConnection()->createTable($table);

} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();