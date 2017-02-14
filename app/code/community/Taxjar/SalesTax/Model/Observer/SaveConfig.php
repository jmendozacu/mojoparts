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

class Taxjar_SalesTax_Model_Observer_SaveConfig
{
    protected $_version = 'v2';
    protected $_storeZip;
    protected $_regionCode;

    public function execute($observer)
    {
        $apiKey = Mage::getStoreConfig('taxjar/config/apikey');
        $apiKey = preg_replace('/\s+/', '', $apiKey);

        if ($apiKey) {
            $client = Mage::getModel('taxjar/client');
            $configuration = Mage::getModel('taxjar/configuration');
            $regionId = Mage::getStoreConfig('shipping/origin/region_id');
            $this->_storeZip = trim(Mage::getStoreConfig('shipping/origin/postcode'));
            $this->_regionCode = Mage::getModel('directory/region')->load($regionId)->getCode();
            $validZip = preg_match("/(\d{5}-\d{4})|(\d{5})/", $this->_storeZip);
            $debug = Mage::getStoreConfig('taxjar/config/debug');

            if (isset($this->_regionCode)) {
                $configJson = $client->getResource($apiKey, $this->apiUrl('config'));
                $configJson = $configJson['configuration'];
            } else {
                Mage::throwException('Please check that you have set a Region/State in Shipping Settings.');
            }

            if ($debug) {
                Mage::getSingleton('core/session')->addNotice('Debug mode enabled. Tax rates have not been altered.');
                return;
            }

            if ($configJson['wait_for_rates'] > 0) {
                $dateUpdated = Mage::getStoreConfig('taxjar/config/last_update');
                Mage::getSingleton('core/session')->addNotice('Your last rate update was too recent. Please wait at least 5 minutes and try again.');
                return;
            }

            if ($validZip === 1 && isset($this->_storeZip) && $this->_storeZip !== '') {
                $ratesJson = $client->getResource($apiKey, $this->apiUrl('rates'));
            } else {
                Mage::throwException('Please check that your zip code is a valid US zip code in Shipping Settings.');
            }
            
            $categoriesJson = $client->getResource($apiKey, $this->apiUrl('categories'));

            $configuration->setTaxBasis($configJson);
            $configuration->setShippingTaxability($configJson);
            $configuration->setDisplaySettings();
            $configuration->setApiSettings($apiKey);

            Mage::getModel('core/config')->saveConfig('taxjar/config/categories', json_encode($categoriesJson['categories']));
            Mage::getModel('core/config')->saveConfig('taxjar/config/states', serialize(explode(',', $configJson['states'])));
            Mage::getModel('core/config')->saveConfig('taxjar/config/freight_taxable', $configJson['freight_taxable']);

            $this->purgeExisting();

            if (false !== file_put_contents($this->getTempFileName(), serialize($ratesJson))) {
                Mage::dispatchEvent('taxjar_salestax_import_rates');
            } else {
                // We need to be able to store the file...
                Mage::throwException('Could not write to your Magento temp directory. Please check permissions for '.Mage::getBaseDir('tmp').'.');
            }
        } else {
            Mage::getSingleton('core/session')->addNotice('TaxJar has been uninstalled. All tax rates have been removed.');
            Mage::getModel('core/config')->saveConfig('taxjar/smartcalcs/enabled', 0);
            $this->purgeExisting();
            $this->setLastUpdateDate(null);
        }

        // Clearing the cache to avoid UI elements not loading
        Mage::app()->getCacheInstance()->flush();
    }

    /**
     * Build URL string
     *
     * @param string $type
     * @return string
     */
    private function apiUrl($type)
    {
        $apiHost = 'https://api.taxjar.com/';
        $prefix = $apiHost . $this->_version . '/plugins/magento/';

        if ($type == 'config') {
            return $prefix . 'configuration/' . $this->_regionCode;
        } elseif ($type == 'rates') {
            return $prefix . 'rates/' . $this->_regionCode . '/' . $this->_storeZip;
        } elseif ($type == 'categories') {
            return $apiHost . $this->_version . '/categories';
        }
    }

    /**
     * Purge existing rule calculations and rates
     *
     * @param void
     * @return void
     */
    private function purgeExisting()
    {
        $rates = Mage::getModel('taxjar/import_rate')->getExistingRates()->load();

        foreach ($rates as $rate) {
            $calculations = Mage::getModel('taxjar/import_rate')->getCalculationsByRateId($rate->getId())->load();
            
            try {
                foreach ($calculations as $calculation) {
                    $calculation->delete();
                }
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError('There was an error deleting from Magento model tax/calculation');
            }

            try {
                $rate->delete();
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError('There was an error deleting from Magento model tax/calculation_rate');
            }
        }
    }

    /**
     * Set the last updated date
     *
     * @param string $date
     * @return void
     */
    private function setLastUpdateDate($date)
    {
        Mage::getModel('core/config')->saveConfig('taxjar/config/last_update', $date);
    }

    /**
     * Set the filename
     *
     * @param void
     * @return string
     */
    private function getTempFileName()
    {
        return Mage::getBaseDir('tmp') . DS . 'tj_tmp.dat';
    }
}
