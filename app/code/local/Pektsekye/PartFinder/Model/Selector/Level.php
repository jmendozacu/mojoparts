<?php

class Pektsekye_PartFinder_Model_Selector_Level extends Mage_Core_Model_Abstract
{                  
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/selector_level');
    }

        
    public function deleteLevels()
    {    
      $this->getResource()->emptyTable();
    }    

}
