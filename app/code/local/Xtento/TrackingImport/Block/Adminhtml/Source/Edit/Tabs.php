<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit/Tabs.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('source_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('xtento_trackingimport')->__('Import Source'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Source Configuration'),
            'title' => Mage::helper('xtento_trackingimport')->__('Source Configuration'),
            'content' => $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_source_edit_tab_configuration')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}