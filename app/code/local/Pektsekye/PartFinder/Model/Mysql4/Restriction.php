<?php

class Pektsekye_PartFinder_Model_Mysql4_Restriction extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('partfinder/restriction', 'selector_row_id');
    }  




    public function getProductIds($rowId)
    {
      $pIds = $this->_getReadAdapter()->fetchOne($this->_getReadAdapter()->select()
      		->from($this->getMainTable(), 'product_ids')
      		->where('selector_row_id =?', $rowId)
      );
      
      return empty($pIds) ? array() : explode(',', $pIds);
    }



    public function insertIds($productIdsByRowId)
    { 			
			$valuesStr = '';
			foreach ($productIdsByRowId as $rowId => $productIds)
				$valuesStr .= ($valuesStr != '' ? ',' : '') . "({$rowId},'". implode(',', $productIds) ."')";	        	            			
							
      $this->_getWriteAdapter()->raw_query("
          INSERT INTO `{$this->getMainTable()}` (
            `selector_row_id`,            
            `product_ids`             
          ) VALUES {$valuesStr}
      ");       
    }



    public function getAllMagentoProductIds()
    { 	
      return $this->_getReadAdapter()->fetchPairs("SELECT `sku`,`entity_id` FROM {$this->getTable('catalog/product')}");
    }
    
        
    
    public function updateTextRestrictions($entityTypeId, $attributeId, $data)
    { 
      $productIdsStr = implode(',', array_keys($data));  
      
      $this->_getWriteAdapter()->raw_query("DELETE FROM `{$this->_resources->getTableName('catalog_product_entity_text')}` WHERE attribute_id = {$attributeId} AND `entity_id` IN ({$productIdsStr})");		    	
    
      $values = '';
      foreach ($data as $productId => $restriction){
        $restriction = trim($restriction);          
        if ($restriction != '')
          $values .= ($values != '' ? ',' : '') . "($entityTypeId, $attributeId, 0, {$productId}, {$this->_getWriteAdapter()->quote($restriction)})";        
      }
      
      if ($values != ''){        	  	        	  	  	
        $this->_getWriteAdapter()->raw_query("
          INSERT INTO `{$this->_resources->getTableName('catalog_product_entity_text')}`
           (`entity_type_id` ,
            `attribute_id` ,
            `store_id` ,
            `entity_id` ,
            `value`
            ) VALUES {$values}
        ");
      }
    }      
       
       

    public function getRestrictionData($attributeId)
    {
      return $this->_getReadAdapter()->query("
        SELECT p.sku as product_sku, pet.value
        FROM `{$this->getTable('catalog/product')}` p
        LEFT JOIN `{$this->_resources->getTableName('catalog_product_entity_text')}` pet
          ON pet.entity_id = p.entity_id AND pet.store_id = 0 AND pet.attribute_id = {$attributeId}
      ");
    } 


       
    public function emptyTable()
    {    
			$this->_getWriteAdapter()->truncate($this->getMainTable());
		}
    
}
