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

class Taxjar_SalesTax_Model_Observer_ConfigChanged
{
    public function execute(Varien_Event_Observer $observer)
    {
        $this->_updateSmartcalcs();
        $this->_updateBackupRates();
    }
    
    private function _updateSmartcalcs()
    {
        $enabled = Mage::getStoreConfig('tax/taxjar/enabled'); 
        $prevEnabled = Mage::app()->getCache()->load('taxjar_salestax_config_enabled');

        if (isset($prevEnabled)) {
            if($prevEnabled != $enabled && $enabled == 1) {
                Mage::dispatchEvent('taxjar_salestax_import_data');
            }
        }
    }
    
    private function _updateBackupRates()
    {
        $enabled = Mage::getStoreConfig('tax/taxjar/backup'); 
        $prevEnabled = Mage::app()->getCache()->load('taxjar_salestax_config_backup');

        if (isset($prevEnabled)) {
            if($prevEnabled != $enabled) {
                Mage::dispatchEvent('taxjar_salestax_import_data');
                Mage::dispatchEvent('taxjar_salestax_import_rates');
            }
        }
    }
}
