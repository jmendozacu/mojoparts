<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-14T22:46:52+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Log/Grid.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function getFormMessages()
    {
        $formMessages = array();
        $formMessages[] = array('type' => 'notice', 'message' => Mage::helper('xtento_trackingimport')->__('Imports get logged here. Use this to easily find failed imports.'));
        return $formMessages;
    }

    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('log_id');
        $this->setId('xtento_trackingimport_log_grid');
        $this->setDefaultDir('desc');
        $this->setUseAjax(true);
    }

    protected function _getCollectionClass()
    {
        return 'xtento_trackingimport/log_collection';
    }

    protected function _prepareCollection()
    {
        if ($this->getCollection()) {
            return parent::_prepareCollection();
        }
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->joinLeft(array('profile' => $collection->getTable('xtento_trackingimport/profile')), 'main_table.profile_id = profile.profile_id', array('concat(profile.name," (ID: ", profile.profile_id,")") as profile', 'profile.entity', 'profile.name'));
        if ($this->getRequest()->getParam('log_id', false) && !$this->getRequest()->getParam('ajax', false) == true) {
            $collection->addFieldToFilter('log_id', $this->getRequest()->getParam('log_id'));
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('log_id',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Import ID'),
                'width' => '50px',
                'index' => 'log_id',
                'type' => 'number'
            )
        );

        $this->addColumn('import_type',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Import Type'),
                'index' => 'import_type',
                'type' => 'options',
                'options' => Mage::getSingleton('xtento_trackingimport/system_config_source_import_type')->toOptionArray(),
                'renderer' => 'xtento_trackingimport/adminhtml_log_grid_renderer_type',
            )
        );

        $this->addColumn('entity',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Entity'),
                'index' => 'entity',
                'type' => 'options',
                'options' => Mage::getSingleton('xtento_trackingimport/system_config_source_import_entity')->toOptionArray()
            )
        );

        $this->addColumn('profile',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Profile'),
                'index' => 'profile',
                'filter_index' => 'name'
            )
        );

        $this->addColumn('files',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Imported Files'),
                'index' => 'files',
                'renderer' => 'xtento_trackingimport/adminhtml_log_grid_renderer_filename',
            )
        );

        $this->addColumn('source_ids',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Sources'),
                'index' => 'source_ids',
                'renderer' => 'xtento_trackingimport/adminhtml_log_grid_renderer_source',
                'filter' => false
            )
        );

        $this->addColumn('records_imported',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Record Count'),
                'index' => 'records_imported',
                'type' => 'number'
            )
        );

        $this->addColumn('result',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Result'),
                'index' => 'result',
                'type' => 'options',
                'options' => Mage::getSingleton('xtento_trackingimport/system_config_source_log_result')->toOptionArray(),
                'renderer' => 'xtento_trackingimport/adminhtml_log_grid_renderer_result',
            )
        );

        $this->addColumn('result_message',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Result Message'),
                'index' => 'result_message',
            )
        );

        $this->addColumn('created_at',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Executed At'),
                'index' => 'created_at',
                'type' => 'datetime'
            )
        );

        $this->addColumn('action',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    /*array(
                        'caption' => Mage::helper('xtento_trackingimport')->__('Download file(s)'),
                        'url' => array('base' => '* /trackingimport_log/download'),
                        'field' => 'id'
                    ),*/
                    array(
                        'caption' => Mage::helper('xtento_trackingimport')->__('Delete log'),
                        'url' => array('base' => '*/trackingimport_log/delete'),
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
        $this->setMassactionIdField('log_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        $this->getMassactionBlock()->setFormFieldName('log');

        /*$this->getMassactionBlock()->addItem('download', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Download file(s)'),
            'url' => $this->getUrl('* / * /massDownload')
        ));*/

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('adminhtml')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('adminhtml')->__('Are you sure?')
        ));
    }

    /*public function getRowUrl($row)
    {
        return $this->getUrl('* /trackingimport_log/download', array('id' => $row->getId()));
    }*/

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->getParam('ajax')) {
            return parent::_toHtml();
        }
        return $this->_getFormMessages() . parent::_toHtml();
    }

    protected function _getFormMessages()
    {
        $html = '<div id="messages"><ul class="messages">';
        foreach ($this->getFormMessages() as $formMessage) {
            $html .= '<li class="' . $formMessage['type'] . '-msg"><ul><li><span>' . $formMessage['message'] . '</span></li></ul></li>';
        }
        $html .= '</ul></div>';
        return $html;
    }
}