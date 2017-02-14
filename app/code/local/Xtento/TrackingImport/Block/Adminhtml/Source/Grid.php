<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Grid.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('source_id');
        $this->setId('xtento_trackingimport_source_grid');
        $this->setDefaultDir('asc');
        $this->setUseAjax(true);
    }

    protected function _getCollectionClass()
    {
        return 'xtento_trackingimport/source_collection';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('source_id',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Source ID'),
                'width' => '50px',
                'index' => 'source_id',
                'type' => 'number'
            )
        );

        $this->addColumn('type',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Type'),
                'index' => 'type',
                'type' => 'options',
                'options' => Mage::getSingleton('xtento_trackingimport/system_config_source_source_type')->toOptionArray(),
            )
        );

        $this->addColumn('name',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Name'),
                'index' => 'name'
            )
        );

        $this->addColumn('configuration',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Configuration'),
                'index' => 'source_id',
                'filter' => false,
                'renderer' => 'xtento_trackingimport/adminhtml_source_grid_renderer_configuration',
                'width' => '180px'
            )
        );

        $this->addColumn('status',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Status'),
                'index' => 'source_id',
                'filter' => false,
                'renderer' => 'xtento_trackingimport/adminhtml_source_grid_renderer_status',
            )
        );

        $this->addColumn('last_result',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Last Result'),
                'index' => 'last_result',
                'type' => 'options',
                'options' => array(
                    0 => Mage::helper('xtento_trackingimport')->__('Failed'),
                    1 => Mage::helper('xtento_trackingimport')->__('Success'),
                ),
                'renderer' => 'xtento_trackingimport/adminhtml_source_grid_renderer_result',
            )
        );

        $this->addColumn('last_result_message',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Last Result Message'),
                'index' => 'last_result_message',
                'type' => 'text'
            )
        );

        $this->addColumn('last_modification',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Last Modification'),
                'index' => 'last_modification',
                'type' => 'datetime'
            )
        );

        $this->addColumn('action',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('xtento_trackingimport')->__('Edit Source'),
                        'url' => array('base' => '*/trackingimport_source/edit'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
            ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('source_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        $this->getMassactionBlock()->setFormFieldName('source');
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}