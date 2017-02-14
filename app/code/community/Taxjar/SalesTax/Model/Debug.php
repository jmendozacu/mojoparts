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
 * TaxJar Debug UI
 * Get diagnostic info when enabled
 */
class Taxjar_SalesTax_Model_Debug
{
    /**
     * Display debug information
     *
     * @param void
     * @return string
     */
    public function getCommentText()
    {
        $debug = Mage::getStoreConfig('tax/taxjar/debug');

        if ($debug) {
            return "<p class='note'><span>If enabled, does not alter your tax rates or database and instead prints debug messages for use with TaxJar support.</span></p><br/>" . $this->_getDebugHtmlString();
        } else {
            return "<p class='note'><span>If enabled, does not alter your tax rates or database and instead prints debug messages for use with TaxJar support.</span></p>";
        }
    }

    /**
     * Gather debug information
     *
     * @param void
     * @return string
     */
    private function _getDebugHtmlString()
    {
        $states         = unserialize(Mage::getStoreConfig('tax/taxjar/states'));
        $apiUser        = Mage::getModel('api/user');
        $existingUserId = $apiUser->load('taxjar', 'username')->getUserId();
        $pluginVersion  = '2.2.1';
        $phpMemory      = @ini_get('memory_limit');
        $phpVersion     = @phpversion();
        $magentoVersion = Mage::getVersion();
        $lastUpdated    = Mage::getStoreConfig('tax/taxjar/last_update');
        
        if (!empty($states)) {
            $states = implode(',', $states);
        }

        return "<ul> <li><strong>Additional States:</strong> ". $states ."</li> <li><strong>API User ID:</strong> ". $existingUserId ."</li><li><strong>Memory:</strong> ". $phpMemory ."</li> <li><strong>TaxJar Version:</strong> ". $pluginVersion ."</li> <li><strong>PHP Version</strong> ". $phpVersion ."</li> <li><strong>Magento Version:</strong> ". $magentoVersion ."</li> <li><strong>Last Updated:</strong> ". $lastUpdated ."</li> </ul><br/><p><small><strong>Include the above information when emailing TaxJar support at support@taxjar.com</strong><small></p>";
    }
}
