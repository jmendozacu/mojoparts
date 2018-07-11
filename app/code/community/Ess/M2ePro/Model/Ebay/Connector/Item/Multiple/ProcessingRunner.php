<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Connector_Item_Multiple_ProcessingRunner
    extends Ess_M2ePro_Model_Connector_Command_Pending_Processing_Runner_Single
{
    /** @var Ess_M2ePro_Model_Listing_Product[] $listingsProducts */
    private $listingsProducts = array();

    // ########################################

    public function processSuccess()
    {
        // all listings products can be removed during processing action
        if (count($this->getListingsProducts()) <= 0) {
            return true;
        }

        return parent::processSuccess();
    }

    public function processExpired()
    {
        // all listings products can be removed during processing action
        if (count($this->getListingsProducts()) <= 0) {
            return;
        }

        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        // all listings products can be removed during processing action
        if (count($this->getListingsProducts()) <= 0) {
            $this->getProcessingObject()->deleteInstance();
            return;
        }

        parent::complete();
    }

    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var Ess_M2ePro_Model_Ebay_Processing_Action $processingAction */
        $processingAction = Mage::getModel('M2ePro/Ebay_Processing_Action');
        $processingAction->setData(array(
            'processing_id'   => $this->getProcessingObject()->getId(),
            'account_id'      => $params['account_id'],
            'marketplace_id'  => $params['marketplace_id'],
            'type'            => $this->getProcessingActionType(),
            'request_timeout' => $params['request_timeout'],
        ));

        $processingAction->save();

        foreach ($params['request_data']['items'] as $listingProductId => $productData) {
            /** @var Ess_M2ePro_Model_Ebay_Processing_Action_Item $processingActionItem */
            $processingActionItem = Mage::getModel('M2ePro/Ebay_Processing_Action_Item');
            $processingActionItem->setData(array(
                'action_id'  => $processingAction->getId(),
                'related_id' => $listingProductId,
                'input_data' => Mage::helper('M2ePro')->jsonEncode($productData),
            ));

            $processingActionItem->save();
        }
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $alreadyLockedListings = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            $listingProduct->addProcessingLock(NULL, $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock('in_action', $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock(
                $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
            );

            if (isset($alreadyLockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());

            $alreadyLockedListings[$listingProduct->getListingId()] = true;
        }
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        $alreadyUnlockedListings = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            $listingProduct->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks(
                $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
            );

            if (isset($alreadyUnlockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());

            $alreadyUnlockedListings[$listingProduct->getListingId()] = true;
        }
    }

    // ########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case Ess_M2ePro_Model_Listing_Product::ACTION_STOP:
                return Ess_M2ePro_Model_Ebay_Processing_Action::TYPE_LISTING_PRODUCT_STOP;

            default:
                throw new Ess_M2ePro_Model_Exception_Logic('Unknown action type.');
        }
    }

    protected function getListingsProducts()
    {
        if (!empty($this->listingsProducts)) {
            return $this->listingsProducts;
        }

        $params = $this->getParams();

        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Collection $collection */
        $collection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Listing_Product');
        $collection->addFieldToFilter('id', array('in' => $params['listing_product_ids']));

        return $this->listingsProducts = $collection->getItems();
    }

    // ########################################
}