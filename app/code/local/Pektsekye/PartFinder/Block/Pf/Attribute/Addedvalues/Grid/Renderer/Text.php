<?php

class Pektsekye_PartFinder_Block_Pf_Attribute_Addedvalues_Grid_Renderer_Text extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {  
        $attributeIds = $row->getData($this->getColumn()->getIndex());

        return count(explode(',', $attributeIds));

    }

}
