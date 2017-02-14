<?php

class CommerceExtensions_Sitetransferimportexport_Block_System_Convert_Gui extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'system_convert_gui';
        $this->_blockGroup = 'sitetransferimportexport';
        
        $this->_headerText = Mage::helper('sitetransferimportexport')->__('Profiles');
        $this->_addButtonLabel = Mage::helper('sitetransferimportexport')->__('Add New Profile');

        parent::__construct();
    }
}