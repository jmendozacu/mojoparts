<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
 
/**
 * TaxJar Admin Router
 * Connect and disconnect TaxJar accounts
 */
class Taxjar_SalesTax_Adminhtml_Tax_NexusController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Sales'))
             ->_title($this->__('Tax'))
             ->_title($this->__('Nexus Addresses'));

        $this->_reviewAddresses();

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('taxjar/adminhtml_tax_nexus'))
            ->renderLayout();
    }
    
    public function syncAction()
    {
        try {
            Mage::getModel('taxjar/tax_nexus')->syncCollection();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('taxjar')->__('Your nexus addresses have been synced from TaxJar.'));            
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
    
    public function newAction()
    {
        $this->_forward('edit');
    }
    
    public function editAction()
    {
        $this->_title($this->__('Sales'))
             ->_title($this->__('Tax'))
             ->_title($this->__('Nexus Addresses'));

        $nexusId = $this->getRequest()->getParam('id');
        $model = Mage::getModel('taxjar/tax_nexus');

        if ($nexusId) {
            $model->load($nexusId);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('taxjar')->__('This nexus address no longer exists.')
                );
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($nexusId ? $model->getRegion() : $this->__('New Nexus Address'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('taxjar/tax_nexus', $model);

        $this->_initAction()
            ->_addBreadcrumb(
                $nexusId ? Mage::helper('taxjar')->__('Edit Nexus Address') : Mage::helper('taxjar')->__('New Nexus Address'),
                $nexusId ? Mage::helper('taxjar')->__('Edit Nexus Address') : Mage::helper('taxjar')->__('New Nexus Address')
            )
            ->_addContent(
                $this->getLayout()->createBlock('taxjar/adminhtml_tax_nexus_edit')
                    ->setData('action', $this->getUrl('*/tax_nexus/save'))
            )
            ->renderLayout();
    }
    
    public function saveAction()
    {
        $nexusPost = $this->getRequest()->getPost();

        if ($nexusPost) {
            $nexusId = $this->getRequest()->getParam('id');

            if ($nexusId) {
                $nexusModel = Mage::getSingleton('taxjar/tax_nexus')->load($nexusId);

                if (!$nexusModel->getId()) {
                    unset($nexusPost['id']);
                }
            }

            $nexusModel = Mage::getModel('taxjar/tax_nexus')->setData($nexusPost);
            $regionModel = Mage::getModel('directory/region')->load($nexusModel->getRegionId());
            
            $nexusModel->setRegion($regionModel->getName());
            $nexusModel->setRegionCode($regionModel->getCode());

            try {
                if (is_array($errors = $nexusModel->validate())) {
                    Mage::getSingleton('adminhtml/session')->setFormData($nexusPost);
                    Mage::getSingleton('adminhtml/session')->addError($errors[0]);
                    $this->_redirectReferer();
                    return;
                }
                
                try {
                    if ($nexusModel->getCountryId() == 'US') {
                        $nexusModel->sync();
                    }
                    $nexusModel->save();
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('taxjar')->__('The nexus address has been saved.'));
                    $this->getResponse()->setRedirect($this->getUrl("*/*/"));
                    return true;
                } catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('adminhtml/session')->setFormData($nexusPost);
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->setFormData($nexusPost);
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }

            $this->_redirectReferer();
            return;
        }

        $this->getResponse()->setRedirect($this->getUrl('*/tax_nexus'));
    }
    
    public function deleteAction()
    {
        if ($nexusId = $this->getRequest()->getParam('id')) {
            $nexusModel = Mage::getModel('taxjar/tax_nexus')->load($nexusId);
            if ($nexusModel->getId()) {
                try {
                    if ($nexusModel->getCountryId() == 'US') {
                        $nexusModel->syncDelete();
                    }
                    $nexusModel->delete();

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('taxjar')->__('The nexus address has been deleted.'));
                    $this->getResponse()->setRedirect($this->getUrl("*/*/"));
                    return true;
                }
                catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
                catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('taxjar')->__('An error occurred while deleting this nexus address.'));
                }
                if ($referer = $this->getRequest()->getServer('HTTP_REFERER')) {
                    $this->getResponse()->setRedirect($referer);
                }
                else {
                    $this->getResponse()->setRedirect($this->getUrl("*/*/"));
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('taxjar')->__('An error occurred while deleting this nexus address. Incorrect nexus ID.'));
                $this->getResponse()->setRedirect($this->getUrl('*/*/'));
            }
        }
    }
    
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/tax/taxjar_salestax_nexus')
            ->_addBreadcrumb(Mage::helper('taxjar')->__('Tax'), Mage::helper('taxjar')->__('Tax'))
            ->_addBreadcrumb(Mage::helper('taxjar')->__('Nexus Addresses'), Mage::helper('taxjar')->__('Nexus Addresses'))
        ;
        return $this;    
    }
    
    protected function _isAllowed()
    {
        $connected = Mage::getStoreConfig('tax/taxjar/connected');
        
        if (!$connected) {
            return false;
        }
        
        return Mage::getSingleton('admin/session')->isAllowed('sales/tax/taxjar_salestax_nexus');
    }
    
    protected function _reviewAddresses()
    {
        $nexusMissingPostcode = Mage::getModel('taxjar/tax_nexus')->getCollection()->addFieldToFilter('postcode', array('null' => true));
        
        if ($nexusMissingPostcode->getSize()) {
            return Mage::getSingleton('core/session')->addNotice(Mage::helper('taxjar')->__('One or more of your nexus addresses are missing a zip/post code. Please provide accurate data for each nexus address.'));
        }
    }
}