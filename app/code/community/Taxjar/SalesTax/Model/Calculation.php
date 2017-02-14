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
 * Calculation Model
 * TaxJar Zip+4 Rate Calculation Support for US
 */
class Taxjar_SalesTax_Model_Calculation extends Mage_Tax_Model_Resource_Calculation
{
    /**
     * Returns tax rates for request and when US only uses five digit zip code lookups
     *
     * @param Varien_Object $request
     * @return array
     */
    protected function _getRates($request)
    {
        $countryId = $request->getCountryId();
        $currentPostcode = $request->getPostcode();

        if ($countryId == 'US') {
            // Trim whitespace
            $newPostcode = preg_replace('/\s+/', '', $request->getPostcode());
            // Snatch only the first five characters
            $newPostcode = substr($newPostcode, 0, 5);
            // Replace the request's zip code with one that now has 5 digits
            $request->setPostcode($newPostcode);
            // Find rates by the new 5-digit zip
            $rates = parent::_getRates($request);
            // Reset the request's postcode to what it was
            $request->setPostcode($currentPostcode);
        } else {
            // Non-US should just work normally
            $rates = parent::_getRates($request);
        }

        return $rates;
    }
}
