<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-15T17:07:56+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import extends Mage_Core_Model_Abstract
{
    /*
     * The actual import model handling object updates/imports
     */

    // Import entities
    const ENTITY_ORDER_UPDATE = 'order';

    // Import processors
    const PROCESSOR_CSV = 'csv';
    const PROCESSOR_XML = 'xml';

    // Import types
    const IMPORT_TYPE_TEST = 0; // Test Import
    const IMPORT_TYPE_MANUAL = 2; // From "Manual Import" screen
    const IMPORT_TYPE_CRONJOB = 3; // Cronjob Import

    protected $_sources;

    public function _construct()
    {
        if ($this->getProfileId()) {
            $profile = Mage::getModel('xtento_trackingimport/profile')->load($this->getProfileId());
            $this->setProfile($profile);
        }
        parent::_construct();
    }

    public function getEntities()
    {
        $values = array();
        $values[Xtento_TrackingImport_Model_Import::ENTITY_ORDER_UPDATE] = Mage::helper('xtento_trackingimport')->__('Order Update');
        return $values;
    }

    public function getProcessors()
    {
        $values = array();
        $values[Xtento_TrackingImport_Model_Import::PROCESSOR_CSV] = Mage::helper('xtento_trackingimport')->__('CSV / TXT / Tab-delimited / Fixed-Length');
        $values[Xtento_TrackingImport_Model_Import::PROCESSOR_XML] = Mage::helper('xtento_trackingimport')->__('XML');
        return $values;
    }

    public function getImportTypes()
    {
        $values = array();
        $values[Xtento_TrackingImport_Model_Import::IMPORT_TYPE_TEST] = Mage::helper('xtento_trackingimport')->__('Test Import');
        $values[Xtento_TrackingImport_Model_Import::IMPORT_TYPE_MANUAL] = Mage::helper('xtento_trackingimport')->__('Manual Import');
        $values[Xtento_TrackingImport_Model_Import::IMPORT_TYPE_CRONJOB] = Mage::helper('xtento_trackingimport')->__('Cronjob Import');
        return $values;
    }

    /*
     * Validate profile function used to run a test import when editing a profile
     */
    public function testImport($uploadedFile)
    {
        $this->setImportType(self::IMPORT_TYPE_TEST);
        $this->_runImport($uploadedFile);
        return $this;
    }

    public function manualImport($uploadedFile = false)
    {
        $this->_checkStatus();
        $this->setImportType(self::IMPORT_TYPE_MANUAL);
        $this->_beforeImport();
        $importResult = $this->_runImport($uploadedFile);
        $this->_afterImport();
        return $importResult;
    }

    public function cronImport()
    {
        $this->setImportType(self::IMPORT_TYPE_CRONJOB);
        $this->_beforeImport();
        $importResult = $this->_runImport();
        if (empty($importResult)) {
            $this->getLogEntry()->delete();
            return false;
        }
        $this->_afterImport();
        return $this;
    }

    private function _runImport($uploadedFile = false)
    {
        try {
            $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__('Starting import...'));
            if ($this->getTestMode()) {
                $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__('Test mode enabled. No real data will be imported. This is a tool to preview the import.'));
            } else {
                // Real import
                @set_time_limit(0);
                Mage::helper('xtcore/utils')->increaseMemoryLimit('1024M');
            }
            if (!$this->getProfile()) {
                Mage::throwException(Mage::helper('xtento_trackingimport')->__('No profile to import specified.'));
            }
            if (!$uploadedFile) {
                $filesToProcess = $this->_loadFiles();
            } else {
                $filesToProcess = array($uploadedFile);
                $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Processing just the uploaded file."));
            }
            if (empty($filesToProcess)) {
                Mage::throwException(Mage::helper('xtento_trackingimport')->__('0 files have been retrieved from import sources.'));
            } else {
                $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("%d files have been retrieved from import sources.", count($filesToProcess)));
            }
            // Process files
            $processor = Mage::getModel('xtento_trackingimport/processor_' . $this->getProfile()->getProcessor(), array('profile' => $this->getProfile()));
            $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Using %s processor to parse files.", Mage::helper('xtento_trackingimport/import')->getProcessorName($this->getProfile()->getProcessor())));
            $updatesInFilesToProcess = $processor->getRowsToProcess($filesToProcess);
            if (empty($updatesInFilesToProcess)) {
                $this->_archiveFiles($filesToProcess);
                $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("%d files have been parsed, however, they did not contain any valid updates. Make sure the import processors are set up properly. Try running a test import in the debug section.", count($filesToProcess)));
                $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Files parsed: <pre>%s</pre>", print_r($filesToProcess, true)));
            } else {
                $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("The following data has been parsed in the import file(s): %s", print_r($updatesInFilesToProcess, true)));
            }
            $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__('Trying to import the updates...'));
            // Import/update objects
            $import = Mage::getModel('xtento_trackingimport/import_iterator_' . $this->getProfile()->getEntity());
            $import->setImportType($this->getImportType());
            $import->setTestMode($this->getTestMode());
            $import->setProfile($this->getProfile());
            $importResult = $import->processUpdates($updatesInFilesToProcess);
            if (!$importResult) {
                if (!$uploadedFile) {
                    $this->_archiveFiles($filesToProcess);
                }
                Mage::throwException(Mage::helper('xtento_trackingimport')->__('0 %s updates have been imported.', $this->getProfile()->getEntity()));
            }
            // Archive files
            if (!$uploadedFile) {
                $this->_archiveFiles($filesToProcess);
            }
            if (is_array($importResult)) {
                $this->getLogEntry()->setRecordsImported($importResult['updated_record_count']);
            }
            return $importResult;
        } catch (Exception $e) {
            if ($this->getLogEntry()) {
                $result = Xtento_TrackingImport_Model_Log::RESULT_FAILED;
                if (preg_match('/have been imported/', $e->getMessage()) || preg_match('/have been retrieved/', $e->getMessage())) {
                    if ($this->getImportType() == self::IMPORT_TYPE_MANUAL) {
                        $result = Xtento_TrackingImport_Model_Log::RESULT_WARNING;
                    } else {
                        return false;
                    }
                }
                $this->getLogEntry()->setResult($result);
                $this->getLogEntry()->addResultMessage($e->getMessage());
                $this->_afterImport();
            }
            if ($this->getImportType() == self::IMPORT_TYPE_MANUAL || $this->getImportType() == self::IMPORT_TYPE_TEST) {
                Mage::throwException($e->getMessage());
            }
            return false;
        }
    }

    /*
     * Load files from sources
     */
    private function _loadFiles()
    {
        $sourcesChecked = 0;
        $this->_sources = $this->getProfile()->getSources();
        foreach ($this->_sources as $source) {
            $sourcesChecked++;
            try {
                $filesToProcess = $source->loadFiles();
                if (is_array($this->getFiles()) && is_array($filesToProcess)) {
                    $this->setFiles(array_merge($this->getFiles(), $filesToProcess));
                } else {
                    $this->setFiles($filesToProcess);
                }
            } catch (Exception $e) {
                $this->getLogEntry()->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                $this->getLogEntry()->addResultMessage($e->getMessage());
            }
        }
        if ($sourcesChecked < 1) {
            Mage::throwException("Fatal Error: No import sources have been enabled for this profile. For manual/automatic imports to run, import sources must be defined where files will be downloaded from, OR a file must be uploaded manually.");
        } else {
            $this->getLogEntry()->addDebugMessage(Mage::helper('xtento_trackingimport')->__("%d import sources have been found.", $sourcesChecked));
        }
        return $this->getFiles();
    }

    /*
     * Archive files on sources
     */
    private function _archiveFiles($filesToProcess)
    {
        if (!$this->getTestMode()) {
            foreach ($this->_sources as $source) {
                try {
                    $source->archiveFiles($filesToProcess);
                } catch (Exception $e) {
                    $this->getLogEntry()->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                    $this->getLogEntry()->addResultMessage($e->getMessage());
                }
            }
        }
        return $this->getFiles();
    }

    private function _beforeImport()
    {
        $this->setBeginTime(time());
        #$memBefore = memory_get_usage();
        #$timeBefore = time();
        #echo "Before import: " . $memBefore . " bytes / Time: " . $timeBefore . "<br>";
        $logEntry = Mage::getModel('xtento_trackingimport/log');
        $logEntry->setCreatedAt(now());
        $logEntry->setProfileId($this->getProfile()->getId());
        $logEntry->setSourceIds($this->getProfile()->getSourceIds());
        $logEntry->setImportType($this->getImportType());
        $logEntry->setRecordsImported(0);
        $logEntry->setResultMessage(Mage::helper('xtento_trackingimport')->__('Import started...'));
        $logEntry->save();
        if ($this->getImportType() == Xtento_TrackingImport_Model_Import::IMPORT_TYPE_MANUAL || $this->getImportType() == Xtento_TrackingImport_Model_Import::IMPORT_TYPE_TEST) {
            $logEntry->setLogDebugMessages(true);
        }
        $this->setLogEntry($logEntry);
        Mage::unregister('tracking_import_log');
        Mage::unregister('tracking_import_profile');
        Mage::register('tracking_import_log', $logEntry);
        Mage::register('tracking_import_profile', $this->getProfile());
    }

    private function _afterImport()
    {
        $this->_saveLog();
        Mage::unregister('tracking_import_profile');
        #echo "After import: " . memory_get_usage() . " (Difference: " . round((memory_get_usage() - $memBefore) / 1024 / 1024, 2) . " MB, " . (time() - $timeBefore) . " Secs) - Count: " . (count($importIds)) . " -  Per entry: " . round(((memory_get_usage() - $memBefore) / 1024 / 1024) / (count($importIds)), 2) . "<br>";
    }

    private function _saveLog()
    {
        $this->_saveLastExecutionNow();
        if (is_array($this->getFiles())) {
            $importedFiles = array();
            foreach ($this->getFiles() as $fileInfo) {
                $importedFiles[] = $fileInfo['filename'];
            }
            $this->getLogEntry()->setFiles(implode("|", $importedFiles));
        }
        if ($this->getTestMode()) {
            $resultMessage = 'Test mode: %d %s updates would have been imported in %d seconds.';
        } else {
            $resultMessage = 'Import of %d %s updates finished in %d seconds.';
        }
        $this->getLogEntry()->setResult($this->getLogEntry()->getResult() ? $this->getLogEntry()->getResult() : Xtento_TrackingImport_Model_Log::RESULT_SUCCESSFUL);
        $this->getLogEntry()->setResultMessage($this->getLogEntry()->getResultMessages() ? $this->getLogEntry()->getResultMessages() : Mage::helper('xtento_trackingimport')->__($resultMessage, $this->getLogEntry()->getRecordsImported(), $this->getProfile()->getEntity(), (time() - $this->getBeginTime())));
        $this->getLogEntry()->save();
        $this->_errorEmailNotification();
    }

    private function _saveLastExecutionNow()
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->update(
            $this->getProfile()->_getResource()->getMainTable(),
            array('last_execution' => now()),
            array("`{$this->getProfile()->_getResource()->getIdFieldName()}` = {$this->getProfile()->getId()}")
        );
    }

    private function _errorEmailNotification()
    {
        if (!Mage::helper('xtento_trackingimport')->isDebugEnabled() || Mage::helper('xtento_trackingimport')->getDebugEmail() == '') {
            return $this;
        }
        if ($this->getLogEntry()->getResult() >= Xtento_TrackingImport_Model_Log::RESULT_WARNING) {
            try {
                $mail = new Zend_Mail();
                $mail->setFrom('store@' . @$_SERVER['SERVER_NAME'], @$_SERVER['SERVER_NAME']);
                foreach (explode(",", Mage::helper('xtento_trackingimport')->getDebugEmail()) as $emailAddress) {
                    $emailAddress = trim($emailAddress);
                    $mail->addTo($emailAddress, $emailAddress);
                }
                $mail->setSubject('Magento Tracking Import Module @ ' . @$_SERVER['SERVER_NAME']);
                $mail->setBodyText('Warning/Error/Message(s): ' . $this->getLogEntry()->getResultMessages());
                $mail->send(Mage::helper('xtcore/utils')->getEmailTransport());
            } catch (Exception $e) {
                $this->getLogEntry()->addResultMessage('Exception: ' . $e->getMessage());
                $this->getLogEntry()->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                $this->getLogEntry()->setResultMessage($this->getLogEntry()->getResultMessages());
                $this->getLogEntry()->save();
            }
        }
        return $this;
    }

    private function _checkStatus()
    {
        if (!Xtento_TrackingImport_Model_System_Config_Source_Order_Status::isEnabled()) {
            Mage::throwException(Mage::helper('xtento_trackingimport')->getMsg());
        }
    }

    private function _getExperimentalFeatureSupport()
    {
        $experimentalFeatureDataFile = Mage::helper('xtcore/filesystem')->getModuleDir($this) . DS . 'xtento' . DS . 'experimental_features.xml';
        if (@file_exists($experimentalFeatureDataFile)) {
            return true;
        }
        return false;
    }
}