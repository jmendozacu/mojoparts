<?php

class Pektsekye_PartFinder_Block_Pf_Column_Grid extends Mage_Adminhtml_Block_Widget_Grid
{


  public function __construct()
  {
      parent::__construct();
      $this->setId('column_grid');
  }



  protected function _prepareCollection()
  {
      $collection = Mage::getModel('partfinder/column')->getCollection()
            ->addAttributeLabel();
            
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }



  protected function _prepareColumns()
  {
  
      $this->addColumn('column_name', array(
          'header'    => Mage::helper('partfinder')->__('Column name in PartFinder Db.'),
          'align'     => 'left',
          'width'     => '400px',
          'index'     => 'column_name',
          'filter'    => false,   
          'sortable'  => false           
      ));
       
      $this->addColumn('attribute_label', array(
          'header'    => Mage::helper('partfinder')->__('Attribute'),
          'align'     => 'left', 	
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
                  'caption'   => Mage::helper('adminhtml')->__('Delete'),
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
