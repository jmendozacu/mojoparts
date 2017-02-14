<?php

class Pektsekye_PartFinder_Pf_Attribute_AddedvaluesController extends Mage_Adminhtml_Controller_action
{

  
  public function indexAction()
  {
      $this->_title($this->__('Part Finder'))
           ->_title($this->__('Add Values'));
      
      $this->loadLayout();
      $this->_setActiveMenu('catalog/partfinder');   
      $this->_addContent($this->getLayout()->createBlock('partfinder/pf_attribute_addedvalues', 'partfinder_attribute'));         
      $this->renderLayout();
  }




  public function newAction()
  { 
      $this->_title($this->__('Part Finder'))
           ->_title($this->__('Add Values'));

      $this->_title($this->__('Add Value'));
    
      $this->loadLayout();
      $this->_setActiveMenu('catalog/partfinder');
      $this->_addContent($this->getLayout()->createBlock('partfinder/pf_attribute_addedvalues_add'));
      $this->renderLayout();
  }




  public function saveAction()
  {  
		if ($post = $this->getRequest()->getPost()) {
 
			try {
			
        Mage::getModel('partfinder/attribute_addedvalues')->addValues($post['attribute_id'], $post['column_name']);	
	
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Values were successfully added.'));
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
          Mage::getModel('partfinder/attribute_addedvalues')->load($id)->deleteValues();
          Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The values have been deleted.'));
      } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while deleting the values.'));
      }

      $this->_redirect("*/*/");
  }
  
  
  
  public function massDeleteAction()
  {
    $ids = $this->getRequest()->getParam('ids');
    
    if (is_array($ids)) {
      try {
          $model = Mage::getModel('partfinder/attribute_addedvalues');      
          foreach ($ids as $id)
            $model->load((int) $id)->deleteValues();         
          
          Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The values have been deleted.'));
      } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while deleting the values.'));
      }  
    } else {
    	Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
    }
    
    $this->_redirect('*/*/');

  }
  

 
}
