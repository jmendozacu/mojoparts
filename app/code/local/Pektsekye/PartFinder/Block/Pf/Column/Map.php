<?php

class Pektsekye_PartFinder_Block_Pf_Column_Map extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'partfinder';        
        $this->_controller = 'pf_column';
        $this->_mode = 'map';
        
        parent::__construct();
        $this->_removeButton('reset');        
        $this->_updateButton('save', 'label', Mage::helper('partfinder')->__('Apply'));

    }


    public function getHeaderText()
    {
      return Mage::helper('partfinder')->__('Map column to attribute');
    }


    
}
