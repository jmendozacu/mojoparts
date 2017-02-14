<?php

class Pektsekye_PartFinder_ProductController extends Mage_Core_Controller_Front_Action
{



    public function listAction()
    {
  
      $selectedValues = $this->getSelectedValues();
      
      if (is_null($selectedValues)){
		    $this->getResponse()->setRedirect(Mage::getBaseUrl());      
        return;
      }
      
      
      $rowId = Mage::getModel('partfinder/selector')->fetchRowId($selectedValues);  
             
      if (is_null($rowId)){
		    $this->getResponse()->setRedirect(Mage::getBaseUrl());      
        return;
      }
     

      $pIds  = Mage::getModel('partfinder/restriction')->getProductIds($rowId);      
      $pIds2 = Mage::getModel('partfinder/attribute')->getProductIds($rowId);       

      $pIds = array_unique(array_merge($pIds, $pIds2));
      
      if (count($pIds) == 0){
		    $this->getResponse()->setRedirect(Mage::getBaseUrl());          
        return;
      }


      if ($this->categorySearchEnabled() && $this->isCategorySelected())              
        Mage::register('partfinder_applied_category', $this->getCategory());                    
               
     
      if ($this->wordSearchEnabled() && $this->isSearchQuery())
        Mage::helper('partfinder')->saveQuery();       
       
       
      Mage::register('partfinder_selected_values', $selectedValues);
      Mage::register('partfinder_product_ids', $pIds);
      Mage::register('partfinder_layer', $this->getLayer());  
      
      
      $this->loadLayout();
      $this->renderLayout();
      
    }





    public function getSelectedValues()
    {
    
      $values = array();
      $collection = Mage::getModel('partfinder/selector_level')->getCollection();
      foreach($collection as $level){
        $value = $this->getRequest()->getParam($level->getUrlParameter());
        if (!is_null($value)){
          $values[] = $value;          
        } else {         
          return null;
        }
      }
      
      return $values;
    }  




    public function getCategory()
    {
      $categoryId = $this->getRequest()->getParam(Mage::getModel('catalog/layer_filter_category')->getRequestVar());
      if (!is_null($categoryId)){      
        $category = Mage::getModel('catalog/category')->load((int) $categoryId);
        if (!is_null($category->getId()))
          return $category;
      }      
       return null;     
    }
    


    public function getLayer()
    {  	    	    
      if ($this->wordsearchEnabled() && $this->isSearchQuery()){            
        $layerName = 'partfinder/searchlayer';                         
      } else {
        $layerName = 'partfinder/categorylayer';      
      }
      
      return Mage::getSingleton($layerName);
    }    
    
    
    
    public function categorySearchEnabled()
    {
      return Mage::getStoreConfig('partfinder/frontendsettings/categorysearch') == 1;
    }      
    
    public function wordSearchEnabled()
    {
      return Mage::getStoreConfig('partfinder/frontendsettings/wordsearch') == 1;
    }      
    
    public function isSearchQuery()
    {
      return !is_null($this->getSearchQuery());
    }  
    
    public function getSearchQuery()
    {
      return $this->getRequest()->getParam(Mage::helper('catalogsearch')->getQueryParamName());
    }  
    
    public function isCategorySelected()
    {
      return !is_null($this->getCategory());
    }  
    
    
  
}
