<?php

class Pektsekye_PartFinder_Model_Attribute_Visibility extends Mage_Core_Model_Abstract
{                  

    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/attribute_visibility');
    } 
    
    
    
    public function getVisibleAttributesAsOptions()
    { 
      $hiddenIds = $this->getResource()->getHiddenAttributeIds();  
      
      $productAttributes = Mage::getResourceSingleton('catalog/product')
          ->loadAllAttributes()
          ->getAttributesByCode();

      $attributes = array();
      foreach ($productAttributes as $attribute){
        if (!in_array($attribute->getAttributeId(), $hiddenIds) && $attribute->getIsUserDefined() && ($attribute->getIsFilterable() > 0 || $attribute->getIsFilterableInSearch() == 1))
          $attributes[] = array('value'=>$attribute->getAttributeId(), 'label'=>$attribute->getFrontendLabel());        
      }
      
      return $attributes;
    }	
    
}
