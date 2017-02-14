<?php
class Pektsekye_PartFinder_Block_Pf_Attribute_Addedvalues extends Mage_Adminhtml_Block_Widget_Grid_Container
{

  public function __construct()
  {
    $this->_controller = 'pf_attribute_addedvalues';
    $this->_blockGroup = 'partfinder';
    $this->_headerText = Mage::helper('partfinder')->__('Attributes with added values');         
    $this->_addButtonLabel = Mage::helper('partfinder')->__('Add Values');
    
    parent::__construct();
    
  }

      
}
