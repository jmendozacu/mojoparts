<?php

class CommerceExtensions_Productimportexport_Block_System_Convert_Gui_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post', 'enctype' => 'multipart/form-data'));

        $model = Mage::registry('current_convert_profile');

        if ($model->getId())
        {
            $form->addField('profile_id', 'hidden', array(
                'name' => 'profile_id',
            ));
            $form->setValues($model->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
