<?php

class Pektsekye_PartFinder_Model_Db extends Mage_Core_Model_Abstract
{                  
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/db');
    }
    
    
    public function getColumnValues($columnName)
    {
      $columnValues = $this->getResource()->getColumnValues($columnName);

      return array_unique($columnValues);
    }



    public function getRowIds($columns)
    {
      $rowIds = array();
      foreach ($columns as $column){    
        $values = $this->getResource()->getColumnValues($column); 
        foreach ($values as $rowId => $value)
          $rowIds[$value][] = $rowId;          
      }
      
      return $rowIds;
    }

    
    
    public function getPfDatabaseExists()
    {
      return $this->getResource()->getPfDatabaseExists();
    }




    public function getDbColumnNames()
    { 
      $columns = $this->getResource()->getDbColumnNames();
      
      if (count($columns) == 0)
        return array();
        
        //remove the first id column
      unset($columns[0]); 
      
       //remove pf_ prefix   
      foreach ($columns as $k => $column)
        $columns[$k] = substr($column, 3);
      
      
      return $columns;
    }     

    
    
    
    public function getUsedColumns()
    {     
      $selectorColumns = Mage::getResourceModel('partfinder/selector_level')->getColumnNames();      
      $mappedColumns   = Mage::getResourceModel('partfinder/column')->getColumnNames(); 
    
      return array_merge($selectorColumns, $mappedColumns);
    }
    
    
    

    public function getFreeDbColumnsAsOptions()
    {
      return Mage::helper('partfinder')->toOptions(array_diff($this->getDbColumnNames(), $this->getUsedColumns()));
    }
    
    
    
    public function getMappingColumnsAsOptions()
    {
      $selectorColumns = Mage::getResourceModel('partfinder/selector_level')->getColumnNames();  
    
      $columns = array_diff($this->getDbColumnNames(), $selectorColumns);    

      return Mage::helper('partfinder')->toOptions($columns);
    }    

   
}
