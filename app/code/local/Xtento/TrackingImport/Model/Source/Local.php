<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-12T17:12:53+01:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Source/Local.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Source_Local extends Xtento_TrackingImport_Model_Source_Abstract
{
    /*
    * Get files from a local directory
    */
    public function testConnection()
    {
        $importDirectory = $this->_fixBasePath($this->getSource()->getPath());
        $archiveDirectory = $this->_fixBasePath($this->getSource()->getArchivePath());
        $testResult = new Varien_Object();

        // Check for forbidden folders
        $forbiddenFolders = array(Mage::getBaseDir('base'), Mage::getBaseDir('base') . DS . 'downloader');
        foreach ($forbiddenFolders as $forbiddenFolder) {
            if (@realpath($importDirectory) == $forbiddenFolder) {
                return $testResult->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('It is not allowed to load import files from the directory you have specified. Please change the local import directory to be located in the ./var/ folder for example. Do not use the Magento root directory for example.'));
            }
            if (@realpath($archiveDirectory) == $forbiddenFolder) {
                return $testResult->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('It is not allowed to move archived files into the directory you have specified. Please change the archive directory to be located in the ./var/ folder for example. Do not use the Magento root directory for example.'));
            }
        }

        if (!is_dir($importDirectory) && !preg_match('/%importid%/', $importDirectory)) {
            return $testResult->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('The specified local import directory does not exist. Please create this directory or adjust the path. Could not load files from: %s', $importDirectory));
        }
        $this->_connection = @opendir($importDirectory);
        if (!$this->_connection || @!is_readable($importDirectory)) {
            return $testResult->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('Could not open local import directory for reading. Please make sure that we have rights to read in the directory: %s', $importDirectory));
        }

        $testResult->setSuccess(true)->setMessage(Mage::helper('xtento_trackingimport')->__('Local directory exists and is readable. Connection tested successfully.'));
        $this->getSource()->setLastResult($testResult->getSuccess())->setLastResultMessage($testResult->getMessage())->save();
        return $testResult;
    }

    public function loadFiles()
    {
        $filesToProcess = array();

        $logEntry = Mage::registry('tracking_import_log');
        // Test connection
        $testResult = $this->testConnection();
        if (!$testResult->getSuccess()) {
            $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
            $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): %s', $this->getSource()->getName(), $this->getSource()->getId(), $testResult->getMessage()));
            return false;
        }

        $importDirectory = $this->_fixBasePath($this->getSource()->getPath());

        while (false !== ($filename = readdir($this->_connection))) {
            if ($filename != "." && $filename != ".." && !is_dir($importDirectory . DS . $filename)) {
                if (!preg_match($this->getSource()->getFilenamePattern(), $filename) && !preg_match('/\.chunk\./', $filename)) {
                    continue;
                }
                $fileHandle = fopen($importDirectory . DS . $filename, "r");
                if ($fileHandle) {
                    $buffer = '';
                    while (!feof($fileHandle)) {
                        $buffer .= fgets($fileHandle, 4096);
                    }
                    if (!empty($buffer)) {
                        $filesToProcess[] = array('source_id' => $this->getSource()->getId(), 'path' => $importDirectory, 'filename' => $filename, 'data' => $buffer);
                    } else {
                        $this->archiveFiles(array(array('source_id' => $this->getSource()->getId(), 'path' => $importDirectory, 'filename' => $filename)));
                    }
                } else {
                    $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                    $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not open and read the file "%s" in the import directory.', $this->getSource()->getName(), $this->getSource()->getId(), $testResult->getMessage(), $filename));
                    return false;
                }
            }
        }

        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false)
    {
        $logEntry = Mage::registry('tracking_import_log');
        $archiveDirectory = $this->_fixBasePath($this->getSource()->getArchivePath());

        if ($forceDelete) {
            foreach ($filesToProcess as $file) {
                if ($file['source_id'] !== $this->getSource()->getId()) {
                    continue;
                }
                if (!@unlink($file['path'] . $file['filename'])) {
                    $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                    $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not delete file "%s%s" from the local import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename']));
                }
            }
        } else if ($archiveDirectory !== "") {
            if (!is_dir($archiveDirectory)) {
                $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Archive directory does not exist. Please make sure the directory exists and that we have rights to read/write in the directory. Could not archive files.', $this->getSource()->getName(), $this->getSource()->getId()));
            } else {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@rename($file['path'] . $file['filename'], $archiveDirectory . $file['filename'])) {
                        $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not move file "%s%s" to the local archive directory located at "%s". Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename'], $archiveDirectory));
                    }
                }
            }
        } else if ($this->getSource()->getDeleteImportedFiles() == true) {
            foreach ($filesToProcess as $file) {
                if ($file['source_id'] !== $this->getSource()->getId()) {
                    continue;
                }
                if (!@unlink($file['path'] . $file['filename'])) {
                    $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                    $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not delete file "%s%s" from the local import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename']));
                }
            }
        }
    }

    private function _fixBasePath($originalPath)
    {
        /*
        * Let's try to fix the import directory and replace the dot with the actual Magento root directory.
        * Why? Because if the cronjob is executed using the PHP binary a different working directory (when using a dot (.) in a directory path) could be used.
        * But Magento is able to return the right base path, so let's use it instead of the dot.
        */
        if (substr($originalPath, 0, 2) == './') {
            return Mage::getBaseDir('base') . '/' . substr($originalPath, 2);
        } else {
            return $originalPath;
        }
    }
}