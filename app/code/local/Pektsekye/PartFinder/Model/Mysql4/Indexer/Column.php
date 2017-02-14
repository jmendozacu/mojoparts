<?php

class Pektsekye_PartFinder_Model_Mysql4_Indexer_Column extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {    
        $this->_init('partfinder/attribute_option', 'id');
    }  



    public function addOptionIds($attributeId, $optionIds)
    {
    
        $valuesStr = '';
        foreach ($optionIds as $optionId => $rowIds){  
          foreach ($rowIds as $rowId) 
            $valuesStr .= ($valuesStr != '' ? ',' : '') . "({$rowId}, {$attributeId}, {$optionId})";	        	            
        }
        
        $this->_getWriteAdapter()->raw_query("
            INSERT IGNORE INTO `{$this->getMainTable()}` (
              `selector_row_id`,            
              `attribute_id`,
              `attribute_option_id`              
            ) VALUES {$valuesStr}
        ");           

    } 
    
    
    
     public function getOptionIds($rowId)     
    {      
      $select = $this->_getReadAdapter()->select()     
                 ->from($this->getMainTable(), array('attribute_id', 'attribute_option_id'))
                 ->where("selector_row_id = ?", $rowId);
                 
      $rows = $this->_getReadAdapter()->fetchAll($select); 
      
      $optionIds = array();
      foreach ($rows as $r)
        $optionIds[$r['attribute_id']][] = $r['attribute_option_id'];      
      
      return $optionIds; 
            
    }
    
    
    
    public function emptyTable()
    {    
			$this->_getWriteAdapter()->truncate($this->getMainTable());
		}
}
