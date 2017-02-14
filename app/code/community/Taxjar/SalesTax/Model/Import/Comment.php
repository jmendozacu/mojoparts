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
 * TaxJar Extension UI
 * Returns imported states and help info
 */
class Taxjar_SalesTax_Model_Import_Comment
{
    protected $_regionCode;
    
    /**
     * Display Nexus states loaded and API Key setting
     *
     * @param void
     * @return string
     */
    public function getCommentText()
    {
        $isEnabled = Mage::getStoreConfig('tax/taxjar/backup'); 
        $regionId = Mage::getStoreConfig('shipping/origin/region_id');
        $this->_regionCode = Mage::getModel('directory/region')->load($regionId)->getCode();

        if ($isEnabled) {
            return $this->buildEnabledHtml();
        } else {
            return $this->buildDisabledHtml();
        }
    }

    /**
     * Get the number of rates loaded
     *
     * @param array $states
     * @return array
     */
    private function getNumberOfRatesLoaded($states)
    {
        $rates = Mage::getModel('tax/calculation_rate');
        $stateRatesLoadedCount = 0;
        $ratesByState = array();

        foreach (array_unique($states) as $state) {
            $regionModel = Mage::getModel('directory/region')->loadByCode($state, 'US');
            $regionId = $regionModel->getId();
            $ratesByState[$state] = $rates->getCollection()->addFieldToFilter('tax_region_id', array('eq' => $regionId))->getSize();
        }

        $rateCalcs = array(
            'total_rates' => array_sum($ratesByState), 
            'rates_loaded' => Mage::getModel('taxjar/import_rate')->getExistingRates()->getSize(),
            'rates_by_state' => $ratesByState
        );

        return $rateCalcs;
    }

    /**
     * Get region name from region code
     *
     * @param string $regionCode
     * @return string
     */
    private function getStateName($regionCode)
    {
        $regionModel = Mage::getModel('directory/region')->loadByCode($regionCode, 'US');
        return $regionModel->getDefaultName();
    }

    /**
     * Build HTML for backup rates enabled
     *
     * @return string
     */
    private function buildEnabledHtml()
    {
        $states = unserialize(Mage::getStoreConfig('tax/taxjar/states'));
        $htmlString = "<p class='note'><span>Download zip-based rates from TaxJar as a fallback. TaxJar uses your shipping origin and nexus addresses to sync rates rach month.</span></p><br/>";
        
        if (!empty($states)) {
            $htmlString .= "<ul class='messages'>" . $this->buildStatesHtml($states) . "</ul>";
        }
        
        $htmlString .= $this->buildSyncHtml();

        return $htmlString;
    }

    /**
     * Build HTML for backup rates disabled
     *
     * @return string
     */
    private function buildDisabledHtml()
    {
        $htmlString = "<p class='note'><span>Download zip-based rates from TaxJar as a fallback. TaxJar uses your shipping origin and nexus addresses to sync rates rach month.</span></p><br/>";

        return $htmlString;
    }

    /**
     * Build HTML list of states
     *
     * @param string $states
     * @param string $regionCode
     * @return string
     */
    private function buildStatesHtml($states)
    {
        $states[] = $this->_regionCode;
        $statesHtml = '';
        $lastUpdate = Mage::getStoreConfig('tax/taxjar/last_update');

        sort($states);

        $taxRatesByState = $this->getNumberOfRatesLoaded($states);

        foreach (array_unique($states) as $state) {
            if (($stateName = $this->getStateName($state)) && !empty($stateName)) {
                if ($taxRatesByState['rates_by_state'][$state] == 1 && ($taxRatesByState['rates_loaded'] == $taxRatesByState['total_rates'])) {
                    $totalForState = 'Origin-based rates set';
                    $class = 'success';
                } elseif ($taxRatesByState['rates_by_state'][$state] == 0 && ($taxRatesByState['rates_loaded'] == $taxRatesByState['total_rates'])) {
                    $class = 'error';
                    $totalForState = '<a href="' . Mage::helper('adminhtml')->getUrl('adminhtml/tax_nexus/index') . '">Click here</a> and add a zip code for this state to load rates.';
                } else {
                    $class = 'success';
                    $totalForState = $taxRatesByState['rates_by_state'][$state] . ' rates';
                }

                $statesHtml .= '<li class="' . $class . '-msg"><ul><li style="line-height: 1.9em"><span style="font-size: 1.4em">' . $stateName . '</span>: ' . $totalForState . '</li></ul></li>'; 
            }
        }

        if ($taxRatesByState['rates_loaded'] != $taxRatesByState['total_rates']) {
            $matches = 'error';
        } else {
            $matches = 'success';
        }

        $statesHtml .= '<p class="' . $matches . '-msg" style="background: none !important;"><small>&nbsp;&nbsp;' . $taxRatesByState['total_rates'] . ' of ' . $taxRatesByState['rates_loaded'] . ' expected rates loaded.</small></p>';
        $statesHtml .= '<p class="' . $matches . '-msg" style="background: none !important;"><small>&nbsp;&nbsp;' . 'Last synced on ' . $lastUpdate . '</small></p><br/>';

        return $statesHtml;   
    }

    /**
     * Build HTML for sync button
     *
     * @return string
     */
    private function buildSyncHtml()
    {
        $syncUrl = Mage::helper('adminhtml')->getUrl('adminhtml/taxjar/sync_rates');
        $redirectUrl = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/tax');
        $syncHtml = '<p><button type="button" class="scalable" onclick="syncBackupRates()"><span>Sync Backup Rates</span></button></p><br/>';
        $syncHtml .= <<<EOT
        <script>
            function syncBackupRates() {
                new Ajax.Request('{$syncUrl}', {
                    method: 'get',
                    onCreate: function(request) {
                        varienLoaderHandler.handler.onCreate({options: {loaderArea: true}});
                    },
                    onComplete: function(data) { 
                        varienLoaderHandler.handler.onComplete();
                        window.location = '{$redirectUrl}';
                    }
                });
            }
        </script>
EOT;

        return $syncHtml;
    }
}
