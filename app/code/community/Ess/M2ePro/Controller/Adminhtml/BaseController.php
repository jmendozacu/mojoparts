<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Controller_Adminhtml_BaseController
    extends Mage_Adminhtml_Controller_Action
{
    protected $generalBlockWasAppended = false;

    protected $pageHelpLink = NULL;

    protected $isUnAuthorized = false;

    //########################################

    public function indexAction()
    {
        $this->_redirect(Mage::helper('M2ePro/Module_Support')->getPageRoute());
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    //########################################

    protected function setPageHelpLink($component = NULL, $article = NULL, $tinyLink = NULL)
    {
        $this->pageHelpLink = Mage::helper('M2ePro/Module_Support')->getDocumentationUrl(
            $component, $article, $tinyLink
        );
    }

    protected function getPageHelpLink()
    {
        if (is_null($this->pageHelpLink)) {
            return Mage::helper('M2ePro/Module_Support')->getDocumentationUrl();
        }

        return $this->pageHelpLink;
    }

    //########################################

    final public function preDispatch()
    {
        parent::preDispatch();

        /**
         * Custom implementation of APPSEC-1034 (SUPEE-6788) [see additional information below].
         * M2E Pro prevents redirect to Magento Admin Panel login page.
         *
         * This PHP class is the base PHP class of all M2E Pro controllers.
         * Thus, it protects any action of any controller of M2E Pro extension.
         *
         * The code below is the logical extension of the method \Ess_M2ePro_Controller_Router::addModule.
         */
        // -----------------------------------------------------------------
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {

            $this->isUnAuthorized = true;

            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
            $this->setFlag('', self::FLAG_NO_PRE_DISPATCH, true);

            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->getResponse()->setBody(json_encode(array(
                    'ajaxExpired'  => 1,
                    'ajaxRedirect' => Mage::getBaseUrl()
                )));
            }

            if (Mage::helper('M2ePro/Module')->isProductionEnvironment()) {
                return $this->getResponse()->setRedirect(Mage::getBaseUrl());
            }
        }
        // -----------------------------------------------------------------

        Mage::helper('M2ePro/Module_Exception')->setFatalErrorHandler();

        // flag that controller is loaded
        if (is_null(Mage::helper('M2ePro/Data_Global')->getValue('is_base_controller_loaded'))) {
            Mage::helper('M2ePro/Data_Global')->setValue('is_base_controller_loaded',true);
        }

        $this->__preDispatch();

        return $this;
    }

    final public function dispatch($action)
    {
        try {

            parent::dispatch($action);

        } catch (Exception $exception) {

            if ($this->isUnAuthorized) {
                throw $exception;
            }

            if ($this->getRequest()->getControllerName() ==
                Mage::helper('M2ePro/Module_Support')->getPageControllerName()) {
                return $this->getResponse()->setBody($exception->getMessage());
            } else {

                if (Mage::helper('M2ePro/Module')->isDevelopmentEnvironment()) {
                    throw $exception;
                } else {

                    Mage::helper('M2ePro/Module_Exception')->process($exception);

                    if (($this->getRequest()->isGet() || $this->getRequest()->isPost()) &&
                        !$this->getRequest()->isXmlHttpRequest()) {

                        $this->_getSession()->addError(
                            Mage::helper('M2ePro/Module_Exception')->getUserMessage($exception)
                        );

                        $params = array(
                            'error' => 'true'
                        );

                        if (!is_null(Mage::helper('M2ePro/View')->getCurrentView())) {
                            $params['referrer'] = Mage::helper('M2ePro/View')->getCurrentView();
                        }

                        $this->_redirect(Mage::helper('M2ePro/Module_Support')->getPageRoute(), $params);
                    } else {
                        return $this->getResponse()->setBody($exception->getMessage());
                    }
                }
            }
        }
    }

    final public function postDispatch()
    {
        parent::postDispatch();

        if ($this->isUnAuthorized) {
            return;
        }

        $this->__postDispatch();
    }

    //########################################

    protected function __preDispatch() {}

    protected function __postDispatch()
    {
        // Removes garbage from the response's body
        ob_get_clean();
    }

    //########################################

    public function loadLayout($ids=null, $generateBlocks=true, $generateXml=true)
    {
        $customLayout = Ess_M2ePro_Helper_View::LAYOUT_NICK;
        is_array($ids) ? $ids[] = $customLayout : $ids = array('default',$customLayout);
        return parent::loadLayout($ids, $generateBlocks, $generateXml);
    }

    // ---------------------------------------

    protected function _addLeft(Mage_Core_Block_Abstract $block)
    {
        $this->appendGeneralBlock($this->getLayout()->getBlock('left'));
        $this->beforeAddLeftEvent();
        return $this->addLeft($block);
    }

    protected function _addContent(Mage_Core_Block_Abstract $block)
    {
        $this->appendGeneralBlock($this->getLayout()->getBlock('content'));
        $this->beforeAddContentEvent();
        return $this->addContent($block);
    }

    // ---------------------------------------

    protected function beforeAddLeftEvent() {}

    protected function beforeAddContentEvent() {}

    //########################################

    public function getSession()
    {
        return $this->_getSession();
    }

    protected function getRequestIds()
    {
        $id = $this->getRequest()->getParam('id');
        $ids = $this->getRequest()->getParam('ids');

        if (is_null($id) && is_null($ids)) {
            return array();
        }

        $requestIds = array();

        if (!is_null($ids)) {
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $requestIds = (array)$ids;
        }

        if (!is_null($id)) {
            $requestIds[] = $id;
        }

        return array_filter($requestIds);
    }

    // ---------------------------------------

    protected function _initPopUp()
    {
        $themeFileName = 'prototype/windows/themes/magento.css';
        $themeLibFileName = 'lib/'.$themeFileName;
        $themeFileFound = false;
        $skinBaseDir = Mage::getDesign()->getSkinBaseDir(
            array(
                '_package' => Mage_Core_Model_Design_Package::DEFAULT_PACKAGE,
                '_theme' => Mage_Core_Model_Design_Package::DEFAULT_THEME,
            )
        );

        if (!$themeFileFound && is_file($skinBaseDir .'/'.$themeLibFileName)) {
            $themeFileFound = true;
            $this->getLayout()->getBlock('head')->addCss($themeLibFileName);
        }

        if (!$themeFileFound && is_file(Mage::getBaseDir().'/js/'.$themeFileName)) {
            $themeFileFound = true;
            $this->getLayout()->getBlock('head')->addItem('js_css', $themeFileName);
        }

        if (!$themeFileFound) {
            $this->getLayout()->getBlock('head')->addCss($themeLibFileName);
            $this->getLayout()->getBlock('head')->addItem('js_css', $themeFileName);
        }

        $this->getLayout()->getBlock('head')
            ->addJs('prototype/window.js')
            ->addItem('js_css', 'prototype/windows/themes/default.css');

        return $this;
    }

    //########################################

    protected function appendGeneralBlock(Mage_Core_Block_Abstract $block)
    {
        if ($this->generalBlockWasAppended) {
            return;
        }

        $generalBlockPath = Ess_M2ePro_Helper_View::GENERAL_BLOCK_PATH;
        $blockGeneral = $this->getLayout()->createBlock($generalBlockPath);
        $blockGeneral->setData('page_help_link', $this->getPageHelpLink());

        $block->append($blockGeneral);
        $this->generalBlockWasAppended = true;
    }

    protected function addLeft(Mage_Core_Block_Abstract $block)
    {
        return parent::_addLeft($block);
    }

    protected function addContent(Mage_Core_Block_Abstract $block)
    {
        return parent::_addContent($block);
    }

    //########################################
}