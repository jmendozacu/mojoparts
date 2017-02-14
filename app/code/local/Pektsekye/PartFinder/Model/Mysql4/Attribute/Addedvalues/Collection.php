<?php

class Pektsekye_PartFinder_Model_Mysql4_Attribute_Addedvalues_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/attribute_addedvalues');
    }

    public function addAttributeLabel()
    {
      $this->getSelect()
        ->join(array('eav_attribute_table'=>$this->getTable('eav/attribute')),
                  '`main_table`.attribute_id=`eav_attribute_table`.attribute_id', array('attribute_label' => 'frontend_label'));
      return $this;
    }    

}
