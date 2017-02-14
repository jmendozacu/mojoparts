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

class Taxjar_SalesTax_Model_Observer_ImportData
{
    protected $_apiKey;
    protected $_client;
    
    public function execute(Varien_Event_Observer $observer)
    {
        $this->_apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));
        $storeRegionCode = Mage::getModel('directory/region')->load(Mage::getStoreConfig('shipping/origin/region_id'))->getCode();
        
        if ($this->_apiKey) {
            $this->_client = Mage::getModel('taxjar/client');
            
            if (isset($storeRegionCode)) {
                $this->_setConfiguration();
            } else {
                Mage::throwException('Please check that you have set a Region/State in Shipping Settings.');
            }
        }
    }
    
    /**
     * Get TaxJar product categories
     *
     * @param void
     * @return string
     */
    private function _getCategoryJson()
    {
        $categoryJson = $this->_client->getResource($this->_apiKey, 'categories');
        return $categoryJson['categories'];
    }
    
    /**
     * Get TaxJar user account configuration
     *
     * @param void
     * @return string
     */
    private function _getConfigJson()
    {
        $configJson = $this->_client->getResource($this->_apiKey, 'config');
        return $configJson['configuration'];
    }
    
    /**
     * Set TaxJar config
     *
     * @param array $configJson
     * @return void
     */
    private function _setConfiguration()
    {
        $configuration = Mage::getModel('taxjar/configuration');
        $configJson = $this->_getConfigJson();
        $categoryJson = $this->_getCategoryJson();

        $configuration->setTaxBasis($configJson);
        $configuration->setShippingTaxability($configJson);
        $configuration->setDisplaySettings();
        $configuration->setApiSettings($this->_apiKey);

        Mage::getConfig()->saveConfig('tax/taxjar/categories', json_encode($categoryJson));
        Mage::getConfig()->saveConfig('tax/taxjar/states', serialize(explode(',', $configJson['states'])));
        Mage::getConfig()->saveConfig('tax/taxjar/freight_taxable', $configJson['freight_taxable']);
        Mage::getConfig()->reinit();
    }
}