<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-08-11T16:23:25+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Processor/Csv.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Processor_Csv extends Xtento_TrackingImport_Model_Processor_Abstract
{
    protected $config = array();
    protected $headerRow;
    protected $rowData;

    private function _initConfiguration()
    {
        if (!$this->config) {
            # Load configuration:
            $this->config = array(
                'IMPORT_SKIP_HEADER' => $this->getConfigValue('skip_header'),
                'IMPORT_DELIMITER' => $this->getConfigValue('delimiter'),
                'IMPORT_ENCLOSURE' => $this->getConfigValue('enclosure'),
            );

            # Get mapping model
            $this->mappingModel = Mage::getModel('xtento_trackingimport/processor_mapping_fields');
            $this->mappingModel->setMappingData($this->getConfigValue('mapping'));

            # Load mapping
            $this->mapping = $this->mappingModel->getMapping();
            if ($this->mappingModel->getMappedFieldsForField('order_identifier') === false) {
                Mage::throwException('Please configure the CSV processor in the configuration section of this import profile. The Order Identifier field may not be empty and must be mapped.');
            }
            if ($this->config['IMPORT_DELIMITER'] == '') {
                Mage::throwException('Please configure the CSV processor in the configuration section of this import profile. The Field Delimiter may not be empty.');
            }
            if ($this->config['IMPORT_ENCLOSURE'] == '') {
                $this->config['IMPORT_ENCLOSURE'] = '"';
            }
            if (strtolower($this->config['IMPORT_DELIMITER']) == 'tab' || $this->config['IMPORT_DELIMITER'] == '\t' || $this->config['IMPORT_DELIMITER'] == chr(9)) {
                $this->config['IMPORT_DELIMITER'] = "\t";
            }
            if (strtolower($this->config['IMPORT_DELIMITER']) == 'flf') {
                $this->config['IMPORT_FIXED_LENGTH_FORMAT'] = true;
            } else {
                $this->config['IMPORT_FIXED_LENGTH_FORMAT'] = false;
            }
        }
    }

    public function getRowsToProcess($filesToProcess)
    {
        @ini_set('auto_detect_line_endings', 1);

        # Logger for this processor
        //$writer = new Zend_Log_Writer_Stream(Mage::getBaseDir('log') . DS . 'tracking_import_processor_csv.log');
        //$log = new Zend_Log($writer);

        # Updates to process, later the result
        $updatesInFilesToProcess = array();

        $this->_initConfiguration();

        foreach ($filesToProcess as $importFile) {
            $data = $importFile['data'];
            $filename = $importFile['filename'];
            unset($importFile['data']);

            // Remove UTF8 BOM
            $bom = pack('H*', 'EFBBBF');
            $data = preg_replace("/^$bom/", '', $data);

            $updatesToProcess = array();
            $foundFields = array();
            $rowCounter = 0;

            if ($this->config['IMPORT_FIXED_LENGTH_FORMAT']) {
                // Fixed length format
                foreach (explode("\n", $data) as $line) {
                    $this->rowData = $line;

                    $rowCounter++;
                    if ($rowCounter == 1) {
                        // Skip the header
                        if ($this->config['IMPORT_SKIP_HEADER'] == true) {
                            continue;
                        }
                    }

                    $skipRow = false;
                    // First run: Get order number for row
                    $rowIdentifier = "";
                    foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                        if ($fieldData['field'] == 'order_identifier') {
                            $rowIdentifier = $this->getFieldData($fieldData);
                        }
                        // Check if row should be skipped.
                        if (true === Mage::getSingleton('xtento_trackingimport/processor_mapping_fields_configuration')->checkSkipImport($fieldData['field'], $fieldData['config'], $this)) {
                            $skipRow = true;
                        }
                    }
                    if ($skipRow) {
                        // Field in field_configuration XML determined that this row should be skipped. "<skip>" parameter in XML field config
                        continue;
                    }
                    if (empty($rowIdentifier)) {
                        continue;
                    }
                    if (!isset($updatesToProcess[$rowIdentifier])) {
                        $updatesToProcess[$rowIdentifier] = array();
                    }

                    $rowArray = array();
                    foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                        if (isset($fieldData['disabled']) && $fieldData['disabled']) {
                            continue;
                        }
                        $fieldName = $fieldData['field'];
                        $fieldValue = $this->getFieldData($fieldData);
                        if ($fieldValue !== '') {
                            if (!in_array($fieldName, $foundFields)) {
                                $foundFields[] = $fieldName;
                            }
                            #$rowArray[$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                            if (isset($fieldData['group']) && !empty($fieldData['group'])) {
                                $rowArray[$fieldData['group']][$rowCounter - 1][$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                            } else {
                                $rowArray[$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                            }
                        }
                    }
                    $updatesToProcess[$rowIdentifier] = $rowArray;
                }
            } else {
                // Traditional CSV format
                $fileHandle = fopen('php://memory', 'rw');
                fwrite($fileHandle, $data);
                rewind($fileHandle);

                $this->headerRow = array();

                while (($rowData = fgetcsv($fileHandle, 0, $this->config['IMPORT_DELIMITER'], $this->config['IMPORT_ENCLOSURE'])) !== false) {
                    $this->rowData = $rowData;

                    $rowCounter++;
                    if ($rowCounter == 1) {
                        // Skip the header line but parse it for field names.
                        $numberOfFields = count($rowData);
                        for ($i = 0; $i < $numberOfFields; $i++) {
                            $this->headerRow[$rowData[$i]] = $i;
                        }
                        if ($this->config['IMPORT_SKIP_HEADER'] == true) {
                            continue;
                        }
                    }

                    // First run: Get order number for row
                    $rowIdentifier = "";
                    foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                        if ($fieldData['field'] == 'order_identifier') {
                            $fieldValue = $this->getFieldData($fieldData);
                            if (!empty($fieldValue)) {
                                $rowIdentifier = $fieldValue;
                            }
                        }
                    }
                    if (empty($rowIdentifier)) {
                        continue;
                    }
                    if (!isset($updatesToProcess[$rowIdentifier])) {
                        $updatesToProcess[$rowIdentifier] = array();
                        $rowArray = array();
                    } else {
                        $rowArray = $updatesToProcess[$rowIdentifier];
                    }

                    foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                        if (isset($fieldData['disabled']) && $fieldData['disabled']) {
                            continue;
                        }
                        $fieldName = $fieldData['field'];
                        $fieldValue = $this->getFieldData($fieldData);
                        if ($fieldValue !== '') {
                            if (!in_array($fieldName, $foundFields)) {
                                $foundFields[] = $fieldName;
                            }
                            if ($fieldName == 'sku' && isset($fieldData['config']['sku_qty_one_field']) && $fieldData['config']['sku_qty_one_field'] == 1) {
                                // We're supposed to import the SKU and Qtys all from one field. Each combination separated by a ; and sku/qty separated by :
                                $skuAndQtys = explode(";", $fieldValue);
                                foreach ($skuAndQtys as $skuAndQty) {
                                    $rowCounter++;
                                    list ($sku, $qty) = explode(":", $skuAndQty);
                                    if ($sku !== '' && isset($fieldData['group']) && !empty($fieldData['group'])) {
                                        $rowArray[$fieldData['group']][$rowCounter - 1]['sku'] = $sku;
                                        $rowArray[$fieldData['group']][$rowCounter - 1]['qty'] = $qty;
                                    }
                                }
                            } else {
                                if (isset($fieldData['group']) && !empty($fieldData['group'])) {
                                    $rowArray[$fieldData['group']][$rowCounter - 1][$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                                } else {
                                    $rowArray[$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                                }
                            }
                        }
                    }
                    $updatesToProcess[$rowIdentifier] = $rowArray;
                }
            }

            // Output the header row in a nicer string
            $hasHeaderRow = ($this->config['IMPORT_SKIP_HEADER']) ? "Yes" : "No";
            $headerRowTemp = $this->headerRow;
            array_walk($headerRowTemp, create_function('&$i,$k', '$i=" \"$k\"=\"$i\"";'));
            // File processed
            $updatesInFilesToProcess[] = array(
                "FILE_INFORMATION" => $importFile,
                "HEADER_ROW" => "Skip header row: " . $hasHeaderRow . " | Header row:" . implode($headerRowTemp, ""),
                "FIELDS" => $foundFields,
                "ROWS" => $updatesToProcess
            );
        }

        @ini_set('auto_detect_line_endings', 0);

        #ini_set('xdebug.var_display_max_depth', 10);
        #Zend_Debug::dump($updatesToProcess);
        #die();

        return $updatesInFilesToProcess;
    }

    public function getFieldPos($mappedField)
    {
        if (!is_numeric($mappedField) && isset($this->headerRow[$mappedField])) {
            return $this->headerRow[$mappedField];
        } else {
            return $mappedField;
        }
    }

    /**
     * @param $fieldData
     * @return mixed
     *
     * Wrapper function to manipulate field data returned
     */
    public function getFieldData($fieldData)
    {
        $returnData = $this->getFieldDataRaw($fieldData);
        $returnData = Mage::getSingleton('xtento_trackingimport/processor_mapping_fields_configuration')->manipulateFieldFetched($fieldData['field'], $returnData, $fieldData['config'], $this);
        return $returnData;
    }

    public function getFieldDataRaw($fieldData, $bypassFieldConfiguration = false)
    {
        if ($this->config['IMPORT_FIXED_LENGTH_FORMAT']) {
            $fieldPosition = explode("-", $this->getFieldPos($fieldData['value']));
            if (!isset($fieldPosition[1])) return "";
            $data = trim(substr($this->rowData, $fieldPosition[0] - 1, $fieldPosition[1] - $fieldPosition[0]));
        } else {
            $field = $fieldData['field'];
            $fieldPos = $this->getFieldPos($fieldData['value']);
            if (isset($this->rowData[$fieldPos])) {
                $data = $this->rowData[$fieldPos];
                if (!$bypassFieldConfiguration) {
                    $data = Mage::getSingleton('xtento_trackingimport/processor_mapping_fields_configuration')->handleField($field, $data, $fieldData['config']);
                }
                if ($data == '') {
                    // Try to get the default value at least.. otherwise ''
                    $data = $this->mappingModel->getDefaultValue($fieldData['id']);
                }
            } else {
                if (!$bypassFieldConfiguration) {
                    $data = Mage::getSingleton('xtento_trackingimport/processor_mapping_fields_configuration')->handleField($field, '', $fieldData['config']);
                } else {
                    $data = '';
                }
                if (empty($data)) {
                    // Try to get the default value at least.. otherwise ''
                    $data = $this->mappingModel->getDefaultValue($fieldData['id']);
                }
            }
        }
        return trim($data);
    }
}
