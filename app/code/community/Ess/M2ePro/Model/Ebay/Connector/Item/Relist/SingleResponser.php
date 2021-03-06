<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Connector_Item_Relist_SingleResponser
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Single_Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was successfully Relisted';
    }

    //########################################

    protected function processCompleted(array $data = array(), array $params = array())
    {
        if (!empty($data['already_active'])) {
            $this->getResponseObject()->processAlreadyActive($data, $params);

            // M2ePro_TRANSLATIONS
            // Item was already started on eBay
            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item was already started on eBay',
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct, $message
            );

            return;
        }

        parent::processCompleted($data, $params);
    }

    public function eventAfterExecuting()
    {
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        if (!$this->listingProduct->getAccount()->getChildObject()->isModeSandbox() &&
            $this->isEbayApplicationErrorAppeared($responseMessages)) {

            $this->markAsPotentialDuplicate();

            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'An error occurred while Listing the Item. The Item has been blocked.
                 The next M2E Pro Synchronization will resolve the problem.',
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }

        if ($this->isConditionErrorAppeared($responseMessages)) {

            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                Mage::helper('M2ePro')->__(
                    'M2E Pro was not able to send Condition on eBay. Please try to perform the Relist Action once more.'
                ),
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $additionalData = $this->listingProduct->getAdditionalData();
            $additionalData['is_need_relist_condition'] = true;

            $this->listingProduct
                ->setSettings('additional_data', $additionalData)
                ->save();
        }

        if ($this->getStatusChanger() == Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_SYNCH &&
            $this->isItemCanNotBeAccessed($responseMessages)) {

            $itemId = null;
            if (isset($this->params['product']['request']['item_id'])) {
                $itemId = $this->params['product']['request']['item_id'];
            }

            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                Mage::helper('M2ePro')->__(
                    "This Item {$itemId} cannot be accessed on eBay, so the Relist action cannot be executed for it.
                    M2E Pro has automatically detected this issue and run the List action to solve it basing
                    on the List Rule of the Synchronization Policy."
                ),
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
            $this->processAdditionalAction(
                Ess_M2ePro_Model_Listing_Product::ACTION_LIST, $configurator,
                array('skip_check_the_same_product_already_listed_ids' => array($this->listingProduct->getId()))
            );
        }

        if ($this->getStatusChanger() == Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_SYNCH &&
            ($this->getConfigurator()->isPartialMode()) &&
            $this->isNewRequiredSpecificNeeded($responseMessages)) {

            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'It has been detected that the Category you are using is going to require the Product Identifiers
                to be specified (UPC, EAN, ISBN, etc.). The Relist Action will be automatically performed
                to send the value(s) of the required Identifier(s) based on the settings
                provided in eBay Catalog Identifiers section of the Description Policy.',
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
            $this->processAdditionalAction($this->getActionType(), $configurator);
        }

        $additionalData = $this->listingProduct->getAdditionalData();

        if ($this->isVariationErrorAppeared($responseMessages) &&
            $this->getRequestDataObject()->hasVariations() &&
            !isset($additionalData['is_variation_mpn_filled'])
        ) {
            $this->tryToResolveVariationMpnErrors();
        }

        if ($message = $this->isDuplicateErrorByUUIDAppeared($responseMessages)) {
            $this->processDuplicateByUUID($message);
        }

        if ($message = $this->isDuplicateErrorByEbayEngineAppeared($responseMessages)) {
            $this->processDuplicateByEbayEngine($message);
        }

        parent::eventAfterExecuting();
    }

    //########################################
}