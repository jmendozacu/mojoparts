<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-12T17:30:50+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Processor/Mapping/Configuration/Abstract.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

abstract class Xtento_TrackingImport_Model_Processor_Mapping_Configuration_Abstract
{
    protected $_field = '';
    protected $_xmlConfig = false;

    protected function _loadXml($fieldXml)
    {
        if (empty($fieldXml)) {
            return false;
        }
        try {
            $this->_xmlConfig = new DOMDocument();
            $this->_xmlConfig->loadXML($fieldXml);
        } catch (Exception $e) {
            $log = Mage::registry('tracking_import_log');
            if ($log !== null) {
                $log->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                $log->addResultMessage("Could not load XML configuration for field " . $this->_field . ", skipping field validation: " . $e->getMessage());
            }
            if (Mage::app()->getRequest() && Mage::app()->getRequest()->getControllerName() == 'trackingimport_profile' && Mage::app()->getRequest()->getActionName() == 'edit') {
                Mage::register('tracking_import_xml_'.$this->_configurationType.'_warning', Mage::helper('xtcore')->__("Could not load XML configuration for field " . $this->_field . ": " . $e->getMessage()), true);
            }
            return false;
        }
        return true;
    }

    protected function _getXmlConfig()
    {
        return $this->_xmlConfig;
    }

    public function getConfiguration($field, $fieldXml)
    {
        $this->_field = $field;
        $fieldConfiguration = array();
        if ($this->_loadXml($fieldXml)) {
            $xmlConfig = $this->_getXmlConfig();
            $root = $xmlConfig->documentElement;
            $fieldConfiguration = $this->_domToArray($root);
            $fieldConfiguration['@root'] = $root->tagName;
        }
        return $fieldConfiguration;
    }

    private function _domToArray($node)
    {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->_domToArray($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string)$v;
                    }
                }
                if ($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
                    $output = array('@content' => $output); //Change output into an array.
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = array();
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string)$attrNode->value;
                        }
                        $output['@'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) == 1 && $t != '@') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
    }
}