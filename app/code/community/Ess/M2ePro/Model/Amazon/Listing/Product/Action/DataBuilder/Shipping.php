<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Amazon_Listing_Product_Action_DataBuilder_Shipping
    extends Ess_M2ePro_Model_Amazon_Listing_Product_Action_DataBuilder_Abstract
{
    //########################################

    public function getData()
    {
        $data = array();

        if (!$this->getAmazonListingProduct()->isExistShippingTemplate()) {
            return $data;
        }

        $shippingTemplateSource = $this->getAmazonListingProduct()->getShippingTemplateSource();

        $data['shipping_data']['template_name'] = $shippingTemplateSource->getTemplateName();

        return $data;
    }

    //########################################
}