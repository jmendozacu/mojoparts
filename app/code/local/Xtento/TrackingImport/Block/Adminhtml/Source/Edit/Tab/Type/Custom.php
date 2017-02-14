<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit/Tab/Type/Custom.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit_Tab_Type_Custom
{
    // Custom Type Configuration
    public function getFields($form)
    {
        $fieldset = $form->addFieldset('config_fieldset', array(
            'legend' => Mage::helper('xtento_trackingimport')->__('Custom Type Configuration'),
            'class' => 'fieldset-wide'
        ));

        $fieldset->addField('custom_class', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Custom Class Identifier'),
            'name' => 'custom_class',
            'note' => Mage::helper('xtento_trackingimport')->__('You can set up an own class in our (or another) module which gets called when importing. The loadFiles() function would be called in your class. If your class was called Xtento_TrackingImport_Model_Source_Myclass then the identifier to enter here would be xtento_trackingimport/source_myclass<br/><br/>The loadFiles() function needs to return an array like this: array(array(\'source_id\' => $this->getSource()->getId(), \'filename\' => $filename, \'data\' => $fileContents))'),
            'required' => true
        ));
    }
}