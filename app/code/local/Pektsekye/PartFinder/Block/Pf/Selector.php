<?php

class Pektsekye_PartFinder_Block_Pf_Selector extends Mage_Adminhtml_Block_Widget
{ 

    protected $_levelCollection;
    
    public function getPfDatabaseExists()
    {    
      return Mage::getModel('partfinder/db')->getPfDatabaseExists();      
    }


    public function getDbColumnNames()
    {
      return Mage::getModel('partfinder/db')->getDbColumnNames();
    }


    public function getSelectorExists()
    {    
      return $this->getLevelCollection()->getSize() > 0;
    }


    public function getLevelCollection()
    {      
       if (!isset($this->_levelCollection))
          $this->_levelCollection = Mage::getModel('partfinder/selector_level')->getCollection();
          
       return $this->_levelCollection; 
    }

    
    public function getSubmitUrl()
    {
      return $this->getUrl('*/*/create');
    }   
     
    public function getDeleteUrl()
    {
      return $this->getUrl('*/*/delete');
    }     
    
    public function getSaveUrl()
    {
      return $this->getUrl('*/*/save');
    }
    
}
