<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-06T14:10:12+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit/Tab/Configuration.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit_Tab_Configuration extends Xtento_TrackingImport_Block_Adminhtml_Widget_Tab
{
    protected function _prepareForm()
    {
        $model = Mage::registry('source');
        // Set default values
        if (!$model->getId()) {
            $model->setEnabled(1);
        }

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('xtento_trackingimport')->__('Source Settings'),
        ));

        if ($model->getId()) {
            $fieldset->addField('source_id', 'hidden', array(
                'name' => 'source_id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Name'),
            'name' => 'name',
            'required' => true,
            'note' => Mage::helper('xtento_trackingimport')->__('Assign a name to identify this source in logs/profiles.')
        ));

        if ($model->getId()) {
            $typeNote = 'Changing the source type will reload the page.';
        } else {
            $typeNote = '';
        }

        $fieldset->addField('type', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Source Type'),
            'name' => 'type',
            'options' => array_merge(array('' => Mage::helper('xtento_trackingimport')->__('--- Please Select ---')), Mage::getModel('xtento_trackingimport/system_config_source_source_type')->toOptionArray()),
            'required' => true,
            'onchange' => ($model->getId()) ? 'if (this.value==\'\') { return false; } editForm.submitUrl = $(\'edit_form\').action+\'continue/edit/switch/true\'; editForm._submit();' : '',
            'note' => Mage::helper('xtento_trackingimport')->__($typeNote)
        ));

        if (!Mage::registry('source') || !Mage::registry('source')->getId()) {
            $fieldset->addField('continue_button', 'note', array(
                'text' => $this->getChildHtml('continue_button'),
            ));
        }

        if ($model->getId()) {
            $fieldset->addField('status', 'text', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Status'),
                'name' => 'status',
                'disabled' => true,
            ));
            $model->setStatus(Mage::helper('xtento_trackingimport')->__('Used in %d profile(s)', count($model->getProfileUsage())));

            $fieldset->addField('last_result_message', 'textarea', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Last Result Message'),
                'name' => 'last_result_message_dis',
                'disabled' => true,
                'style' => 'height: 90px',
            ));

            $this->_addFieldsForType($form, $model->getType());
        }

        $form->setValues($model->getData());

        return parent::_prepareForm();
    }

    private function _addFieldsForType($form, $type)
    {
        return Mage::getBlockSingleton('xtento_trackingimport/adminhtml_source_edit_tab_type_' . $type)->getFields($form);
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