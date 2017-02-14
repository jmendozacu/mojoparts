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

/**
 * Rate Model
 * Create new tax rates when importing rates
 */
class Taxjar_SalesTax_Model_Import_Rate
{
    private $cache;

    public function __construct()
    {
        $this->cache = Mage::getSingleton('core/cache');
    }

    /**
     * Attempt to create a new rate from JSON data
     *
     * @param string $rateJson
     * @return array
     */
    public function create($rateJson)
    {
        try {
            $zip        = $rateJson['zip'];
            $regionCode = $rateJson['state'];
            $rate       = $rateJson['rate'];

            if (isset($rateJson['country'])) {
                $countryCode = $rateJson['country'];
            } else {
                $countryCode = 'US';
            }

            if ($this->cache->load('regionId') && $regionCode == $this->cache->load('regionCode') && $countryCode == $this->cache->load('countryCode')) {
                $regionId = $this->cache->load('regionId');
            } else {
                $regionId = Mage::getModel('directory/region')->loadByCode($regionCode, $countryCode)->getId();
                $this->cache->save($regionId, 'regionId');
                $this->cache->save($regionCode, 'regionCode');
                $this->cache->save($countryCode, 'countryCode');
            }

            $rateModel = Mage::getModel('tax/calculation_rate');
            $rateModel->setTaxCountryId($countryCode);
            $rateModel->setTaxRegionId($regionId);
            $rateModel->setTaxPostcode($zip);
            $rateModel->setCode($countryCode . '-' . $regionCode . '-' . $zip);
            $rateModel->setRate($rate);
            $rateModel->save();

            if ($rateJson['freight_taxable']) {
                $shippingRateId = $rateModel->getId();
            } else {
                $shippingRateId = 0;
            }

            return array($rateModel->getId(), $shippingRateId);
        } catch (Exception $e) {
            // Mage::getSingleton('core/session')->addNotice("There was an error encountered while loading rate with code " . $rateModel->getCode() . ". This is most likely due to duplicate codes and can be safely ignored if lots of other rates were loaded. If the error persists, email support@taxjar.com with a screenshot of any Magento errors displayed.");
            unset($rateModel);
            return;
        }
    }
  
    /**
     * Get existing TaxJar rates based on configuration states
     *
     * @param void
     * @return array
     */
    public function getExistingRates()
    {
        return Mage::getModel('tax/calculation_rate')
            ->getCollection()
            ->addFieldToFilter('tax_region_id', $this->getRegionFilter());
    }
    
    /**
     * Get existing TaxJar rule calculations based on the rate ID
     *
     * @param string $rateId
     * @return array
     */
    public function getCalculationsByRateId($rateId)
    {
        return Mage::getModel('tax/calculation')
            ->getCollection()
            ->addFieldToFilter('tax_calculation_rate_id', $rateId);
    }

    /**
     * Get region filter for existing configuration states
     *
     * @param void
     * @return array
     */
    private function getRegionFilter()
    {
        $states = unserialize(Mage::getStoreConfig('tax/taxjar/states'));
        $filter = array();

        foreach (array_unique($states) as $state) {
            $regionId = Mage::getModel('directory/region')->loadByCode($state, 'US')->getId();
            $filter[] = array('finset' => array($regionId));
        }

        return $filter;
    }
}
