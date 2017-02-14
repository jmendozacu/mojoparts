<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-24T22:40:39+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Settings.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Settings extends Xtento_TrackingImport_Block_Adminhtml_Widget_Tab
{
    protected function getFormMessages()
    {
        $formMessages = array();
        $formMessages[] = array('type' => 'notice', 'message' => Mage::helper('xtento_trackingimport')->__('The settings specified below will be applied to all manual and automatic imports.'));
        return $formMessages;
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('tracking_import_profile');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('settings', array('legend' => Mage::helper('xtento_trackingimport')->__('Import Settings'), 'class' => 'fieldset-wide',));

        $fieldset->addField('order_identifier', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Order Identifier'),
            'name' => 'order_identifier',
            'values' => Mage::getSingleton('xtento_trackingimport/system_config_source_order_identifier')->toOptionArray(),
            'note' => Mage::helper('xtento_trackingimport')->__('This is what is called the Order Identifier in the import settings and is what\'s used to identify the orders in the import file. Almost always you will want to use the Order Increment ID (Example: 100000001).')
        ));

        $fieldset->addField('product_identifier', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Product Identifier'),
            'name' => 'product_identifier',
            'values' => Mage::getSingleton('xtento_trackingimport/system_config_source_product_identifier')->toOptionArray(),
            'note' => Mage::helper('xtento_trackingimport')->__('This is what is called the Product Identifier in the import settings and is what\'s used to identify the product in the import file. Almost always you will want to use the SKU.')
        ));
        $attributeCodeJs = "<script>Event.observe(window, 'load', function() { function checkAttributeField(field) {if(field.value=='attribute') {\$('product_identifier_attribute_code').parentNode.parentNode.show()} else {\$('product_identifier_attribute_code').parentNode.parentNode.hide()}} checkAttributeField($('product_identifier')); $('product_identifier').observe('change', function(){ checkAttributeField(this); }); });</script>";
        if ($model->getData('product_identifier') !== 'attribute') {
            // Not filled
            $attributeCodeJs .= "<script>$('product_identifier_attribute_code').parentNode.parentNode.hide()</script>";
        }
        $fieldset->addField('product_identifier_attribute_code', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Product Identifier: Attribute Code'),
            'name' => 'product_identifier_attribute_code',
            'note' => Mage::helper('xtento_trackingimport')->__('IMPORTANT: This is not the attribute name. It is the attribute code you assigned the attribute.') . $attributeCodeJs,
        ));

        if (Mage::helper('xtcore/utils')->mageVersionCompare(Mage::getVersion(), '1.4.0.1', '>')) {
            $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
                ->setTemplate('promo/fieldset.phtml')
                ->setNewChildUrl($this->getUrl('*/trackingimport_profile/newConditionHtml/form/rule_conditions_fieldset', array('profile_id' => Mage::registry('tracking_import_profile')->getId())));

            $fieldset = $form->addFieldset('rule_conditions_fieldset', array(
                'legend' => Mage::helper('xtento_trackingimport')->__('Process %s only if...', Mage::registry('tracking_import_profile')->getEntity()),
            ))->setRenderer($renderer);

            $fieldset->addField('conditions', 'text', array(
                'name' => 'conditions',
                'label' => Mage::helper('salesrule')->__('Conditions'),
                'title' => Mage::helper('salesrule')->__('Conditions'),
            ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));
        }

        $form->setValues($model->getConfiguration());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
