<?php

class Pektsekye_PartFinder_Model_Mysql4_Selector_Level_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/selector_level');
    }

}
