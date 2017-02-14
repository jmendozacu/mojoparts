<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-22T18:20:02+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Source/Httpdownload.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Source_Httpdownload extends Xtento_TrackingImport_Model_Source_Abstract
{
    public function testConnection()
    {
        $testResult = $this->initConnection();
        return $testResult;
    }

    public function initConnection()
    {
        $testResult = new Varien_Object();
        $testResult->setSuccess(true)->setMessage(Mage::helper('xtento_trackingimport')->__('HTTP Download class initialized.'));
        $this->getSource()->setLastResult($testResult->getSuccess())->setLastResultMessage($testResult->getMessage())->save();
        return $testResult;
    }

    public function loadFiles()
    {
        // Init connection
        $this->initConnection();

        $curlClient = curl_init();
        curl_setopt($curlClient, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlClient, CURLOPT_HEADER, false);
        curl_setopt($curlClient, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlClient, CURLOPT_URL, $this->getSource()->getCustomFunction());
        curl_setopt($curlClient, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curlClient);

        if ($result === false) {
            // curl_error
            $logEntry = Mage::registry('tracking_import_log');
            $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
            $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source "%s" (ID: %s): There was a problem downloading the file "%s", probably a firewall is blocking the connection: curl_error: %s', $this->getSource()->getName(), $this->getSource()->getId(), $this->getSource()->getCustomFunction(), curl_error($curlClient)));
        }

        curl_close($curlClient);

        $filesToProcess[] = array('source_id' => $this->getSource()->getId(), 'path' => '', 'filename' => basename($this->getSource()->getCustomFunction()), 'data' => $result);

        // Return files to process
        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false)
    {

    }
}