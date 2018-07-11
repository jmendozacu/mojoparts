<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Amazon_Repricing_Updating extends Ess_M2ePro_Model_Amazon_Repricing_Abstract
{
    //########################################

    /**
     * @param Ess_M2ePro_Model_Amazon_Listing_Product_Repricing[] $listingsProductsRepricing
     * @return bool|array
     */
    public function process(array $listingsProductsRepricing)
    {
        $changesData = array();
        $updatedSkus = array();

        foreach ($listingsProductsRepricing as $listingProductRepricing) {

            $changeData = $this->getChangeData($listingProductRepricing);
            if ($changeData && !in_array($changeData['sku'], $updatedSkus, true)) {
                $changesData[] = $changeData;
                $updatedSkus[] = $changeData['sku'];
            }
        }

        if (!$this->isDataUpdateNeed($changesData)) {
            return false;
        }

        if (!$this->sendData($changesData)) {
            return false;
        }

        return $updatedSkus;
    }

    //########################################

    private function getChangeData(Ess_M2ePro_Model_Amazon_Listing_Product_Repricing $listingProductRepricing)
    {
        $isDisabled = $listingProductRepricing->isDisabled();

        if ($isDisabled && !$listingProductRepricing->isOnlineManaged()) {
            return false;
        }

        $regularPrice = $listingProductRepricing->getRegularPrice();
        $minPrice     = $listingProductRepricing->getMinPrice();
        $maxPrice     = $listingProductRepricing->getMaxPrice();

        if ($regularPrice == $listingProductRepricing->getOnlineRegularPrice() &&
            $minPrice     == $listingProductRepricing->getOnlineMinPrice() &&
            $maxPrice     == $listingProductRepricing->getOnlineMaxPrice() &&
            $isDisabled   == $listingProductRepricing->isOnlineDisabled()
        ) {
            return false;
        }

        return array(
            'sku' => $listingProductRepricing->getAmazonListingProduct()->getSku(),
            'regular_product_price'   => $regularPrice,
            'minimal_product_price'   => $minPrice,
            'maximal_product_price'   => $maxPrice,
            'is_calculation_disabled' => $isDisabled,
        );
    }

    private function sendData(array $changesData)
    {
        try {
            $result = $this->getHelper()->sendRequest(
                Ess_M2ePro_Helper_Component_Amazon_Repricing::COMMAND_SYNCHRONIZE_USER_CHANGES,
                array(
                    'account_token' => $this->getAmazonAccountRepricing()->getToken(),
                    'offers'        => Mage::helper('M2ePro')->jsonEncode($changesData),
                )
            );
        } catch (Exception $exception) {

            $this->getSynchronizationLog()->addMessage(
                Mage::helper('M2ePro')->__($exception->getMessage()),
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH
            );

            Mage::helper('M2ePro/Module_Exception')->process($exception, false);
            return false;
        }

        $this->processErrorMessages($result['response']);
        return true;
    }

    //########################################

    private function isDataUpdateNeed(array $changesData)
    {
        foreach ($changesData as $changesDatum) {
            if ($changesDatum['is_calculation_disabled'] !== NULL ||
                $changesDatum['minimal_product_price'] !== NULL ||
                $changesDatum['maximal_product_price'] !== NULL ||
                $changesDatum['regular_product_price'] !== NULL) {
                return true;
            }
        }

        return false;
    }

    //########################################
}