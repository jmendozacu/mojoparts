<?php

class Pektsekye_PartFinder_Model_Attribute_Addedvalues extends Mage_Core_Model_Abstract
{   

    const ENTITY = 'partfinder_attribute_addedvalues';
    
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/attribute_addedvalues');
    }   
	 
	 
    public function addValues($attributeId, $columnName)
    {    

        $columnValues  = Mage::getModel('partfinder/db')->getColumnValues($columnName);
       
        if (count($columnValues) > 0){ 

          $eavOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
              ->setAttributeFilter($attributeId)
              ->setStoreFilter(0, false)            
              ->load();           

          $eavValues = array();    
          foreach ($eavOptionCollection as $option)
            $eavValues[$option->getId()] = $option->getValue();
                            
          $valuesToAddToEav  = array_diff($columnValues, $eavValues);          

          if (count($valuesToAddToEav) > 0){  
          
            $optionIds = $this->getResource()->addValues($attributeId, $valuesToAddToEav);
            
            $this->load($attributeId, 'attribute_id');
                  
            if (!is_null($this->getId())){
              $ids = explode(',', $this->getValueIds());
              $optionIds = array_unique(array_merge($ids, $optionIds)); 
            } else {
              $this->setAttributeId($attributeId);		            
            }

            $this->setValueIds(implode(',', $optionIds));			
            $this->save();
          }
          
          
        } else {
           Mage::throwException(Mage::helper('partfinder')->__('Column "%s" does not have values.', $columnName));
        }
    }
    
    
    
    
    public function deleteValues()
    {    
      $this->getResource()->deleteValues($this->getValueIds());
      $this->delete();
    }
    
    
    /**
     * Init indexing process after attribute data commit
     *
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();

        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        return $this;
    }

    /**
     * Register indexing event before delete catalog eav attribute
     *
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function _beforeDelete()
    {
        Mage::getSingleton('index/indexer')->logEvent(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
        );
        return parent::_beforeDelete();
    }   
    
}
