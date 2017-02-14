<?php

class Pektsekye_PartFinder_Selector_AjaxController extends Mage_Core_Controller_Front_Action
{


    public function initSelectorFilter()
    { 
      $values = array();
      
      $collection = Mage::getModel('partfinder/selector_level')->getCollection();
      foreach ($collection as $level)
        $values[] = $this->getRequest()->getParam($level->getUrlParameter());
        
      $rowId = Mage::getModel('partfinder/selector')->fetchRowId($values);  
          
      if (is_null($rowId))        
        return;
        
      $pIds  = Mage::getModel('partfinder/restriction')->getProductIds($rowId);    
      $pIds2 = Mage::getModel('partfinder/attribute')->getProductIds($rowId);       
			
      $pIds = array_unique(array_merge($pIds, $pIds2));
      
      if (count($pIds) == 0)         
        return;      
      
      Mage::register('partfinder_product_ids', $pIds);
	    
    }



     
    public function checkResultAction()
    { 

      $this->initSelectorFilter();

      $result = array();

      if (!is_null(Mage::registry('partfinder_product_ids'))){

	      $searchQuery = $this->getRequest()->getParam(Mage::helper('catalogsearch')->getQueryParamName());
	      $categoryId  = $this->getRequest()->getParam(Mage::getModel('catalog/layer_filter_category')->getRequestVar());	  
	        
        if (!is_null($searchQuery)){
        
          Mage::helper('partfinder')->saveQuery();           
	        $result['productsFound'] = Mage::getSingleton('partfinder/searchlayer')->getProductCollection()->getSize() > 0;	          
	        
        } elseif (!is_null($categoryId)) {
        	               
          $category = Mage::getModel('catalog/category')->load((int) $categoryId);          
	        $result['categories']    = Mage::getSingleton('partfinder/categorylayer')->checkSubCategories($category); 
	                  
        } else { 

	        $result['categories']    = Mage::getSingleton('partfinder/categorylayer')->checkSubCategories(null);
	        $result['productsFound'] = true;	                            
        }
	       

     
	    }
	    	    
      $this->getResponse()->setBody(Zend_Json::encode($result));
    }   

      
}
