<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Model_Synchronization_Templates_Synchronization_Inspector
{
    //########################################

    abstract public function isMeetListRequirements(
        Ess_M2ePro_Model_Listing_Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    abstract public function isMeetRelistRequirements(
        Ess_M2ePro_Model_Listing_Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    abstract public function isMeetStopRequirements(
        Ess_M2ePro_Model_Listing_Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    // ---------------------------------------

    abstract public function isMeetReviseQtyRequirements(
        Ess_M2ePro_Model_Listing_Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    abstract public function isMeetRevisePriceRequirements(
        Ess_M2ePro_Model_Listing_Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    //########################################

    protected function isTriedToList(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $listingProductAdditionalData = $listingProduct->getAdditionalData();
        if (empty($listingProductAdditionalData['last_list_attempt_date'])) {
            return false;
        }

        $lastListAttemptDate = new DateTime(
            $listingProductAdditionalData['last_list_attempt_date'], new DateTimeZone('UTC')
        );

        $minDate = new DateTime('now', new DateTimeZone('UTC'));
        $minDate->modify('- 3 days');

        if ((int)$lastListAttemptDate->format('U') < (int)$minDate->format('U')) {
            return false;
        }

        return true;
    }

    protected function isChangeInitiatorOnlyInspector(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $changeInitiators = $listingProduct->getData('change_initiators');

        if (empty($changeInitiators)) {
            return false;
        }

        $changeInitiators = (array)explode(',', $changeInitiators);
        $changeInitiators = array_unique($changeInitiators);

        if (count($changeInitiators) != 1) {
            return false;
        }

        if ((int)reset($changeInitiators) != Ess_M2ePro_Model_ProductChange::INITIATOR_INSPECTOR) {
            return false;
        }

        return true;
    }

    //########################################
}