<?php

class Pektsekye_PartFinder_Block_Pf_Attribute_Addedvalues_Add_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('attribute_add_form');
        $this->setTitle(Mage::helper('partfinder')->__('Add values to attribute'));
    }
    
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form(array(
                                      'id' => 'edit_form',
                                      'action' => $this->getUrl('*/*/save'),
                                      'method' => 'post'
                                   )
      );
      
      $fieldset = $form->addFieldset('partfinder_form', array('legend'=>Mage::helper('partfinder')->__('Add values to attribute')));


      $fieldset->addField('column_name', 'select', array(
          'label'     => $this->__('Column in PartFinder Db.'),
          'required'  => true,
          'name'      => 'column_name',
          'values'    => Mage::getModel('partfinder/db')->getMappingColumnsAsOptions()          
      ));
      
      $fieldset->addField('attribute', 'select', array(
          'label'     => $this->__('Attribute'),
          'required'  => true,
          'name'      => 'attribute_id',
          'values'    => Mage::getModel('partfinder/attribute')->getAttributesAsOptions()         
      ));  
      



      $form->setUseContainer(true);
      $this->setForm($form);
      
      return parent::_prepareForm();
  }
}
