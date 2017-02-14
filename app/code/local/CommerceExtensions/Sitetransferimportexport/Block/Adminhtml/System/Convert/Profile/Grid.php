<?php

class CommerceExtensions_Sitetransferimportexport_Block_Adminhtml_System_Convert_Profile_Grid extends Mage_Adminhtml_Block_System_Convert_Profile_Grid
{
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('dataflow/profile_collection')
            ->addFieldToFilter('entity_type', array('null'=>''))
            ->addFieldToFilter('is_commerce_extensions', 0);

        $this->setCollection($collection);
    }
}

