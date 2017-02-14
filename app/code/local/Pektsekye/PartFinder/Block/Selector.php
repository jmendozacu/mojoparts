<?php

class Pektsekye_PartFinder_Block_Selector extends Mage_Core_Block_Template
{

     protected $_levelCollection;
     protected $_parentCategories;



     public function getLevelSelectHtml($item)         
    {
    
        $id    = "partfinder_level_{$item->getLevel()}_select";
        $class = '';
        
        if ($item->getLevel() == 0){
              
          $extra = 'onchange="partFinder.loadLevel(this.value,0)"'; 
          
        } else {        
              
          if ($item->getLevel() + 1 < $this->getLevelCount()){ 
            $extra = 'onchange="partFinder.loadLevel(this.value,'. $item->getLevel() .')"';
          } elseif ($this->canShowExtra()) {   
            $extra = 'onchange="partFinder.showExtra()"';                     
          } else {       
            //$extra = 'onchange="partFinder.submit()"'; 
          } 
                    
          if (!$this->getIsSelected()){                    
            $class  = 'disabled';
            $extra .= ' disabled="disabled"';  
          }          
                    
        }
 
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($item->getUrlParameter())
            ->setId($id)
            ->setClass($class)            
            ->setOptions($this->getLevelOptions($item))           
            ->setExtraParams($extra);
            
        if ($this->getIsSelected())
          $select->setValue($this->getSelectedValue($item->getLevel()));
        
        
        return $select->getHtml();
       
    }

    
    
    
     public function getLevelOptions($item)     
    {
      $selectorModel = Mage::getModel('partfinder/selector');
      
      $values = array();      
      if ($item->getLevel() == 0){
        $values = $selectorModel->fetchColumnValues();       
      } elseif ($this->getIsSelected()) {  
        $values = $selectorModel->fetchColumnValues(array_slice($this->getSelectedValues(), 0, $item->getLevel()));
      }
      
      return Mage::helper('partfinder')->toOptions($values, $item->getOptionTitle());
    }




     public function getCategorySelectsHtml()     
    { 

      if (!$this->productsFound())
        return '';
    
      $html = '';

      for ($i = 0;$i < $this->getCategorySelectCount();$i++){

        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($this->getCategoryParamName())
            ->setId("partfinder_category_select_{$i}")     
            ->setOptions($this->getCategoryOptions($i))                    
            ->setExtraParams("onchange=\"partFinder.checkSubCategories({$i}, this.value)\"");

        if ($this->isCategorySelected())
          $select->setValue($this->getParentCategory($i)->getId());
          
        $html .= $select->getHtml();
        
      }

      return $html;  
     
    }





     public function getCategoryOptions($index)     
    {
        $category   = $index > 0 && $this->isCategorySelected() ? $this->getParentCategory($index - 1) : null;
        
	      $categories = Mage::getModel('partfinder/categorylayer')->checkSubCategories($category);

        $options = array(array('value' => '', 'label' => $this->__('-- Select Category --')));
        foreach ($categories as $category)
          $options[] = array('value' => $category['id'], 'label' => $category['title']);
          
        return $options;  
    }





     public function getLevelCollection()     
    { 
       if (!isset($this->_levelCollection))
          $this->_levelCollection = Mage::getModel('partfinder/selector_level')->getCollection();
          
       return $this->_levelCollection;   
    }   
    

     public function getLevelCount()     
    { 
       return $this->getLevelCollection()->getSize();        
    }
    
    
    
    
    
    
    
    
     public function getSubmitUrl()     
    { 
       return Mage::getUrl('partfinder/product/list');        
    }
    
    
    
     public function getSearchQueryParamName()     
    { 
       return $this->helper('catalogsearch')->getQueryParamName();        
    }    
           
           
           
     public function getSearchQueryText()     
    { 
       return $this->productsFound() ? $this->helper('catalogsearch')->getEscapedQueryText() : '';
    } 



     public function getCategoryParamName()     
    {
       return Mage::getModel('catalog/layer_filter_category')->getRequestVar();       
    }
    
    
     public function getRequestUrl()     
    { 
       return Mage::getUrl('partfinder/selector/fetch');        
    }
        
        
        
     public function getCheckResultUrl()     
    { 
       return Mage::getUrl('partfinder/selector_ajax/checkResult');        
    }



     public function getLevelParameterNames()     
    {
      $paramNames = array();
      foreach ($this->getLevelCollection() as $item)
        $paramNames[] = $item->getUrlParameter(); 
        
      return Zend_Json::encode($paramNames);        
    }
    
    
    
    
     public function canShowExtra()     
    {     
      return $this->getWordSearchEnabled() || ($this->getCategorySearchEnabled() && $this->canShowCategorySelect());   
    }    
    

     public function canShowCategorySelect()     
    {     
      return !$this->getIsSelected() || (!$this->productsFound() || $this->getCategorySelectCount() > 0);  
    }     
    
    
    
     public function getCategorySearchEnabled()     
    { 
       return Mage::getStoreConfig('partfinder/frontendsettings/categorysearch') == 1;        
    }



     public function getWordSearchEnabled()     
    { 
       return Mage::getStoreConfig('partfinder/frontendsettings/wordsearch') == 1;        
    }


     public function getCategorySearchEnabledStr()     
    { 
       return $this->getCategorySearchEnabled() ? 'true' : 'false';        
    }


     public function getWordSearchEnabledStr()     
    { 
       return $this->getWordSearchEnabled() ? 'true' : 'false';        
    }    
    
    
     public function getCategorySelectCount()     
    {
      $catCount = count($this->getParentCategories());
      return $this->productsFound() ? ($this->isCategorySelected() ? $catCount : 1) : 0;       
    } 





    
     public function productsFound()     
    {
      if (!is_null(Mage::registry('partfinder_product_ids'))){
        $productCount = Mage::registry('partfinder_layer')->getProductCollection()->getSize();
        return $productCount > 0;
      }
      return false;       
    }



     public function isCategorySelected()     
    { 
      return !is_null(Mage::registry('partfinder_applied_category'));
    }    
             


     public function getIsSelected()     
    { 
        return !is_null($this->getSelectedValues());        
    }
       
    
     public function getSelectedValues()     
    {      
        return Mage::registry('partfinder_selected_values');
    }

    
     public function getSelectedValue($level)     
    { 
        $values = $this->getSelectedValues();         
        return $values[$level];
    }    



     public function getParentCategories()     
    {
    
      if (!isset($this->_parentCategories)){   
      
        $this->_parentCategories = array();  
        
        $category = Mage::registry('partfinder_applied_category');        
        if (!is_null($category)){
          $parent = $category->getParentCategories();
          foreach ($category->getPathIds() as $id)
            if (isset($parent[$id]))
              $this->_parentCategories[] = $parent[$id];
        }
        
      }
      
       return $this->_parentCategories;      
    }



     public function getParentCategory($index)   
    {
       $categories = $this->getParentCategories();
       return $categories[$index];      
    }

    
}
