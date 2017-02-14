<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-24T22:40:39+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tabs.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('profile_tabs');
        $this->setDestElementId('edit_form');
        if (!Mage::registry('tracking_import_profile')) {
            $this->setTitle(Mage::helper('xtento_trackingimport')->__('Import Profile'));
        } else {
            $this->setTitle(Mage::helper('xtento_trackingimport')->__('%s Profile', Mage::helper('xtento_trackingimport/entity')->getEntityName(Mage::registry('tracking_import_profile')->getEntity())));
        }
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general', array(
            'label' => Mage::helper('xtento_trackingimport')->__('General Configuration'),
            'title' => Mage::helper('xtento_trackingimport')->__('General Configuration'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_general')->toHtml(),
        ));

        if (!Mage::registry('tracking_import_profile') || !Mage::registry('tracking_import_profile')->getId()) {
            // We just want to display the "General" tab to set the import entity for new profiles
            return parent::_beforeToHtml();
        }

        $this->addTab('settings', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Settings & Filters'),
            'title' => Mage::helper('xtento_trackingimport')->__('Settings & Filters'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_settings')->toHtml(),
        ));

        $this->addTab('mapping', array(
            'label' => Mage::helper('xtento_trackingimport')->__('File Mapping'),
            'title' => Mage::helper('xtento_trackingimport')->__('File Mapping'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_mapping')->toHtml(),
        ));

        $this->addTab('actions', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Actions'),
            'title' => Mage::helper('xtento_trackingimport')->__('Actions'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_actions')->toHtml(),
        ));

        $this->addTab('source', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Import Sources'),
            'title' => Mage::helper('xtento_trackingimport')->__('Import Sources'),
            'url' => $this->getUrl('*/*/source', array('_current' => true)),
            'class' => 'ajax',
        ));

        $this->addTab('automatic', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Automatic Import'),
            'title' => Mage::helper('xtento_trackingimport')->__('Automatic Import'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_automatic')->toHtml(),
        ));

        $this->addTab('log', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Profile Execution Log'),
            'title' => Mage::helper('xtento_trackingimport')->__('Profile Execution Log'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_log')->toHtml(),
        ));

        /*$this->addTab('history', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Profile Import History'),
            'title' => Mage::helper('xtento_trackingimport')->__('Profile Import History'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_history')->toHtml(),
        ));*/

        return parent::_beforeToHtml();
    }
}