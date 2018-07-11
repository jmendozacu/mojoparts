<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Connector_Item_Stop_MultipleRequester
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Multiple_Requester
{
    //########################################

    public function getMaxProductsCount()
    {
        return 10;
    }

    //########################################

    protected function getCommand()
    {
        return array('item','update','ends');
    }

    protected function getActionType()
    {
        return Ess_M2ePro_Model_Listing_Product::ACTION_STOP;
    }

    protected function getLogsAction()
    {
        if (!empty($this->params['remove'])) {
            return Ess_M2ePro_Model_Listing_Log::ACTION_STOP_AND_REMOVE_PRODUCT;
        }

        return Ess_M2ePro_Model_Listing_Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
    }

    //########################################

    protected function getRequestData()
    {
        $requestData = parent::getRequestData();

        $requestData['items'] = $requestData['products'];
        unset($requestData['products']);

        return $requestData;
    }

    //########################################
}