<?php

class Pektsekye_PartFinder_Pf_ColumnController extends Mage_Adminhtml_Controller_action
{

  
  public function indexAction()
  {
      $this->_title($this->__('Part Finder'))->_title($this->__('Map Columns'));
        
      $this->loadLayout();
      $this->_setActiveMenu('catalog/partfinder');   
      $this->_addContent(
          $this->getLayout()->createBlock('partfinder/pf_column', 'partfinder_column')
      );         
      $this->renderLayout();
  }


  
  public function mapColumnAction()
  {      
    $this->_title($this->__('Part Finder'))->_title($this->__('Map Column'));
      
    $this->loadLayout();
    $this->_setActiveMenu('catalog/partfinder');                  
    $this->_addContent($this->getLayout()->createBlock('partfinder/pf_column_map', 'partfinder_column_map'));             
    $this->renderLayout();

  }

  


  public function applyAction()
  {
		if ($post = $this->getRequest()->getPost()) {
 
			try {
			
        $model = Mage::getModel('partfinder/column');        
        $model->setAttributeId($post['attribute_id']);
        $model->setColumnName($post['column_name']);			
	      $model->save();
	      
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Column was successfully mapped.'));
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
          Mage::getModel('partfinder/column')->load($id)->delete();
          Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The record was successfully deleted'));
      } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while deleting the record.'));
      }

      $this->_redirect("*/*/index");
  }
  
  
  
  public function massDeleteAction()
  {
    $ids = $this->getRequest()->getParam('ids');
    if (!is_array($ids)) {
    	Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
    } else {
      try {
          $model = Mage::getModel('partfinder/column');
          foreach ($ids as $id){
	          $model->load((int) $id)->delete();
          }
          
          Mage::getSingleton('adminhtml/session')->addSuccess(
              Mage::helper('adminhtml')->__(
                  'Total of %d record(s) were successfully deleted', count($ids)
              )
          );
          
      } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
    }
    
    $this->_redirect('*/*/index');

  }

 
}

