<?php

class Pektsekye_PartFinder_Model_Indexer_Column extends Mage_Index_Model_Indexer_Abstract
{

    /**
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE            
        ),
        Pektsekye_PartFinder_Model_Convert_Adapter_Db::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE           
        ),
        Pektsekye_PartFinder_Model_Selector::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE           
        ),
        Pektsekye_PartFinder_Model_Column::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,        
            Mage_Index_Model_Event::TYPE_DELETE           
        ),
        Pektsekye_PartFinder_Model_Attribute_Addedvalues::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,        
            Mage_Index_Model_Event::TYPE_DELETE           
        )            
    );

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('partfinder/indexer_column');
    }

    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('partfinder')->__('PartFinder Mapped Columns');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('partfinder')->__('Rebuild PartFinder mapped columns index');
    }


    /**
     * Register data required by process in event object
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $mappedAttributeIds = Mage::getResourceModel('partfinder/column')->getMappedAttributeIds();
        
        if (count($mappedAttributeIds) == 0)
            return;
            
        if ($event->getEntity() == Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY) {          
          $attribute = $event->getDataObject();          
          if (!in_array($attribute->getId(), $mappedAttributeIds))
            return;            
        } 
       
        $event->getProcess()->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);                      
    }

    /**
     * Process event
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {

    }


    public function reindexAll()
    {
    
      $this->getResource()->emptyTable();
            
      $attributeIds = Mage::getResourceModel('partfinder/column')->getMappedAttributeIds();
      foreach ($attributeIds as $attributeId){
        $this->addAttributeToIndex($attributeId);
      }
            
    }  
    
    
    
    public function addAttributeToIndex($attributeId)
    {
    
      $entityTypeId = (int) Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
      

      $collection = Mage::getModel('partfinder/column')->getCollection()
        ->addFieldToFilter('attribute_id', $attributeId);
        
      if ($collection->getSize() > 0){     
      
        $columns = array();
        foreach ($collection as $item)      
          $columns[] = $item->getColumnName();
          
        $rowIds = Mage::getModel('partfinder/db')->getRowIds($columns);
  
        if (count($rowIds) > 0){ 
  
          $eavOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
              ->setAttributeFilter($attributeId)
              ->setStoreFilter(0, false)            
              ->load();           
  
          $eavOptionIds = array();    
          foreach ($eavOptionCollection as $option)
            $eavOptionIds[$option->getValue()] = $option->getId();
            
          $eavOptionIds = array_intersect_key($eavOptionIds, $rowIds);                 
          
          if (count($eavOptionIds) > 0){ 
          
            $optionIds = array();    
            foreach ($rowIds as $value => $rIds){
              if (isset($eavOptionIds[$value]))     
                $optionIds[$eavOptionIds[$value]] = $rIds;
            }
                                 
            $this->getResource()->addOptionIds($attributeId, $optionIds);
          }
        }
      }
    } 
    
    
    
}
