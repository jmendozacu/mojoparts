<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-04-29T20:54:49+02:00
 * File:          app/code/local/Xtento/TrackingImport/controllers/Adminhtml/Trackingimport/ManualController.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Adminhtml_Trackingimport_ManualController extends Xtento_TrackingImport_Controller_Abstract
{
    /*
     * Manual import handler
     */
    public function manualPostAction()
    {
        $profileId = $this->getRequest()->getPost('profile_id');
        $profile = Mage::getModel('xtento_trackingimport/profile')->load($profileId);
        if (!$profile->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('No profile selected or this profile does not exist anymore.'));
            return $this->_redirectReferer();
        }
        // Import
        try {
            $redirectParameters = array('profile_id' => $profile->getId());
            $beginTime = time();
            $importModel = Mage::getModel('xtento_trackingimport/import', array('profile' => $profile));
            if ($this->getRequest()->getPost('test_mode') !== NULL) {
                $importModel->setTestMode(true);
                $redirectParameters['test'] = 1;
            }
            if ($this->getRequest()->getPost('debug_mode') !== NULL) {
                $importModel->setDebugMode(true);
                $redirectParameters['debug'] = 1;
            }
            // Was a file uploaded manually?
            if (isset($_FILES['manual_file_upload']) && isset($_FILES['manual_file_upload']['tmp_name']) && file_exists($_FILES['manual_file_upload']['tmp_name'])) {
                $tmpFile = $_FILES['manual_file_upload']['tmp_name'];
                $filename = basename($_FILES['manual_file_upload']['name']);
                $uploadedFile = array('source_id' => '0', 'filename' => $filename, 'data' => file_get_contents($tmpFile));
            } else {
                $uploadedFile = false;
            }
            // Start import
            $importResult = $importModel->manualImport($uploadedFile);
            if (!$importResult) {
                Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('xtento_trackingimport')->__('There was an error processing this import.'));
                return $this->_redirect('*/trackingimport_manual/index', $redirectParameters);
            }
            $endTime = time();
            if ($importModel->getTestMode()) {
                $successMessage = sprintf("%d of %d records WOULD have been imported if this wasn't the test mode.", $importResult['updated_record_count'], $importResult['total_record_count']);
            } else {
                $successMessage = Mage::helper('xtento_trackingimport')->__('%d of %d records have been imported in %d seconds. If some records haven\'t been imported, they probably simply didn\'t change and didn\'t need to be updated.', $importResult['updated_record_count'], $importResult['total_record_count'], ($endTime - $beginTime));
            }
            if ($importModel->getDebugMode()) {
                Mage::registry('tracking_import_log')->addDebugMessage($successMessage);
                $this->_setDebugMessages();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess($successMessage);
            if (Mage::registry('tracking_import_log')->getResult() !== Xtento_TrackingImport_Model_Log::RESULT_SUCCESSFUL) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__(nl2br(Mage::registry('tracking_import_log')->getResultMessage())));
            }
            return $this->_redirect('*/trackingimport_manual/index', $redirectParameters);
        } catch (Exception $e) {
            if (isset($importModel) && $importModel->getDebugMode()) {
                Mage::registry('tracking_import_log')->addDebugMessage(Mage::helper('xtento_trackingimport')->__('Error: %s', nl2br($e->getMessage())));
                $this->_setDebugMessages();
            }
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Error: %s', nl2br($e->getMessage())));
            #Mage::throwException($e->getMessage());
            return $this->_redirect('*/trackingimport_manual/index', $redirectParameters);
        }
    }

    protected function _setDebugMessages()
    {
        $maxLen = 900000;
        $debugMessages = Mage::registry('tracking_import_log')->getDebugMessages();
        if (strlen($debugMessages) > $maxLen) {
            $logFilename = 'xtento_trackingimport_' . uniqid() . '.log';
            file_put_contents(Mage::getBaseDir() . DS . 'var' . DS . 'log' . DS . $logFilename, str_replace("\n", "\r\n", $debugMessages));
            $debugMessages = substr($debugMessages, 0, $maxLen) . "...\n\nThe debug messages are to loo long to be shown here. The full debug message log was instead saved in the ./var/log/" . $logFilename . " file.";
        }
        Mage::getSingleton('adminhtml/session')->setData('xtento_trackingimport_debug_log', $debugMessages);
        return $this;
    }

    /*
     * Manual import
     */
    public function indexAction()
    {
        if (!Xtento_TrackingImport_Model_System_Config_Source_Order_Status::isEnabled() || !Mage::helper('xtento_trackingimport')->getModuleEnabled()) {
            return $this->_redirect('*/trackingimport_index/disabled');
        }
        $this->_initAction()->renderLayout();
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/trackingimport')
            ->_title(Mage::helper('xtento_trackingimport')->__('Tracking Import'))->_title(Mage::helper('xtento_trackingimport')->__('Manual Import'));
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/trackingimport/manual');
    }
}