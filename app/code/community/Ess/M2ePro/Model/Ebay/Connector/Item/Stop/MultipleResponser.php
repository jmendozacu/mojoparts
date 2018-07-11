<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Connector_Item_Stop_MultipleResponser
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Multiple_Responser
{
    //########################################

    protected function getSuccessfulMessage(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        return 'Item was successfully Stopped';
    }

    //########################################

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (empty($this->params['params']['remove'])) {
            return;
        }

        foreach ($this->listingsProducts as $listingProduct) {
            $listingProduct->setData('status', Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED);
            $listingProduct->deleteInstance();
        }
    }

    //########################################

    protected function processCompleted(Ess_M2ePro_Model_Listing_Product $listingProduct,
                                        array $data = array(), array $params = array())
    {
        if (!empty($data['already_stop'])) {

            $this->getResponseObject($listingProduct)->processSuccess($data, $params);

            // M2ePro_TRANSLATIONS
            // Item was already Stopped on eBay
            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item was already Stopped on eBay',
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $listingProduct, $message
            );

            return;
        }

        parent::processCompleted($listingProduct, $data, $params);
    }

    //########################################
}