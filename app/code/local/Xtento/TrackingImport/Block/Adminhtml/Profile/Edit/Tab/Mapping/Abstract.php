<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-04-28T18:23:35+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Mapping/Abstract.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

abstract class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Mapping_Abstract extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_addAllButtonLabel;

    public function __construct()
    {
        $mappingModel = Mage::getModel($this->MAPPING_MODEL);
        $profile = Mage::registry('tracking_import_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(isset($configuration[$this->MAPPING_ID]) ? $configuration[$this->MAPPING_ID] : array());

        $importFieldRenderer = Mage::app()->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_mapping_importfields');
        $importFieldRenderer->setImportFields($mappingModel->getMappingFields());
        $importFieldRenderer->setMappingId($this->MAPPING_ID);
        $importFieldRenderer->setSelectLabel($this->SELECT_LABEL);
        $importFieldRenderer->setStyle('width: 91%');

        $this->addColumn('field', array(
            'label' => Mage::helper('adminhtml')->__($this->FIELD_LABEL),
            'style' => 'width: 99.9%',
            'renderer' => $importFieldRenderer
        ));

        if ($this->HAS_VALUE_COLUMN) {
            $this->addColumn('value', array(
                'label' => Mage::helper('adminhtml')->__($this->VALUE_FIELD_LABEL),
                'style' => 'width:98%',
            ));
        }

        if ($this->HAS_DEFAULT_VALUE_COLUMN) {
            $defaultValuesRenderer = Mage::app()->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tab_mapping_defaultvalues');
            $defaultValuesRenderer->setImportFields($mappingModel->getMappingFields());
            $defaultValuesRenderer->setMappingModel($mappingModel);
            $defaultValuesRenderer->setMappingId($this->MAPPING_ID);
            $defaultValuesRenderer->setStyle('width: 99.9%');

            $this->addColumn('default_value', array(
                'label' => Mage::helper('adminhtml')->__($this->DEFAULT_VALUE_FIELD_LABEL),
                'style' => 'width: 98%',
                'renderer' => $defaultValuesRenderer
            ));
        }

        $this->_addAfter = true;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__($this->ADD_FIELD_LABEL);
        $this->_addAllButtonLabel = Mage::helper('adminhtml')->__($this->ADD_ALL_FIELD_LABEL);
        parent::__construct();
        $this->setTemplate('xtento/trackingimport/widget/mapper.phtml');
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $mappingModel = Mage::getModel($this->MAPPING_MODEL);
        $profile = Mage::registry('tracking_import_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(isset($configuration[$this->MAPPING_ID]) ? $configuration[$this->MAPPING_ID] : array());
        $mappingFields = $mappingModel->getMappingFields();

        // Add the actual mapped fields
        $html = '<script>' . "\n";
        $html .= 'var ' . $this->MAPPING_ID . '_mapping_values = new Hash();' . "\n";
        #var_dump($mappingModel->getMapping()); die();
        foreach ($mappingModel->getMapping() as $fieldId => $fieldData) {
            $html .= $this->MAPPING_ID . '_mapping_values[\'' . $fieldData['id'] . '\'] = \'' . $this->_escapeStringJs($fieldData['field']) . '\';' . "\n";
        }
        if ($this->HAS_DEFAULT_VALUE_COLUMN) {
            // Add the default values
            $html .= 'var ' . $this->MAPPING_ID . '_default_values = new Hash();' . "\n";
            foreach ($mappingModel->getMapping() as $fieldId => $fieldData) {
                $html .= $this->MAPPING_ID . '_default_values[\'' . $fieldData['id'] . '\'] = \'' . $this->_escapeStringJs($fieldData['default_value']) . '\';' . "\n";
            }
            // Add default value for default values
            $html .= 'var ' . $this->MAPPING_ID . '_default_value = new Hash();' . "\n";
            foreach ($mappingFields as $field => $fieldData) {
                if (isset($fieldData['default_value'])) {
                    $html .= $this->MAPPING_ID . '_default_value[\'' . $field . '\'] = \'' . $this->_escapeStringJs($fieldData['default_value']) . '\';' . "\n";
                }
            }
            // Add the possible default values
            $html .= 'var ' . $this->MAPPING_ID . '_possible_default_values = $H({' . "\n";
            $loopLength = 0;
            foreach ($mappingFields as $field => $fieldData) {
                if (isset($fieldData['default_values']) && is_array($fieldData['default_values'])) {
                    $loopLength++;
                }
            }
            $loopCounter = 0;
            foreach ($mappingFields as $field => $fieldData) {
                if (isset($fieldData['default_values']) && is_array($fieldData['default_values'])) {
                    $loopCounter++;
                    $loopLength2 = count($fieldData['default_values']);
                    $loopCounter2 = 0;
                    $html .= '\'' . $this->_escapeStringJs($field) . '\': {' . "\n";
                    foreach ($fieldData['default_values'] as $code => $label) {
                        $loopCounter2++;
                        $html .= '\'' . $this->_escapeStringJs($code) . '\': \'' . $this->_escapeStringJs($label) . '\'';
                        if ($loopCounter2 !== $loopLength2) {
                            $html .= ',';
                        }
                        $html .= "\n";
                    }
                    $html .= '}';
                    if ($loopCounter !== $loopLength) {
                        $html .= ',';
                    }
                }
            }
            $html .= '});';
        } else {
            $html .= 'var ' . $this->MAPPING_ID . '_default_values = new Hash();' . "\n";
            $html .= 'var ' . $this->MAPPING_ID . '_possible_default_values = $H({});' . "\n";
        }
        $html .= '</script>' . "\n";

        $html .= parent::render($element);

        return $html;
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }

        return '<input type="text" id="' . $inputName . '" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
        ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
        (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
        (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }

    private function _escapeStringJs($string)
    {
        return str_replace(array("'", "\n", "\r"), array("\\'", " ", " "), $string);
    }

    public function getMappingFields() {
        $mappingModel = Mage::getModel($this->MAPPING_MODEL);
        $profile = Mage::registry('tracking_import_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(isset($configuration[$this->MAPPING_ID]) ? $configuration[$this->MAPPING_ID] : array());

        return $mappingModel->getMappingFields();
    }
}
