<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit/Tab/Type/Httpdownload.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit_Tab_Type_Httpdownload
{
    // HTTP Configuration
    public function getFields($form)
    {
        $fieldset = $form->addFieldset('config_fieldset', array(
            'legend' => Mage::helper('xtento_trackingimport')->__('HTTP Download Configuration'),
            'class' => 'fieldset-wide'
        ));

        $fieldset->addField('http_note', 'note', array(
            'text' => Mage::helper('xtento_trackingimport')->__('<b>Instructions</b>: This source is able to download files from a HTTP server. Please supply an URL in the following format: <b></b>http://www.url.com/file.csv</b> - It can be any url with any valid path/filename that exists on the remote webserver. To provide a username/password in the URL, please use: <b>http://username:password@www.url.com/file.csv</b>')
        ));

        $fieldset->addField('custom_function', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('URL'),
            'name' => 'custom_function',
            'required' => true
        ));
    }
}