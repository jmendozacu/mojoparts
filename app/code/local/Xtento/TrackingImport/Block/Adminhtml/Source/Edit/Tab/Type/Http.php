<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit/Tab/Type/Http.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit_Tab_Type_Http
{
    // HTTP Configuration
    public function getFields($form)
    {
        $fieldset = $form->addFieldset('config_fieldset', array(
            'legend' => Mage::helper('xtento_trackingimport')->__('HTTP Configuration'),
            'class' => 'fieldset-wide'
        ));

        $fieldset->addField('http_note', 'note', array(
            'text' => Mage::helper('xtento_trackingimport')->__('<b>Instructions</b>: To import data to a HTTP server, please follow the following steps:<br>1) Go into the <i>app/code/local/Xtento/TrackingImport/Model/Source/</i> directory and rename the file "Http.php.sample" to "Http.php"<br>2) Enter the function name you want to call in the Http.php class in the field below.<br>3) Open the Http.php file and add a function that matches the function name you entered. This function will be called by this source upon importing then.<br><br><b>Example:</b> If you enter server1 in the function name field below, a method called server1($fileArray) must exist in the Http.php file. This way multiple HTTP servers can be added to the HTTP class, and can be called from different import source, separated by the function name that is called. The function you add then gets called whenever this source is executed by an import profile.')
        ));

        $fieldset->addField('custom_function', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Custom Function'),
            'name' => 'custom_function',
            'note' => Mage::helper('xtento_trackingimport')->__('Please make sure the function you enter exists like this in the app/code/local/Xtento/TrackingImport/Model/Source/Http.php file:<br>public function <i>yourFunctionName</i>($fileArray) { ... }'),
            'required' => true
        ));
    }
}