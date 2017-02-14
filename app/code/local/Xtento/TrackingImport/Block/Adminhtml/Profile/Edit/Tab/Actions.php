<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-22T16:46:09+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Actions.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Actions extends Xtento_TrackingImport_Block_Adminhtml_Widget_Tab
{
    protected function getFormMessages()
    {
        $formMessages = array();
        $formMessages[] = array('type' => 'notice', 'message' => Mage::helper('xtento_trackingimport')->__('The actions set up below will be applied to all manual and automatic imports, in the sort order set up below.'));
        return $formMessages;
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('tracking_import_profile');

        $form = new Varien_Data_Form();

        /*$fieldset = $form->addFieldset('invoice_settings', array('legend' => Mage::helper('xtento_trackingimport')->__('Import Actions: Invoicing'), 'class' => 'fieldset-wide'));

        $fieldset->addField('invoice_order', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Create invoice for order'),
            'name' => 'invoice_order',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note' => Mage::helper('xtento_trackingimport')->__('If enabled, an invoice will be created for the processed order.'),
            'can_have_conditions' => true
        ));

        // Add "import conditions"
        foreach ($fieldset->getElements() as $field) {
            if ($field->getCanHaveConditions()) {
                $field->setNote($field->getNote() . "<br/>" . Mage::helper('xtento_trackingimport')->__('Click <a href="#" onclick="xtShowActionConditionPopup(\'%s\', \'%s\'); return false;">here</a> to adjust import conditions.', Mage::helper('adminhtml')->getUrl('* /trackingimport_profile/editActionCondition', array('profile_id' => $model->getId(), 'field' => $field->getName())), $field->getLabel()));
            }
        }*/


        $form->setValues($model->getConfiguration());
        $this->setForm($form);
        $this->setTemplate('xtento/trackingimport/profile/action.phtml');

        return parent::_prepareForm();
    }

    protected function getActionHtml()
    {
        $model = Mage::registry('tracking_import_profile');
        $form = $this->getForm();
        $mapping = $form->addField('action', 'text', array('label' => '', 'name' => 'action'));
        $form->setValues($model->getConfiguration());
        $block = Mage::getBlockSingleton('xtento_trackingimport/adminhtml_profile_edit_tab_mapping_action');
        return $block->render($mapping);
    }
}
