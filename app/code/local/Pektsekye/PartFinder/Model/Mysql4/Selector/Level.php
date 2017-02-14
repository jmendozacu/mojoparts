<?php

class Pektsekye_PartFinder_Model_Mysql4_Selector_Level extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('partfinder/selector_level', 'id');
    }  


    public function getColumnNames()
    {    
      return (array) $this->_getReadAdapter()->fetchCol($this->_getReadAdapter()->select()     
              ->from($this->getMainTable(), "column_name")
            );      
    } 
    	      
    	      
    public function emptyTable()
    {      
      $this->_getReadAdapter()->raw_query("TRUNCATE TABLE `{$this->getMainTable()}`");   
    }	      


        
}
