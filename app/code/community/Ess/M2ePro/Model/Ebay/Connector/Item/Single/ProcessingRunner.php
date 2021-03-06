<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Connector_Item_Single_ProcessingRunner
    extends Ess_M2ePro_Model_Connector_Command_Pending_Processing_Runner_Single
{
    /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */
    private $listingProduct = array();

    // ########################################

    public function processSuccess()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            return true;
        }

        return parent::processSuccess();
    }

    public function processExpired()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
            return;
        }

        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        // listing product can be removed during processing action
        if (is_null($this->getListingProduct()->getId())) {
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

        /** @var Ess_M2ePro_Model_Ebay_Processing_Action_Item $processingActionItem */
        $processingActionItem = Mage::getModel('M2ePro/Ebay_Processing_Action_Item');
        $processingActionItem->setData(array(
            'action_id'  => $processingAction->getId(),
            'related_id' => $params['listing_product_id'],
            'input_data' => Mage::helper('M2ePro')->jsonEncode($params['request_data']),
        ));

        $processingActionItem->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        $this->getListingProduct()->addProcessingLock(NULL, $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->addProcessingLock(
            $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
        );

        $this->getListingProduct()->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        $this->getListingProduct()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
        $this->getListingProduct()->deleteProcessingLocks(
            $params['lock_identifier'].'_action', $this->getProcessingObject()->getId()
        );

        $this->getListingProduct()->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
    }

    // ########################################

    protected function getProcessingActionType()
    {
        $params = $this->getParams();

        switch ($params['action_type']) {
            case Ess_M2ePro_Model_Listing_Product::ACTION_LIST:
                return Ess_M2ePro_Model_Ebay_Processing_Action::TYPE_LISTING_PRODUCT_LIST;

            case Ess_M2ePro_Model_Listing_Product::ACTION_REVISE:
                return Ess_M2ePro_Model_Ebay_Processing_Action::TYPE_LISTING_PRODUCT_REVISE;

            case Ess_M2ePro_Model_Listing_Product::ACTION_RELIST:
                return Ess_M2ePro_Model_Ebay_Processing_Action::TYPE_LISTING_PRODUCT_RELIST;

            default:
                throw new Ess_M2ePro_Model_Exception_Logic('Unknown action type.');
        }
    }

    protected function getListingProduct()
    {
        if (!empty($this->listingProduct)) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Collection $collection */
        $collection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Listing_Product');
        $collection->addFieldToFilter('id', $params['listing_product_id']);

        return $this->listingProduct = $collection->getFirstItem();
    }

    // ########################################
}