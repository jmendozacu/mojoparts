<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-06T14:10:12+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/General.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_General extends Xtento_TrackingImport_Block_Adminhtml_Widget_Tab
{
    protected function getFormMessages()
    {
        $formMessages = array();
        $model = Mage::registry('tracking_import_profile');
        if ($model->getId() && !$model->getEnabled()) {
            $formMessages[] = array('type' => 'warning', 'message' => Mage::helper('xtento_trackingimport')->__('This profile is disabled. No automatic imports will be made and the profile won\'t show up for manual imports.'));
        }
        return $formMessages;
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('tracking_import_profile');
        // Set default values
        if (!$model->getId()) {
            $model->setEnabled(1);
        }

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('xtento_trackingimport')->__('General Configuration'),
        ));

        if ($model->getId()) {
            $fieldset->addField('profile_id', 'hidden', array(
                'name' => 'profile_id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Name'),
            'name' => 'name',
            'required' => true,
        ));

        if ($model->getId()) {
            $fieldset->addField('enabled', 'select', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Enabled'),
                'name' => 'enabled',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray()
            ));
        }

        $processor = $fieldset->addField('processor', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('File Processor'),
            'name' => 'processor',
            'options' => Mage::getSingleton('xtento_trackingimport/system_config_source_import_processor')->toOptionArray(),
            'required' => true,
            'note' => Mage::helper('xtento_trackingimport')->__('This setting can\'t be changed after creating the profile. Add a new profile for different import types.')
        ));

        $entity = $fieldset->addField('entity', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Import Type'),
            'name' => 'entity',
            'options' => Mage::getSingleton('xtento_trackingimport/system_config_source_import_entity')->toOptionArray(),
            'required' => true,
            'note' => Mage::helper('xtento_trackingimport')->__('This setting can\'t be changed after creating the profile. Add a new profile for different import types.')
        ));
        if ($model->getId() && !Mage::getSingleton('adminhtml/session')->getProfileDuplicated()) {
            // 1.3 Compatibility. Does not accept the disabled param directly in the addField array.
            $entity->setDisabled(true);
            $processor->setDisabled(true);
        }

        if (!Mage::registry('tracking_import_profile') || !Mage::registry('tracking_import_profile')->getId()) {
            $fieldset->addField('continue_button', 'note', array(
                'text' => $this->getChildHtml('continue_button'),
            ));
        }
/*
        if (Mage::registry('tracking_import_profile') && Mage::registry('tracking_import_profile')->getId()) {
            $fieldset = $form->addFieldset('advanced_fieldset', array(
                'legend' => Mage::helper('xtento_trackingimport')->__('Import Settings'),
                'class' => 'fieldset-wide',
            ));

            $fieldset->addField('save_files_local_copy', 'select', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Save local copies of imports'),
                'name' => 'save_files_local_copy',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
                'note' => Mage::helper('xtento_trackingimport')->__('If set to yes, local copies of the imported files will be saved in the ./var/import_bkp/ folder. If set to no, you won\'t be able to download old import files from the import/execution log.')
            ));
        }
*/
        $form->setValues($model->getData());

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->setChild('continue_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'label' => Mage::helper('xtento_trackingimport')->__('Continue'),
                'onclick' => "saveAndContinueEdit()",
                'class' => 'save'
            ))
        );
        return parent::_prepareLayout();
    }
}