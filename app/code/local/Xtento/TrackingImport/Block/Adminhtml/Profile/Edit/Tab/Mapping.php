<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-23T21:16:50+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Mapping.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Mapping extends Xtento_TrackingImport_Block_Adminhtml_Widget_Tab
{
    protected function getFormMessages()
    {
        $formMessages = array();
        $profile = Mage::registry('tracking_import_profile');
        $formMessages[] = array('type' => 'notice', 'message' => Mage::helper('xtento_trackingimport')->__('This is the import processor for imported %s files. Your import format needs to be mapped to Magento fields here.', Mage::helper('xtento_trackingimport/import')->getProcessorName($profile->getProcessor())));
        return $formMessages;
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $profile = Mage::registry('tracking_import_profile');

        $fieldset = $form->addFieldset('settings', array('legend' => Mage::helper('xtento_trackingimport')->__('File Settings'), 'class' => 'fieldset-wide',));
        $fieldset->addField('mapping_note', 'note', array(
            'text' => Mage::helper('xtento_trackingimport')->__('<strong>Notice</strong>: Please make sure to visit our <a href="http://support.xtento.com/wiki/Magento_Extensions:Tracking_Number_Import_Module" target="_blank">support wiki</a> for an explanation on how to set up this processor.')
        ));

        if ($profile->getProcessor() == Xtento_TrackingImport_Model_Import::PROCESSOR_CSV) {
            $fieldset->addField('skip_header', 'select', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Skip header line'),
                'name' => 'skip_header',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
                'note' => Mage::helper('xtento_trackingimport')->__('IMPORTANT: Set this to "Yes" if you want to skip the first line of each imported CSV file as it\'s the header line containing the column names.')
            ));

            $fieldset->addField('delimiter', 'text', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Field Delimiter'),
                'name' => 'delimiter',
                'note' => Mage::helper('xtento_trackingimport')->__('REQUIRED: Set the field delimiter (one character only). Example field delimiter: ;<br/>Hint: If you want to use a tab delimited file enter: \t'),
                'required' => true
            ));

            $fieldset->addField('enclosure', 'text', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Field Enclosure Character'),
                'name' => 'enclosure',
                'maxlength' => 1,
                'note' => Mage::helper('xtento_trackingimport')->__('Set the field enclosure character (<b>one</b> character only). Example: "')
            ));
        }

        if ($profile->getProcessor() == Xtento_TrackingImport_Model_Import::PROCESSOR_XML) {
            $fieldset->addField('xpath_data', 'text', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Data XPath'),
                'name' => 'xpath_data',
                'note' => Mage::helper('xtento_trackingimport')->__('Set the XPath for the node containing the order updates.<br/><br/>Example XML file:<br/>&lt;items&gt;<br/>&lt;item&gt;<br/>...<br/>&lt;/item&gt;<br/>&lt;item&gt;<br/>...<br/>&lt;/item&gt;<br/><br/>&lt;/items&gt;<br/>The order updates would be located in each "item" node, which are located in the "items" node, so the XPath would be: //items/item<br/><br/>Every "item" node located under the "items" node would be processed then.'),
                'required' => true
            ));
        }

        $profile = Mage::registry('tracking_import_profile');
        $form->setValues($profile->getConfiguration());
        $this->setForm($form);
        $this->setTemplate('xtento/trackingimport/profile/mapping.phtml');
        return parent::_prepareForm();
    }

    protected function getMappingHtml()
    {
        $model = Mage::registry('tracking_import_profile');
        $form = $this->getForm();
        $mapping = $form->addField('mapping', 'text', array('label' => '', 'name' => 'mapping'));
        $form->setValues($model->getConfiguration());
        $block = Mage::getBlockSingleton('xtento_trackingimport/adminhtml_profile_edit_tab_mapping_mapper');
        return $block->render($mapping);
    }
}