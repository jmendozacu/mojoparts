<?php

class Pektsekye_PartFinder_Model_Mysql4_Db extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {    
        $this->_init('partfinder/db', 'id');
    }  



    public function getColumnValues($columnName)
    {

      $select = $this->_getReadAdapter()->select()
          ->from(array('selector_table' => $this->getTable('partfinder/selector')), 'id')         
          ->join(array('main_table' => $this->getMainTable()), 'main_table.id = selector_table.id', "pf_{$columnName}")
          ->where("pf_{$columnName} != ''")          
          ->order("pf_{$columnName}");

      return $this->_getReadAdapter()->fetchPairs($select);
    }



    public function getPfDatabaseExists()
    {
      $result = $this->_getReadAdapter()->fetchOne("SHOW TABLE STATUS LIKE '{$this->getMainTable()}'");
      
      return !empty($result);
    }



    public function getDbColumnNames()
    { 
      if (!$this->getPfDatabaseExists())
        return array();

      $result = $this->_getReadAdapter()->fetchAssoc("SHOW COLUMNS FROM `{$this->getMainTable()}`");
      
      return array_keys($result);
    }
    
    
    
    
    public function createTable($fieldNames)
    {    
        $columnsStr = '';
        foreach ($fieldNames as $name)                      
          $columnsStr  .= "`pf_{$name}` varchar(255) default NULL,";                  
        
        $this->_getWriteAdapter()->raw_query("
            DROP TABLE IF EXISTS `{$this->getMainTable()}`;        
            CREATE TABLE `{$this->getMainTable()}` (
              `id` int(10) unsigned NOT NULL auto_increment,
              {$columnsStr} 
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ");            
    }

    
    
    
    public function addValues($data)
    {
        $valuesStr .= '';    
        foreach ($data as $values){
          $cell = '';
          foreach ($values as $value)
            $cell .= ',' . $this->_getWriteAdapter()->quote(trim($value));
        	               
          $valuesStr .= ($valuesStr != '' ? ',' : '') . "(NULL{$cell})";     
        }
        
        $this->_getWriteAdapter()->raw_query("        
          INSERT INTO `{$this->getMainTable()}` VALUES {$valuesStr}       
        ");
          
    }    

}
