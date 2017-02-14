<?php

class Pektsekye_PartFinder_Model_Mysql4_Selector extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('partfinder/selector', 'id');
    }  

    

     public function fetchColumnValues($values = array())     
    {
      $nextlevel = count($values);
      $select = $this->_getWriteAdapter()->select()
              ->distinct(true)      
              ->from($this->getMainTable(), "level_{$nextlevel}")
              ->order("level_{$nextlevel}");
              
      foreach ($values as $k => $value)
        $select->where("level_{$k} = ?", $value);
      
      return $this->_getWriteAdapter()->fetchCol($select);        
    }




     public function getRowValues($levelCount)     
    {
      $levels = array('id');
      for ($i=0;$i<$levelCount;$i++)
        $levels[] = "LOWER(level_{$i}) as level_{$i}";
            
      return $this->_getReadAdapter()->fetchAll($this->_getWriteAdapter()->select()     
              ->from($this->getMainTable(), $levels)
            );        
    }
    
    
    
     public function fetchRowId($values)     
    {
      $select = $this->_getReadAdapter()->select()     
                 ->from($this->getMainTable(), 'id')
                 ->limit(1);
                 
      foreach ($values as $k => $value)           
        $select->where("level_{$k} = ?", $value);
      
      return $this->_getReadAdapter()->fetchOne($select);     
    }
    
  


            
    public function createSelector($columns)
    {       
    
      $sFields  = '';
      $dbFields = '';
      $where = '';
      $l = 0;
      foreach ($columns as $column){
        $sFields  .= ($l != 0 ? ',' : '')     . "`level_{$l}`";
        $dbFields .= ($l != 0 ? ',' : '')     . "`pf_{$column}`";
        $where    .= ($l != 0 ? ' AND ' : '') . "`pf_{$column}` != ''";        
        $l++;
      }
         
      $this->_getWriteAdapter()->raw_query("
		    INSERT INTO `{$this->getMainTable()}`
		    (`id`, {$sFields})
			    SELECT 
				  `id`, {$dbFields}
			    FROM `{$this->getTable('partfinder/db')}`
			    WHERE {$where}
			    GROUP BY {$dbFields};
      ");   
        
    }



    public function emptyTable()
    {      
      $this->_getReadAdapter()->raw_query("TRUNCATE TABLE `{$this->getMainTable()}`");   
    }	      
    
}
