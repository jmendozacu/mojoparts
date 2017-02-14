<?php

class Pektsekye_PartFinder_Block_Pf_Attribute_Visibility_Hide_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('attribute_add_form');
        $this->setTitle(Mage::helper('partfinder')->__('Make attribute hidden'));
    }
    
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form(array(
                                      'id' => 'edit_form',
                                      'action' => $this->getUrl('*/*/save'),
                                      'method' => 'post'
                                   )
      );
      
      $fieldset = $form->addFieldset('partfinder_form', array('legend'=>Mage::helper('partfinder')->__('Choose an attribute to hide')));

      
      $fieldset->addField('attribute', 'select', array(
          'label'     => $this->__('Attribute'),
          'required'  => true,
          'name'      => 'attribute_id',
          'values'    => Mage::getModel('partfinder/attribute_visibility')->getVisibleAttributesAsOptions()         
      ));  
      



      $form->setUseContainer(true);
      $this->setForm($form);
      
      return parent::_prepareForm();
  }
}
