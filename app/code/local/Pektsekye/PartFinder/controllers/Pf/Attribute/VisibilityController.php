<?php

class Pektsekye_PartFinder_Pf_Attribute_VisibilityController extends Mage_Adminhtml_Controller_action
{

  
  public function indexAction()
  {  
      $this->_title($this->__('Part Finder'))->_title($this->__('Set Visibility'));
        
      $this->loadLayout();
      $this->_setActiveMenu('catalog/partfinder');   
      $this->_addContent($this->getLayout()->createBlock('partfinder/pf_attribute_visibility', 'partfinder_attribute'));         
      $this->renderLayout();
  }


  public function newAction()
  {    
      $this->_title($this->__('Part Finder'))
           ->_title($this->__('Attribute Visibility'));

      $this->_title($this->__('Hide Attribute'));
    
      $this->loadLayout();
      $this->_setActiveMenu('catalog/partfinder');
      $this->_addContent($this->getLayout()->createBlock('partfinder/pf_attribute_visibility_hide'));
      $this->renderLayout();
  }




  public function saveAction()
  {  
		if ($post = $this->getRequest()->getPost()) {
 
			try {
			
        Mage::getModel('partfinder/attribute_visibility')
        ->setAttributeId($post['attribute_id'])
        ->save();	
	
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The attribute was set to be hidden.'));
      } catch (Mage_Core_Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());			
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
      
    } else {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find value to save'));
    }
    
    $this->_redirect('*/*/index');
  }




  public function deleteAction()
  {
      $id = $this->getRequest()->getParam('id', false);

      try {
          Mage::getModel('partfinder/attribute_visibility')->load($id)->delete();
          Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The attribute was set to be visible.'));
      } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while setting the attribute to be visible.'));
      }

      $this->_redirect("*/*/");
  }
  
  
  
  public function massDeleteAction()
  {
    $ids = $this->getRequest()->getParam('ids');
    
    if (is_array($ids)) {
      try {
          $model = Mage::getModel('partfinder/attribute_visibility');      
          foreach ($ids as $id){
            $model->load((int) $id)->delete();         
          }
          Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The attributes were set to be visible.'));
      } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while setting the attributes to be visible.'));
      }  
    } else {
    	Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
    }
    
    $this->_redirect('*/*/');

  }


  

 
}
