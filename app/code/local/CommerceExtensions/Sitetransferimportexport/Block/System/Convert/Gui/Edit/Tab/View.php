<?php

class CommerceExtensions_Sitetransferimportexport_Block_System_Convert_Gui_Edit_Tab_View extends Mage_Adminhtml_Block_Widget_Form
{
    public function initForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('profile_');

        $model = Mage::registry('current_convert_profile');

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('sitetransferimportexport')->__('View Actions XML'),
            'class' => 'fieldset-wide'
        ));

        $fieldset->addField('actions_xml', 'textarea', array(
            'name' => 'actions_xml_view',
            'label' => Mage::helper('sitetransferimportexport')->__('Actions XML'),
            'title' => Mage::helper('sitetransferimportexport')->__('Actions XML'),
            'style' => 'height:30em'
        ));

        $form->setValues($model->getData());

        $this->setForm($form);

        return $this;
    }

}

