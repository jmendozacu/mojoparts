<?php

class Pektsekye_PartFinder_Model_Mysql4_Attribute extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {    
        $this->_init('eav/attribute', 'attribute_id');
    }  



    public function fetchProductIds($attributes, $storeId)
    {

      $optionIds = array();
      foreach ($attributes as $v){
        $optionIds[$v['id']][] = $v['option_id'];
      }

      $pp = array();
      $foundIds = array();      
      foreach ($optionIds as $attributeId => $oIds){

        $select = $this->_getReadAdapter()->select()         
          ->from($this->getTable('catalog/product_index_eav'), 'entity_id')
          ->where('attribute_id = ?', $attributeId)  
          ->where('store_id = ?', $storeId);

        $ids = (array) $this->_getReadAdapter()->fetchCol($select);
         
         foreach($ids as $id){
            $pp[$id][] = $attributeId;           
         }
         

        $values = '';
        foreach ($oIds as $oId)
          $values .= ($values != '' ? ' OR ' : '') .  "`value`={$oId}";     
        
        $select = $this->_getReadAdapter()->select()
          ->distinct(true)          
          ->from($productIndexEavTable, 'entity_id')
          ->where('attribute_id = ?', $attributeId)  
          ->where('store_id = ?', $storeId)         
          ->where($values);

        $ids = (array) $this->_getReadAdapter()->fetchCol($select);
        
        foreach($ids as $id){
          $foundIds[$id][] = $attributeId;            
        }
         
      }
      
     
      $spIds = array();
      foreach($pp as $k=>$v){
        if (count($v) > 1)
          $spIds[$k] = $v;
      }


      foreach($spIds as $k=>$attributeIds){
        if (count(array_diff($attributeIds,$foundIds[$k])) > 0)
          unset($foundIds[$k]);
      } 

      return array_keys($foundIds);  
     
     
    }


}
