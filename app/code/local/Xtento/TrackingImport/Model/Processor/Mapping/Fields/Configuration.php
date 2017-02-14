<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-06-06T17:22:48+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Processor/Mapping/Fields/Configuration.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Processor_Mapping_Fields_Configuration extends Xtento_TrackingImport_Model_Processor_Mapping_Configuration_Abstract
{
    protected $_configurationType = 'fields';

    public function handleField($field, $value, $fieldConfiguration)
    {
        // Mapped fields
        if (isset($fieldConfiguration['map'])) {
            if (count($fieldConfiguration['map']) > 1) {
                // Multiple mapping values
                foreach ($fieldConfiguration['map'] as $config) {
                    $value = $this->_mapFromTo($field, $value, $config);
                }
            } else {
                // One mapping value
                $config = $fieldConfiguration['map'];
                $value = $this->_mapFromTo($field, $value, $config);
            }
        }
        // Search & Replace
        if (isset($fieldConfiguration['replace'])) {
            if (count($fieldConfiguration['replace']) > 1) {
                // Multiple mapping values
                foreach ($fieldConfiguration['replace'] as $config) {
                    $value = $this->_searchReplace($field, $value, $config);
                }
            } else {
                // One mapping value
                $config = $fieldConfiguration['replace'];
                $value = $this->_searchReplace($field, $value, $config);
            }
        }
        return $value;
    }

    /*
     * Map "from" > "to" - mappings for fields
     */
    private function _mapFromTo($field, $value, $config)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['from']) && isset($configAttributes['to'])) {
                // Matching method
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($configAttributes['from'] == $value) {
                        $value = $configAttributes['to'];
                    }
                } else {
                    if (trim($configAttributes['method']) == 'preg_match') {
                        // preg_match
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace("e", "", $configAttributes['regex_modifier']);
                        }
                        if (preg_match('/' . str_replace('/', '\\/', $configAttributes['from']) . '/' . $configAttributes['regex_modifier'], $value)) {
                            $value = $configAttributes['to'];
                        }
                    }
                }
            }
        }
        return $value;
    }

    private function _searchReplace($field, $value, $config)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['search']) && isset($configAttributes['replace'])) {
                // Matching method
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'str_replace')) {
                    // No method specified, str_replace
                    $value = str_replace($configAttributes['search'], $configAttributes['replace'], $value);
                } else {
                    if (trim($configAttributes['method']) == 'preg_replace') {
                        // preg_replace
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace("e", "", $configAttributes['regex_modifier']);
                        }
                        $value = preg_replace('/' . str_replace('/', '\\/', $configAttributes['search']) . '/' . $configAttributes['regex_modifier'], $configAttributes['replace'], $value);
                    }
                }
            }
        }
        return $value;
    }

    /*
     * Manipulate the field that is loaded from the file using "use" parameter
     */
    public function manipulateFieldFetched($field, $value, $fieldConfiguration, $processorClass)
    {
        // Use fields based on conditions
        if (isset($fieldConfiguration['use'])) {
            if (count($fieldConfiguration['use']) > 1) {
                // Multiple "use" values
                foreach ($fieldConfiguration['use'] as $config) {
                    $value = $this->_matchManipulatedField($field, $value, $config, $processorClass);
                }
            } else {
                // One "use" value
                $config = $fieldConfiguration['use'];
                $value = $this->_matchManipulatedField($field, $value, $config, $processorClass);
            }
        }
        return $value;
    }

    private function _matchManipulatedField($field, $value, $config, $processorClass)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['field']) && isset($configAttributes['if']) && isset($configAttributes['is'])) {
                // Matching method
                $matchValue = $processorClass->getFieldDataRaw(array('field' => $field, 'value' => $configAttributes['if']), true);
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($matchValue == $configAttributes['is']) { // If field "if" is "is" then use "field"
                        $value = $processorClass->getFieldDataRaw(array('field' => $field, 'value' => $configAttributes['field']), true); // Use field "value" then
                    }
                } else {
                    if (trim($configAttributes['method']) == 'preg_match') {
                        // preg_match
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace("e", "", $configAttributes['regex_modifier']);
                        }
                        if (preg_match('/' . str_replace('/', '\\/', $configAttributes['is']) . '/' . $configAttributes['regex_modifier'], $matchValue)) {
                            $value = $processorClass->getFieldDataRaw(array('field' => $field, 'value' => $configAttributes['field']), true); // Use field "value" then
                        }
                    }
                }
            }
        }
        return $value;
    }

    /*
     * If "skip" node is set in XML configuration, check if this row in the import file should be skipped based on "if" and "is" attributes
     */
    public function checkSkipImport($field, $fieldConfiguration, $processorClass)
    {
        $skipImport = false;
        // Check if import of current row should be skipped
        if (isset($fieldConfiguration['skip'])) {
            if (count($fieldConfiguration['skip']) > 1) {
                // Multiple skip values
                foreach ($fieldConfiguration['skip'] as $config) {
                    if ($this->_skipCheck($field, $config, $processorClass)) {
                        $skipImport = true;
                    }
                }
            } else {
                // One skip value
                $config = $fieldConfiguration['skip'];
                if ($this->_skipCheck($field, $config, $processorClass)) {
                    $skipImport = true;
                }
            }
        }
        return $skipImport;
    }

    private function _skipCheck($field, $config, $processorClass)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['if']) && isset($configAttributes['is'])) {
                // Matching method
                $matchValue = $processorClass->getFieldDataRaw(array('field' => $field, 'value' => $configAttributes['if']), true);
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($matchValue == $configAttributes['is']) { // If field "if" is "is" then use "field"
                        return true;
                    }
                } else {
                    if (trim($configAttributes['method']) == 'preg_match') {
                        // preg_match
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace("e", "", $configAttributes['regex_modifier']);
                        }
                        if (preg_match('/' . str_replace('/', '\\/', $configAttributes['is']) . '/' . $configAttributes['regex_modifier'], $matchValue)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}

?>