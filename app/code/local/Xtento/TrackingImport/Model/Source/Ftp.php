<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-29T10:03:37+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Source/Ftp.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Source_Ftp extends Xtento_TrackingImport_Model_Source_Abstract
{
    const TYPE_FTP = 'ftp';
    const TYPE_FTPS = 'ftps';

    /*
     * Download files from a FTP server
     */
    public function testConnection()
    {
        $this->initConnection();
        return $this->getTestResult();
    }

    public function initConnection()
    {
        $this->setSource(Mage::getModel('xtento_trackingimport/source')->load($this->getSource()->getId()));
        $testResult = new Varien_Object();
        $this->setTestResult($testResult);

        if ($this->getSource()->getFtpType() == self::TYPE_FTPS) {
            if (function_exists('ftp_ssl_connect')) {
                $this->_connection = @ftp_ssl_connect($this->getSource()->getHostname(), $this->getSource()->getPort(), $this->getSource()->getTimeout());
            } else {
                $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('No FTP-SSL functions found. Please compile PHP with SSL support.'));
                return false;
            }
        } else {
            if (function_exists('ftp_connect')) {
                $this->_connection = @ftp_connect($this->getSource()->getHostname(), $this->getSource()->getPort(), $this->getSource()->getTimeout());
            } else {
                $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('No FTP functions found. Please compile PHP with FTP support.'));
                return false;
            }
        }

        if (!$this->_connection) {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('Could not connect to FTP server. Please make sure that there is no firewall blocking the outgoing connection to the FTP server and that the timeout is set to a high enough value. If this error keeps occurring, please get in touch with your server hoster / server administrator AND with the server hoster / server administrator of the remote FTP server. A firewall is probably blocking ingoing/outgoing FTP connections.'));
            return false;
        }

        if (!@ftp_login($this->_connection, $this->getSource()->getUsername(), Mage::helper('core')->decrypt($this->getSource()->getPassword()))) {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('Could not log into FTP server. Wrong username or password.'));
            return false;
        }

        if ($this->getSource()->getFtpPasv()) {
            // Enable passive mode
            if (!@ftp_pasv($this->_connection, true)) {
                #$this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('Could not enable passive mode for FTP connection.'));
                #$this->getSource()->setLastResult($this->getTestResult()->getSuccess())->setLastResultMessage($this->getTestResult()->getMessage())->save();
                #return false;
            }
        }

        if (!@ftp_chdir($this->_connection, $this->getSource()->getPath())) {
            $this->getTestResult()->setSuccess(false)->setMessage(Mage::helper('xtento_trackingimport')->__('Could not change directory on FTP server to import directory. Please make sure the directory exists (base path must be exactly the same) and that we have rights to read in the directory.'));
            return false;
        }

        $this->getTestResult()->setSuccess(true)->setMessage(Mage::helper('xtento_trackingimport')->__('Connection with FTP server tested successfully.'));
        $this->getSource()->setLastResult($this->getTestResult()->getSuccess())->setLastResultMessage($this->getTestResult()->getMessage())->save();
        return true;
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

        $filelist = ftp_nlist($this->_connection, "");
        /* Alternative code for some broken FTP servers: */
        /*
        $filelist = ftp_rawlist($this->_connection, "");
        $results = array();
        foreach ($filelist as $line) {
            $name = array_pop(explode(" ", $line));
            if ($name == '.' || $name == '..') continue;
            $results[] = $name;
        }
        $filelist = $results;
        */
        if (!$filelist) {
            $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
            $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not get file listing for import directory. You should try enabling Passive Mode in the modules FTP configuration.', $this->getSource()->getName(), $this->getSource()->getId()));
            return false;
        }
        foreach ($filelist as $filename) {
            if (!preg_match($this->getSource()->getFilenamePattern(), $filename)) {
                continue;
            }
            if (@ftp_chdir($this->_connection, $filename)) {
                // This is a directory.. do not try to download it.
                ftp_chdir($this->_connection, '..');
                continue;
            }
            $tempHandle = fopen('php://temp', 'r+');
            if (@ftp_fget($this->_connection, $tempHandle, $filename, FTP_BINARY, 0)) {
                rewind($tempHandle);
                $buffer = '';
                while (!feof($tempHandle)) {
                    $buffer .= fgets($tempHandle, 4096);
                }
                if (!empty($buffer)) {
                    $filesToProcess[] = array('source_id' => $this->getSource()->getId(), 'path' => $this->getSource()->getPath(), 'filename' => $filename, 'data' => $buffer);
                } else {
                    $this->archiveFiles(array(array('source_id' => $this->getSource()->getId(), 'path' => $this->getSource()->getPath(), 'filename' => $filename)), false, false);
                }
            } else {
                $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not download file "%s" from FTP server. Please make sure we\'ve got rights to read the file. You can also try enabling Passive Mode in the configuration section of the extension.', $this->getSource()->getName(), $this->getSource()->getId(), $filename));
            }
        }

        // Close FTP connection
        ftp_close($this->_connection);

        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false, $chDir = true)
    {
        $logEntry = Mage::registry('tracking_import_log');

        // Reconnect to archive files
        $testResult = $this->testConnection();
        if (!$testResult->getSuccess()) {
            $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
            $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): %s', $this->getSource()->getName(), $this->getSource()->getId(), $testResult->getMessage()));
            return false;
        }

        if ($this->_connection) {
            if ($forceDelete) {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@ftp_delete($this->_connection, $file['path'] . '/' . $file['filename'])) {
                        $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not delete file "%s%s" from the FTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename']));
                    }
                }
            } else if ($this->getSource()->getArchivePath() !== "") {
                if ($chDir) {
                    if (!@ftp_chdir($this->_connection, $this->getSource()->getArchivePath())) {
                        $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not change directory on FTP server to archive directory. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId()));
                        return false;
                    }
                }
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@ftp_rename($this->_connection, $file['path'] . '/' . $file['filename'], $file['filename'])) {
                        $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not move file "%s%s" to the FTP archive directory located at "%s". Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename'], $this->getSource()->getArchivePath()));
                    }
                }
            } else if ($this->getSource()->getDeleteImportedFiles() == true) {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    if (!@ftp_delete($this->_connection, $file['path'] . '/' . $file['filename'])) {
                        $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                        $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): Could not delete file "%s%s" from the FTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.', $this->getSource()->getName(), $this->getSource()->getId(), $file['path'], $file['filename']));
                    }
                }
            }
            @ftp_close($this->_connection);
        }
    }
}