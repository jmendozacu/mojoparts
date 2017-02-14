<?php

class Pektsekye_PartFinder_Model_Mysql4_Column extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {    
        $this->_init('partfinder/column', 'id');
    }      


    public function getMappedAttributeIds()
    {
      $select = $this->_getReadAdapter()->select()
        ->distinct(true)
        ->from($this->getMainTable(), 'attribute_id'); 
        
      return $this->_getReadAdapter()->fetchCol($select);
    } 
    
    
    public function getColumnNames()
    {    
      return (array) $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()     
              ->from($this->getMainTable(), "column_name")
            );      
    } 
}
