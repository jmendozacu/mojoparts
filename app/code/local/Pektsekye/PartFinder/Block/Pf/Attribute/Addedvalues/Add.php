<?php

class Pektsekye_PartFinder_Block_Pf_Attribute_Addedvalues_Add extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'partfinder';        
        $this->_controller = 'pf_attribute_addedvalues';
        $this->_mode = 'add';
        
        parent::__construct();
        $this->_removeButton('reset');        
        $this->_updateButton('save', 'label', Mage::helper('partfinder')->__('Add Values'));

    }


    public function getHeaderText()
    {
      return Mage::helper('partfinder')->__('Add values to attribute');
    }


    
}
