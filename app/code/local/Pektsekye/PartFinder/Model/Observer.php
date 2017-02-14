<?php

class Pektsekye_PartFinder_Model_Observer
{

    public function hideAttributes($observer)
    {
        $collection = $observer->getEvent()->getCollection();

        if ($collection instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Attribute_Collection
           || $collection instanceof Mage_Eav_Model_Mysql4_Entity_Attribute_Collection) {
        
          $attributeIds = Mage::getResourceModel('partfinder/attribute_visibility')->getHiddenAttributeIds();
          if (count($attributeIds) > 0)
            $collection->addFieldToFilter('main_table.attribute_id', array('nin' => $attributeIds));
        }
        
        return $this;
    }
    
    
    public function setIsGlobal(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getAttribute();
        if ($object->getAttributeCode() == Pektsekye_PartFinder_Model_Restriction::ATTRIBUTE_CODE){ 
        	$object->setIsGlobal(1);        
        }
    }

    
}
