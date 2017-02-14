<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-06T14:11:11+02:00
 * File:          app/code/local/Xtento/TrackingImport/controllers/Adminhtml/Trackingimport/LogController.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Adminhtml_TrackingImport_LogController extends Xtento_TrackingImport_Controller_Abstract
{
    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function downloadAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        $importedFiles = $this->_getFilesForLogId($id);
        if (!$importedFiles) {
            return $this->_redirectReferer();
        }

        return $this->_prepareFileDownload($importedFiles);
    }

    public function massDownloadAction()
    {
        $ids = $this->getRequest()->getParam('log');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select log entries to download.'));
            return $this->_redirect('*/*/');
        }

        $allImportedFiles = array();
        try {
            foreach ($ids as $id) {
                $importedFiles = $this->_getFilesForLogId($id, true);
                if (empty($importedFiles)) {
                    continue;
                }
                foreach ($importedFiles as $filename => $content) {
                    if (isset($allImportedFiles[$filename])) {
                        $filename = 'duplicate_filename_' . $id . '_' . $filename;
                    }
                    $allImportedFiles[$filename] = $content;
                }
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            return $this->_redirectReferer();
        }

        return $this->_prepareFileDownload($allImportedFiles);
    }

    private function _getFilesForLogId($logId, $massDownload = false)
    {
        $model = Mage::getModel('xtento_trackingimport/log');
        $model->load($logId);

        if (!$model->getId()) {
            if (!$massDownload) Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This log entry (ID: %d) does not exist anymore.', $logId));
            return false;
        }

        $filesNotFound = 0;
        $importedFiles = array();
        $savedFiles = $model->getFiles();
        if (empty($savedFiles)) {
            if (!$massDownload) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('xtento_trackingimport')->__('There is nothing to download. No files have been saved with this import. (Log ID: %d)', $logId));
            return false;
        }
        $savedFiles = explode("|", $savedFiles);

        $baseFilenames = array();
        foreach ($savedFiles as $filePath) {
            array_push($baseFilenames, basename($filePath));
        }
        $baseFilenames = array_unique($baseFilenames);

        foreach ($baseFilenames as $filename) {
            $filePath = Mage::helper('xtento_trackingimport/import')->getImportBkpDir() . $logId . '_' . $filename;
            $data = @file_get_contents($filePath);
            if ($data === FALSE && !$this->getRequest()->getParam('force', false)) {
                $filesNotFound++;
                if (!$massDownload) Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('xtento_trackingimport')->__('File not found in local backup directory: %s (Log ID: %d)', $filePath, $logId));
                if ($filesNotFound == count($baseFilenames)) {
                    return false;
                }
            }
            $importedFiles[$filename] = $data;
        }
        if ($filesNotFound > 0 && $filesNotFound !== count($baseFilenames) && !$this->getRequest()->getParam('force', false)) {
            Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('xtento_trackingimport')->__('One or more files of this import have been deleted from the local backup directory. Please click <a href="%s">here</a> to download the remaining existing files. (Log ID: %d)', Mage::helper('adminhtml')->getUrl('*/*/*', array('id' => $logId, 'force' => true)), $logId));
            return false;
        }

        return $importedFiles;
    }

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('xtento_trackingimport/log');
        $model->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This log entry does not exist anymore.'));
            return $this->_redirectReferer();
        }

        try {
            $this->_deleteFilesFromFilesystem($model);
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('Log entry has been successfully deleted.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        return $this->_redirectReferer();
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('log');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select log entries to delete.'));
            return $this->_redirect('*/*/');
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('xtento_trackingimport/log');
                $model->load($id);
                $this->_deleteFilesFromFilesystem($model);
                $model->delete();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($ids)));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        return $this->_redirect('*/*/');
    }

    private function _deleteFilesFromFilesystem($model)
    {
        return true;
        // Disabled
        $savedFiles = $model->getFiles();
        if (empty($savedFiles)) {
            return false;
        }
        $savedFiles = explode("|", $savedFiles);

        $baseFilenames = array();
        foreach ($savedFiles as $filePath) {
            array_push($baseFilenames, basename($filePath));
        }
        $baseFilenames = array_unique($baseFilenames);

        foreach ($baseFilenames as $filename) {
            $filePath = Mage::helper('xtento_trackingimport/import')->getImportBkpDir() . $model->getId() . '_' . $filename;
            @unlink($filePath);
        }
        return true;
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/trackingimport')
            ->_title(Mage::helper('xtento_trackingimport')->__('Tracking Import'))->_title(Mage::helper('xtento_trackingimport')->__('Execution Log'));
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/trackingimport/log');
    }
}