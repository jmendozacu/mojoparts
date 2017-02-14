<?php

class Pektsekye_PartFinder_Model_Attribute extends Mage_Core_Model_Abstract
{
                            
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/attribute');
    }
    
    
    
    
    public function getProductIds($rowId)     
    {          
      $optionIds = Mage::getResourceModel('partfinder/indexer_column')->getOptionIds($rowId);  

      if (count($optionIds) == 0)
        return array();
        
      $storeId = Mage::app()->getStore()->getId();  
      
      $allIds   = array();
      $foundIds = array();         
      foreach ($optionIds as $attributeId => $optionIds){

        $ids = $this->getResource()->findProducts($attributeId, $storeId);
        foreach($ids as $id)
          $allIds[$id][] = $attributeId;           
                
        $ids = $this->getResource()->findProducts($attributeId, $storeId, $optionIds);
        foreach($ids as $id)
          $foundIds[$id][] = $attributeId;            
                 
      }      
     
      $spIds = array();  // products with multiple attributes
      foreach($allIds as $pId => $aIds){
        if (count($aIds) > 1)
          $spIds[$pId] = $aIds;
      }

      foreach($spIds as $pId => $aIds){   // unset products with multiple attributes that don't have all values selected
        if (count(array_diff($aIds, $foundIds[$pId])) > 0)
          unset($foundIds[$pId]);
      } 

      return array_keys($foundIds);  
			
		}	





    public function getFilterableAttributes()
    { 
      $productAttributes = Mage::getResourceSingleton('catalog/product')
          ->loadAllAttributes()
          ->getAttributesByCode();

      $attributes = array();
      foreach ($productAttributes as $attribute){
        if ($attribute->getIsUserDefined() && ($attribute->getIsFilterable() > 0 || $attribute->getIsFilterableInSearch() == 1))
          $attributes[] = $attribute;        
      }
      
      return $attributes;
    }
    
    
    
    
    public function getAttributesAsOptions()
    { 
      $attributes = array();
      foreach ($this->getFilterableAttributes() as $attribute)
          $attributes[] = array('value'=>$attribute->getAttributeId(), 'label'=>$attribute->getFrontendLabel());              
      
      return $attributes;
    }



    public function getFilterableAttributeCodes()
    {     
      $codes = array();
      foreach ($this->getFilterableAttributes() as $attribute)
        $codes[] = $attribute->getAttributeCode();
        
      return $codes;  
    }
    
    
}
