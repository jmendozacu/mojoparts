<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Connector_Item_Verify_SingleResponser
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Single_Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return NULL;
    }

    //########################################

    protected function processResponseMessages()
    {
        $this->getLogger()->setStoreMode(true);
        parent::processResponseMessages();
    }

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getData();

        if (isset($responseData['ebay_item_fees']) && is_array($responseData['ebay_item_fees'])) {
            $this->preparedResponseData = $responseData['ebay_item_fees'];
        }
    }

    protected function processResponseData() {}

    //########################################
}