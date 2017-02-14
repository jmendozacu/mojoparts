<?php

class Pektsekye_PartFinder_Block_Pf_Attribute_Visibility_Grid extends Mage_Adminhtml_Block_Widget_Grid
{


  public function __construct()
  {
      parent::__construct();
      $this->setId('attribute_visibility_grid');
  }



  protected function _prepareCollection()
  {           
      $collection = Mage::getModel('partfinder/attribute_visibility')->getCollection()
            ->addAttributeLabel();            
            
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }



  protected function _prepareColumns()
  {

      $this->addColumn('attribute_label', array(
          'header'    => Mage::helper('partfinder')->__('Attribute'),
          'align'     => 'left', 	
          'width'     => '400px',          
          'filter'    => false,   
          'sortable'  => false,                    
          'index'     => 'attribute_label'
      ));
      
      
      $this->addColumn('action',
          array(
              'header'    => Mage::helper('catalog')->__('Action'),
              'width'     => '100px',
              'type'      => 'action',
              'getter'    => 'getId',
              'actions'   => array(array(
                  'caption'   => Mage::helper('partfinder')->__('Make Visible'),
                  'url'       => array(
                      'base'=>'*/*/delete'
                  ),
                  'field'   => 'id'
              )),
              'filter'    => false,
              'sortable'  => false
      ));     
      
      return parent::_prepareColumns();
  }



  protected function _prepareMassaction()
  {
      $this->setMassactionIdField('id');
      $this->getMassactionBlock()->setFormFieldName('ids');

      $this->getMassactionBlock()->addItem('delete', array(
           'label'    => Mage::helper('partfinder')->__('Make Visible'),
           'url'      => $this->getUrl('*/*/massDelete')
      )); 	      
      return $this;
  }


}
