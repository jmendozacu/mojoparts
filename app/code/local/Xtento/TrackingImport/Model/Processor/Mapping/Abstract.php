<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-29T13:44:10+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Processor/Mapping/Abstract.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

abstract class Xtento_TrackingImport_Model_Processor_Mapping_Abstract extends Varien_Object
{
    protected $_mapping = null;
    protected $_mappingType = '';

    public function getMapping()
    {
        if ($this->_mapping !== null) {
            return $this->_mapping;
        }

        $data = $this->getMappingData();

        $mapping = array();
        foreach ($data as $id => $field) {
            if (!isset($field['field'])) {
                continue;
            }
            if (!isset($field['value'])) {
                $value = '';
            } else {
                $value = $field['value'];
            }
            if (!isset($field['default_value'])) {
                $default_value = false;
            } else {
                $default_value = $field['default_value'];
            }
            if (!isset($field['xml'])) {
                $xml = false;
            } else {
                $xml = $field['xml'];
            }
            // Get data from mapping fields
            $mappingFields = $this->getMappingFields();
            if (!isset($mappingFields[$field['field']]) || !isset($mappingFields[$field['field']]['group'])) {
                $group = false;
            } else {
                $group = $mappingFields[$field['field']]['group'];
            }

            // Get field configuration (based on XML)
            $fieldConfiguration = Mage::getModel('xtento_trackingimport/processor_mapping_'.$this->_mappingType.'_configuration')->getConfiguration($field['field'], $xml);

            // Return field
            $mapping[$id] = array('id' => $id, 'field' => $field['field'], 'value' => $value, 'default_value' => $default_value, 'xml' => $xml, 'config' => $fieldConfiguration, 'group' => $group);
        }
        $this->_mapping = $mapping;

        return $this->_mapping;
    }

    public function getMappedFieldsForField($field)
    {
        $mapping = $this->getMapping();
        $mappingFields = array();
        foreach ($mapping as $rowId => $fieldData) {
            if ($fieldData['field'] == $field) {
                $mappingFields[] = $fieldData;
            }
        }
        if (!empty($mappingFields)) {
            return $mappingFields;
        }
        return false;
    }

    /*public function getMappingConfig() {
        $mappingConfig = new Varien_Object();
        foreach ($this->getMapping() as $field => $data) {
            if (!isset($data['value']) || $data['value'] == '') {
                continue;
            }
            $mappingConfig->setData($data['field'], $data);
        }
        return $mappingConfig;
    }*/

    public function getMappingFields() {

    }

    public function getDefaultValues($entity)
    {
        $defaultValues = array();
        if ($entity == 'shipping_carriers') {
            $carriers = Mage::getSingleton('xtcore/system_config_source_carriers')->toOptionArray();
            foreach ($carriers as $carrier) {
                $defaultValues[$carrier['value']] = $carrier['label'];
            }
        }
        if ($entity == 'order_status') {
            $statuses = Mage::getSingleton('xtento_trackingimport/system_config_source_order_status')->toOptionArray();
            foreach ($statuses as $status) {
                if ($status['value'] == 'no_change') continue;
                $defaultValues[$status['value']] = $status['label'];
            }
        }
        if ($entity == 'yesno') {
            $defaultValues[0] = Mage::helper('xtento_trackingimport')->__('No');
            $defaultValues[1] = Mage::helper('xtento_trackingimport')->__('Yes');
        }
        return $defaultValues;
    }

    public function getDefaultValue($fieldId)
    {
        $mapping = $this->getMapping();
        if (isset($mapping[$fieldId])) {
            return $mapping[$fieldId]['default_value'];
        }
        return '';
    }
}
