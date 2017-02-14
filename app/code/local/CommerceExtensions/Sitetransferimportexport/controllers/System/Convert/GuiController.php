<?php

include_once "Mage/Adminhtml/controllers/System/Convert/ProfileController.php";

class CommerceExtensions_Sitetransferimportexport_System_Convert_GuiController extends Mage_Adminhtml_System_Convert_ProfileController
{
    protected function _initProfile($idFieldName = 'id')
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Import and Export'))
             ->_title($this->__('Profiles'));

        $profileId = (int) $this->getRequest()->getParam($idFieldName);
        $profile = Mage::getModel('sitetransferimportexport/profile');

        if ($profileId) {
            $profile->load($profileId);
            if (!$profile->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->__('The profile you are trying to save no longer exists'));
                $this->_redirect('*/*');
                return false;
            }
        }

        Mage::register('current_convert_profile', $profile);

        return $this;
    }
    
    public function indexAction()
    {
        $this->_title($this->__('System'))
            ->_title($this->__('Import and Export'))
            ->_title($this->__('CommerceExtensions Product Import/Export'));

        if ($this->getRequest()->getQuery('ajax'))
        {
            $this->_forward('grid');
            return;
        }
        $this->loadLayout();

        $this->_setActiveMenu('system/convert');

        $this->_addContent(
            $this->getLayout()->createBlock('sitetransferimportexport/system_convert_gui', 'convert_profile')
        );

        $this->_addBreadcrumb(Mage::helper('sitetransferimportexport')->__('Import/Export'), Mage::helper('adminhtml')->__('Import/Export'));
        $this->_addBreadcrumb(Mage::helper('sitetransferimportexport')->__('Profiles'), Mage::helper('adminhtml')->__('Profiles'));

        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('sitetransferimportexport/system_convert_gui_grid')->toHtml());
    }

    public function editAction()
    {
        $this->_initProfile();
        $this->loadLayout();

        $profile = Mage::registry('current_convert_profile');

        $data = Mage::getSingleton('adminhtml/session')->getConvertProfileData(true);

        if (!empty($data))
        {
            $profile->addData($data);
        }

        $this->_title($profile->getId() ? $profile->getName() : $this->__('New Profile'));

        $this->_setActiveMenu('system/convert');

        $this->_addContent(
            $this->getLayout()->createBlock('sitetransferimportexport/system_convert_gui_edit')
        );

        $this->_addLeft($this->getLayout()->createBlock('sitetransferimportexport/system_convert_gui_edit_tabs'));

        $this->renderLayout();
    }

    public function uploadAction()
    {
        $this->_initProfile();
        $profile = Mage::registry('current_convert_profile');
    }

    public function uploadPostAction()
    {
        $this->_initProfile();
        $profile = Mage::registry('current_convert_profile');
    }

    public function downloadAction()
    {
        $filename = $this->getRequest()->getParam('filename');
        if (!$filename || strpos($filename, '..')!==false || $filename[0]==='.')
        {
            return;
        }
        $this->_initProfile();
        $profile = Mage::registry('current_convert_profile');
    }

    protected function _isAllowed()
    {
  	    return Mage::getSingleton('admin/session')->isAllowed('system/convert/sitetransferimportexport');
    }
}
