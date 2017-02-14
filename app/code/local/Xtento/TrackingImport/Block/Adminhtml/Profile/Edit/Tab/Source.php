<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Source.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Source extends Xtento_TrackingImport_Block_Adminhtml_Source_Grid
{
    protected function getFormMessages()
    {
        $formMessages = array();
        $formMessages[] = array('type' => 'notice', 'message' => Mage::helper('xtento_trackingimport')->__('Import sources are local directories, FTP/SFTP/HTTP servers or webservices where imported files are downloaded from. If you just want to import manually, no import source must be set up. Please click <a href="'.Mage::helper('adminhtml')->getUrl('*/trackingimport_source').'" target="_blank">here</a> to add new import sources.'));
        return $formMessages;
    }

    protected function _getProfile()
    {
        return Mage::getModel('xtento_trackingimport/profile')->load($this->getRequest()->getParam('id'));
    }

    protected function _prepareColumns()
    {
        $this->addColumn('col_sources', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'col_sources',
            'values' => $this->getSelectedSources(),
            'align' => 'center',
            'index' => 'source_id'
        ));

        parent::_prepareColumns();
        unset($this->_columns['action']);
        foreach ($this->_columns as $key => $column) {
            if ($key == 'source_id' || $key == 'col_sources') {
                continue;
            }
            // Rename column IDs so they're not posted to the profile information
            $column->setId('col_'.$column->getId());
            $this->_columns['col_'.$key] = $column;
            unset($this->_columns[$key]);
        }

        $this->addColumn('action',
            array(
                'header' => Mage::helper('xtento_trackingimport')->__('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('xtento_trackingimport')->__('Edit Source'),
                        'url' => array('base' => '*/trackingimport_source/edit'),
                        'field' => 'id',
                        'target' => '_blank'
                    )
                ),
                'filter' => false,
                'sortable' => false,
            )
        );
    }

    protected function _prepareMassaction()
    {
    }

    public function getRowUrl($row)
    {
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/sourceGrid', array('_current' => true));
    }

    public function getSelectedSources()
    {
        $array = explode("&", $this->_getProfile()->getSourceIds());
        return $array;
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