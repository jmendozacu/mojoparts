<?php

class Pektsekye_PartFinder_Model_Mysql4_Attribute_Addedvalues extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('partfinder/attribute_addedvalues', 'id');
    }  



     public function addValues($attributeId, $values)
    {
    
        $r = $this->_getReadAdapter()->fetchRow("SHOW TABLE STATUS LIKE '{$this->getTable('eav/attribute_option')}'");
        $nextOptionId = $r['Auto_increment'];
        
        $r = $this->_getReadAdapter()->fetchRow("SHOW TABLE STATUS LIKE '{$this->getTable('eav/attribute_option_value')}'");
        $nextValueId = $r['Auto_increment'];

        $optionIds = array();

        $optionsStr = '';
        $valuesStr = '';
        foreach ($values as $value){
          $optionsStr .= ($optionsStr  != '' ? ',' : '') . "({$nextOptionId}, {$attributeId})";        
          $valuesStr  .= ($valuesStr   != '' ? ',' : '') . "({$nextValueId}, {$nextOptionId}, 0, {$this->_getWriteAdapter()->quote($value)})";
          $optionIds[] = $nextOptionId;           	        
          $nextOptionId++;
          $nextValueId++;         
        }        

        $this->_getWriteAdapter()->raw_query("
            INSERT INTO `{$this->getTable('eav/attribute_option')}` (
              `option_id`,            
              `attribute_id`
            ) VALUES {$optionsStr}
        ");
             
        $this->_getWriteAdapter()->raw_query("
            INSERT INTO `{$this->getTable('eav/attribute_option_value')}` (
              `value_id` ,            
              `option_id` ,
              `store_id` ,
              `value`
            ) VALUES {$valuesStr}           
        ");
       
        return $optionIds;

    }   
    
 
 
 
    public function deleteValues($optionIdsStr)
    {        
        $this->_getWriteAdapter()->delete($this->getTable('eav/attribute_option'), "`option_id` IN ({$optionIdsStr})"); 
    }
    
    
}
