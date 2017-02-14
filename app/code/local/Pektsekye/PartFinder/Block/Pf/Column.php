<?php
class Pektsekye_PartFinder_Block_Pf_Column extends Mage_Adminhtml_Block_Widget_Grid_Container
{

  public function __construct()
  {
    $this->_controller = 'pf_column';
    $this->_blockGroup = 'partfinder';
    $this->_headerText = Mage::helper('partfinder')->__('Mapped Columns');

    parent::__construct();
    
    $this->_updateButton('add', 'label', $this->__('Map Column'));     
    $this->_updateButton('add', 'onclick', 'setLocation(\'' . $this->getUrl('*/pf_column/mapColumn') .'\')');  
        
  }

      
}
