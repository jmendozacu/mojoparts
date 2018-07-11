<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Amazon_Listing_Product_Action_Type_Delete_Request
    extends Ess_M2ePro_Model_Amazon_Listing_Product_Action_Type_Request
{
    //########################################

    /**
     * @return array
     */
    protected function getActionData()
    {
        return array(
            'sku' => $this->getAmazonListingProduct()->getSku()
        );
    }

    //########################################
}