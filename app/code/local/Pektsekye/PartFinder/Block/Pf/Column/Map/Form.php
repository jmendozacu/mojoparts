<?php

class Pektsekye_PartFinder_Block_Pf_Column_Map_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('attribute_add_form');
        $this->setTitle(Mage::helper('catalog')->__('Map column to attribute'));
    }
    
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form(array(
                                      'id' => 'edit_form',
                                      'action' => $this->getUrl('*/*/apply'),
                                      'method' => 'post'
                                   )
      );
      
      $fieldset = $form->addFieldset('partfinder_form', array('legend'=>Mage::helper('partfinder')->__('Column mapping options')));

      $fieldset->addField('column_name', 'select', array(
          'label'     => $this->__('Free column in PartFinder Db.'),
          'required'  => true,
          'name'      => 'column_name',
          'values'    => Mage::getModel('partfinder/db')->getFreeDbColumnsAsOptions()          
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
