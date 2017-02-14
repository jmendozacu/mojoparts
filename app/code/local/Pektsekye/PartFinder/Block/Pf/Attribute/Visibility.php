<?php
class Pektsekye_PartFinder_Block_Pf_Attribute_Visibility extends Mage_Adminhtml_Block_Widget_Grid_Container
{

  public function __construct()
  {
    $this->_controller = 'pf_attribute_visibility';
    $this->_blockGroup = 'partfinder';
    $this->_headerText = Mage::helper('partfinder')->__('Hidden Attributes');         
    $this->_addButtonLabel = Mage::helper('partfinder')->__('Make Attribute Hidden');               
    parent::__construct();

    
  }

      
}
