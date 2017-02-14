<?php

class Pektsekye_PartFinder_Pf_SelectorController extends Mage_Adminhtml_Controller_action
{

  
  public function indexAction()
  { 
      $this->_title($this->__('Part Finder'))->_title($this->__('Manage Selector'));
        
      $this->loadLayout();
      $this->_setActiveMenu('catalog/partfinder');      
      $this->renderLayout();
  }


  public function createAction()
  {
	  $post = $this->getRequest()->getPost();
		if (!is_null($post) && isset($post['columns'])) {
 
			try {

        Mage::getModel('partfinder/selector')->createSelector($post['columns'], $post['column_orders']);
	
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Selector was successfully created'));
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
      
    } else {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find value to save'));
    }
    
    $this->_redirect('*/*/index');

  }



  public function saveAction()
  {
	  $optionTitles  = $this->getRequest()->getPost('option_titles');	  
	  $parameters = $this->getRequest()->getPost('url_parameters');	
	  
		if (!is_null($optionTitles)) {
               
      $urlParameters = $this->checkUrlParameters($parameters, $optionTitles);                 
          
		  if (!is_null($urlParameters)) {
		  
        try {

          
          $model = Mage::getModel('partfinder/selector_level');
          
          $model->deleteLevels();
          
          $level = 0;
          foreach($optionTitles as $columnName => $optionTitle){
            $model->setId(null);      
            $model->setLevel($level);                
            $model->setColumnName($columnName);
            $model->setOptionTitle((string) $optionTitle);
            $model->setUrlParameter($urlParameters[$columnName]);                    
            $model->save();
            $level++;
          }
    
          Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Changes were successfully saved'));
          
        } catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        
      } else {
        Mage::getSingleton('adminhtml/session')->addError($this->__('The url parameter must be a unique value and it must not match any existing attribute code.'));
      }  
      
    } else {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find value to save'));
    }
    
    $this->_redirect('*/*/index');
  }

  
  
  
  public function deleteAction()
  {

		if (!is_null($this->getRequest()->getPost())) {
 
			try {

        Mage::getModel('partfinder/selector')->deleteSelector();
	
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Selector was successfully deleted'));
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
      
    } else {
      Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find value to delete'));
    }
    
    $this->_redirect('*/*/index');

  }



  public function checkUrlParameters($parameters, $optionTitles)
  {	  
   
    $attributeCodes = Mage::getModel('partfinder/attribute')->getFilterableAttributeCodes();
          
    $urlParameters = array();
    
    foreach($optionTitles as $columnName => $optionTitle){
      $parameter = trim($parameters[$columnName]);     
      $parameter = preg_replace('/[^\w\s]/', '', $parameter);         
      $parameter = preg_replace('/\s+/', '_', $parameter);
      $parameter = $parameter != '' ? $parameter : $columnName;
      if (!in_array($parameter, $attributeCodes)){
        $urlParameters[$columnName] = $parameter;
      } 
    }
    
    if (count(array_unique($urlParameters)) < count($optionTitles))
       return null;
   
    return  $urlParameters; 
  }
  
}
