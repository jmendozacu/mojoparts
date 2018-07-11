<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Adminhtml_Amazon_ConfigurationController
    extends Ess_M2ePro_Controller_Adminhtml_Amazon_MainController
{
    //########################################

    protected function _initAction()
    {
        $this->loadLayout()
            ->_title(Mage::helper('M2ePro')->__('Configuration'))
            ->_title(Mage::helper('M2ePro')->__('Global'));

        $this->setPageHelpLink(NULL, NULL, "x/ioIVAQ");

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro_amazon/configuration');
    }

    //########################################

    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock(
                    'M2ePro/adminhtml_amazon_configuration', '',
                    array('active_tab' => Ess_M2ePro_Block_Adminhtml_Amazon_Configuration_Tabs::TAB_ID_GLOBAL)
                )
            )->renderLayout();
    }

    //########################################

    public function saveAction()
    {
        $businessMode = $this->getRequest()->getParam('business_mode');

        Mage::helper('M2ePro/Module')->getConfig()->setGroupValue(
            '/amazon/business/', 'mode',
            $businessMode
        );

        $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Settings was successfully saved.'));
        $this->_redirectUrl($this->_getRefererUrl());
    }

    //########################################
}