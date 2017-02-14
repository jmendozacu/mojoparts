<?php

class Pektsekye_PartFinder_Model_Selector extends Mage_Core_Model_Abstract
{    

    const ENTITY = 'partfinder_selector';   
    
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/selector');
    }


                
    public function createSelector($columnNames, $columnOrders)
    {
    
      $columns = array();    
      $isOrdered = false; 

      foreach ($columnNames as $k => $columnName){
        $columns[$k]['name']  = $columnName;
        $columns[$k]['order'] = (int) $columnOrders[$columnName];
        if ($columns[$k]['order'] > 0) 
          $isOrdered = true;         
      }
      
      
      if ($isOrdered){
      
        usort($columns, array($this, "sortColumns"));
        
        $columnNames = array();
        foreach ($columns as $column)
          $columnNames[] = $column['name'];
      }
      
      
      $this->getResource()->createSelector($columnNames);

      $level = Mage::getModel('partfinder/selector_level');
      foreach ($columnNames as $k => $column){
        $level->setId(null);
        $level->setLevel($k);        
        $level->setColumnName($column);
        $level->setOptionTitle(Mage::helper('partfinder')->__('-- select --'));
        $level->setUrlParameter($column);        
        $level->save();        
      }
      
      Mage::getSingleton('index/indexer')->processEntityAction(
          $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
      );      
    }




    public function recreateSelector()
    {    
      $this->getResource()->emptyTable();
 
      $columns = Mage::getResourceModel('partfinder/selector_level')->getColumnNames();        
      $this->getResource()->createSelector($columns);    
    }
    
    
    
    
    public function deleteSelector()
    {    
      $this->getResource()->emptyTable();
      Mage::getModel('partfinder/selector_level')->deleteLevels();     
    }



     public function fetchRowId($values)     
    {
        return $this->getResource()->fetchRowId($values);      
    }



     public function fetchColumnValues($values = null)     
    { 
if ($values == null) {
return null;
}
        return $this->getResource()->fetchColumnValues($values);        
    }


     
	  public function sortColumns($a, $b)
	  {
      if ($a['order'] == $b['order'])
          return 0;
      return ($a['order'] < $b['order']) ? -1 : 1;
	  }     

}
