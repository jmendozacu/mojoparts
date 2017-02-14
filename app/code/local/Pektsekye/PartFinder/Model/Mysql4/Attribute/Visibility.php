<?php

class Pektsekye_PartFinder_Model_Mysql4_Attribute_Visibility extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('partfinder/attribute_visibility', 'id');
    }  


    public function getHiddenAttributeIds()
    {
      $select = $this->_getReadAdapter()->select()
        ->from($this->getMainTable(), 'attribute_id');
               
      return (array) $this->_getReadAdapter()->fetchCol($select);
    }   
}
