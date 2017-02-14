<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-27T19:41:29+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Iterator/Order.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Iterator_Order extends Xtento_TrackingImport_Model_Import_Iterator_Abstract
{
    public function processUpdates($updatesInFilesToProcess)
    {
        $logEntry = Mage::registry('tracking_import_log');
        $profileConfiguration = $this->getProfile()->getConfiguration();

        $totalRecordCount = 0;
        $updatedRecordCount = 0;

        $importModel = Mage::getModel('xtento_trackingimport/import_entity_' . $this->getProfile()->getEntity());
        $importModel->setImportType($this->getImportType());
        $importModel->setTestMode($this->getTestMode());
        $importModel->setProfile($this->getProfile());

        // Get actions to apply
        $actionMapping = Mage::getModel('xtento_trackingimport/processor_mapping_action');
        $actionMapping->setMappingData(isset($profileConfiguration['action']) ? $profileConfiguration['action'] : array());
        $importModel->setActionFields($actionMapping->getMappingFields());
        $importModel->setActions($actionMapping->getMapping());

        if (!$importModel->prepareImport($updatesInFilesToProcess)) {
            $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
            $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__("Files have been parsed, however, the prepareImport function complains that there were problems preparing the import data. Stopping import. Make sure your import processor is set up right."));
            return false; // No updates to import.
        }

        foreach ($updatesInFilesToProcess as $updateFile) {
            $path = (isset($updateFile['FILE_INFORMATION']['path'])) ? $updateFile['FILE_INFORMATION']['path'] : '';
            $filename = $updateFile['FILE_INFORMATION']['filename'];
            $sourceId = $updateFile['FILE_INFORMATION']['source_id'];

            #ini_set('xdebug.var_display_max_depth', 10);
            #Zend_Debug::dump($updateFile);
            #die();
            $updatesToProcess = $updateFile['ROWS'];

            foreach ($updatesToProcess as $rowIdentifier => $updateData) {
                $totalRecordCount++;
                try {
                    if (empty($rowIdentifier)) {
                        continue;
                    }

                    $importResult = $importModel->process($rowIdentifier, $updateData);

                    if (!$importResult || isset($importResult['error'])) {
                        $logEntry->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Notice: %s | File '%s'", $importResult['error'], $path . $filename));
                        continue;
                    } else {
                        if (isset($importResult['changed']) && $importResult['changed']) {
                            $updatedRecordCount++;
                        }
                        if (isset($importResult['debug'])) {
                            $logEntry->addDebugMessage(sprintf("%s", $importResult['debug'])); // | File '" . $path . $filename . "'", $importResult['debug']));
                        }
                    }
                } catch (Mage_Core_Exception $e) {
                    // Don't break execution, but log the error.
                    $logEntry->addDebugMessage("Exception catched for row with ID '" . $rowIdentifier . "' specified in '" . $path . $filename . "' from source ID '" . $sourceId . "':\n" . $e->getMessage());
                    continue;
                }
            }
        }

        $importModel->afterRun();

        $importResult = array('total_record_count' => $totalRecordCount, 'updated_record_count' => $updatedRecordCount);
        return $importResult;
    }
}