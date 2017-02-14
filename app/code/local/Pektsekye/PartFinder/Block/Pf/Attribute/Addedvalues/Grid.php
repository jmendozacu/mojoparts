<?php

class Pektsekye_PartFinder_Block_Pf_Attribute_Addedvalues_Grid extends Mage_Adminhtml_Block_Widget_Grid
{


  public function __construct()
  {
      parent::__construct();
      $this->setId('attribute_grid');
  }



  protected function _prepareCollection()
  {           
      $collection = Mage::getModel('partfinder/attribute_addedvalues')->getCollection()
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


      $this->addColumn('value_ids', array(
          'header'    => Mage::helper('partfinder')->__('Added values'),         
          'align'     => 'left', 	   
          'filter'    => false,   
          'sortable'  => false,                    
          'index'     => 'value_ids',
          'renderer'  => 'partfinder/pf_attribute_addedvalues_grid_renderer_text'          
      ));     
      
      $this->addColumn('action',
          array(
              'header'    => Mage::helper('catalog')->__('Action'),
              'width'     => '100px',
              'type'      => 'action',
              'getter'    => 'getId',
              'actions'   => array(array(
                  'caption'   => Mage::helper('partfinder')->__('Delete Values'),
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
           'label'    => Mage::helper('adminhtml')->__('Delete'),
           'url'      => $this->getUrl('*/*/massDelete')
      )); 		
      
      return $this;
  }


}
