<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-09T12:56:06+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Log.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Log extends Xtento_TrackingImport_Block_Adminhtml_Log_Grid
{
    protected function _getProfile()
    {
        return Mage::registry('tracking_import_profile') ? Mage::registry('tracking_import_profile') : Mage::getModel('xtento_trackingimport/profile')->load($this->getRequest()->getParam('id'));
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->joinLeft(array('profile' => $collection->getTable('xtento_trackingimport/profile')), 'main_table.profile_id = profile.profile_id', array('concat(profile.name," (ID: ", profile.profile_id,")") as profile', 'profile.entity', 'profile.name'));
        $collection->addFieldToFilter('profile.profile_id', $this->_getProfile()->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        unset($this->_columns['profile']);
        foreach ($this->_columns as $key => $column) {
            if ($key == 'log_id') {
                continue;
            }
            // Rename column IDs so they're not posted to the profile information
            $column->setId('col_' . $column->getId());
            $this->_columns['col_' . $key] = $column;
            unset($this->_columns[$key]);
        }
    }

    protected function _prepareMassaction()
    {
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/logGrid', array('_current' => true));
    }
}