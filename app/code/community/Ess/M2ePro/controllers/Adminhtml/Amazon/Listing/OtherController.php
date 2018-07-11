<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Adminhtml_Amazon_Listing_OtherController
    extends Ess_M2ePro_Controller_Adminhtml_Amazon_MainController
{
    //########################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_title(Mage::helper('M2ePro')->__('Manage Listings'))
             ->_title(Mage::helper('M2ePro')->__('3rd Party Listings'));

        $this->getLayout()->getBlock('head')
            ->addJs('M2ePro/Plugin/ProgressBar.js')
            ->addCss('M2ePro/css/Plugin/ProgressBar.css')
            ->addJs('M2ePro/Plugin/AreaWrapper.js')
            ->addCss('M2ePro/css/Plugin/AreaWrapper.css')

            ->addJs('M2ePro/GridHandler.js')
            ->addJs('M2ePro/Listing/Other/GridHandler.js')
            ->addJs('M2ePro/Amazon/Listing/Other/GridHandler.js')
            ->addJs('M2ePro/Amazon/Listing/AfnQtyHandler.js')
            ->addJs('M2ePro/Amazon/Listing/RepricingPriceHandler.js')
            ->addJs('M2ePro/Amazon/Listing/Other/GridHandler.js')

            ->addJs('M2ePro/ActionHandler.js')
            ->addJs('M2ePro/Listing/MovingHandler.js')
            ->addJs('M2ePro/Listing/Other/AutoMappingHandler.js')

            ->addJs('M2ePro/Listing/Other/MappingHandler.js')

            ->addJs('M2ePro/Listing/Other/RemovingHandler.js')
            ->addJs('M2ePro/Listing/Other/UnmappingHandler.js');

        $this->_initPopUp();

        $this->setPageHelpLink(NULL, NULL, "x/gogVAQ");

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro_amazon/listings');
    }

    //########################################

    public function indexAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('*/adminhtml_amazon_listing_other/index');
        }

        /** @var $block Ess_M2ePro_Block_Adminhtml_Amazon_Listing_Other */
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_other');
        $block->enableAmazonTab();

        $this->getResponse()->setBody($block->getAmazonTabHtml());
    }

    public function gridAction()
    {
        $response = $this->loadLayout()->getLayout()
                         ->createBlock('M2ePro/adminhtml_amazon_listing_other_view_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //########################################

    public function viewAction()
    {
        /** @var $block Ess_M2ePro_Block_Adminhtml_Amazon_Listing_Other_View */
        $block = $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_listing_other_view');
        $this->_initAction()->_addContent($block)->renderLayout();
    }

    //########################################

    public function removingAction()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (!$productIds) {
            return $this->getResponse()->setBody('0');
        }

        $productArray = explode(',', $productIds);

        if (empty($productArray)) {
            return $this->getResponse()->setBody('0');
        }

        foreach ($productArray as $productId) {
            /* @var $listingOther Ess_M2ePro_Model_Listing_Other */
            $listingOther = Mage::helper('M2ePro/Component')->getComponentObject(
                Ess_M2ePro_Helper_Component_Amazon::NICK, 'Listing_Other', $productId
            );

            if (!is_null($listingOther->getProductId())) {
                $listingOther->unmapProduct(Ess_M2ePro_Helper_Data::INITIATOR_EXTENSION);
            }

            $listingOther->deleteInstance();
        }

        return $this->getResponse()->setBody('1');
    }

    //########################################
}