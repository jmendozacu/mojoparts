<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-06T14:11:11+02:00
 * File:          app/code/local/Xtento/TrackingImport/controllers/Adminhtml/Trackingimport/SourceController.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Adminhtml_TrackingImport_SourceController extends Xtento_TrackingImport_Controller_Abstract
{
    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('xtento_trackingimport/source');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This source no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
            if ($model->getType() == Xtento_TrackingImport_Model_Source::TYPE_LOCAL) {
                if (!$model->getPath()) {
                    $model->setPath('./var/trackingimport/');
                    $model->setArchivePath('./var/trackingimport/archive/');
                }
            }
        } else {
            // Default values
        }

        $this->_title($model->getId() ? $model->getName() : Mage::helper('xtento_trackingimport')->__('New Source'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        } else {
            // Handle certain fields
        }

        Mage::register('source', $model);

        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('xtento_trackingimport')->__('Edit Source') : Mage::helper('xtento_trackingimport')->__('New Source'), $id ? Mage::helper('xtento_trackingimport')->__('Edit Source') : Mage::helper('xtento_trackingimport')->__('New Source'))
            ->_addContent($this->getLayout()->createBlock('xtento_trackingimport/adminhtml_source_edit')->setData('action', $this->getUrl('*/*/save')))
            ->_addLeft($this->getLayout()->createBlock('xtento_trackingimport/adminhtml_source_edit_tabs'));

        $this->renderLayout();

        if (Mage::getSingleton('adminhtml/session')->getSourceDuplicated()) {
            Mage::getSingleton('adminhtml/session')->setSourceDuplicated(0);
        }
    }

    private function _testConnection()
    {
        $source = Mage::registry('source');
        $testResult = Mage::getModel('xtento_trackingimport/source_'.$source->getType(), array('source' => $source))->testConnection();
        if (!$testResult->getSuccess()) {
            Mage::getSingleton('adminhtml/session')->addWarning($testResult->getMessage());
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess($testResult->getMessage());
        }
    }

    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            $model = Mage::getModel('xtento_trackingimport/source');
            $model->setData($postData);
            $model->setLastModification(now());

            // Handle certain fields
            if ($model->getId()) {
                $model->setPath(trim(rtrim($model->getPath(), '/')) . '/');
                if ($model->getArchivePath() !== '') {
                    $model->setArchivePath(trim(rtrim($model->getArchivePath(), '/')) . '/');
                }
                if ($model->getNewPassword() !== '' && $model->getNewPassword() !== '******') {
                    $model->setPassword(Mage::helper('core')->encrypt($model->getNewPassword()));
                }
            }

            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                Mage::register('source', $model, true);
                if (isset($postData['source_id']) && !$this->getRequest()->getParam('switch', false)) {
                    $this->_testConnection();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('The source has been saved.'));
                if ($this->getRequest()->getParam('continue')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId(), 'active_tab' => $this->getRequest()->getParam('active_tab')));
                } else {
                    $this->_redirect('*/*');
                }
                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                $message = $e->getMessage();
                if (preg_match('/Notice: Undefined offset: /', $e->getMessage()) && preg_match('/SSH2/', $e->getMessage())) {
                    $message = 'This doesn\'t seem to be a SFTP server.';
                }
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('An error occurred while saving this source: '.$message));
            }

            Mage::getSingleton('adminhtml/session')->setFormData($postData);
            $this->_redirectReferer();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Could not find any data to save in the POST request. POST request too long maybe?'));
            $this->_redirect('*/*');
        }
    }

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('xtento_trackingimport/source');
        $model->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This source does not exist anymore.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('Source has been successfully deleted.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    public function duplicateAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select a source to duplicate.'));
            return $this->_redirect('*/*');
        }

        try {
            $model = Mage::getModel('xtento_trackingimport/source');
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This source does not exist anymore.'));
                return $this->_redirect('*/*');
            }

            $source = clone $model;
            $source->setEnabled(0);
            $source->setId(null);
            $source->setLastModification(now());
            $source->save();

            Mage::getSingleton('adminhtml/session')->setSourceDuplicated(1);
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('The source has been duplicated. Please make sure to enable it.'));
            $this->_redirect('*/*/edit', array('id' => $source->getId()));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*');
        }
    }

    public function massUpdateStatusAction()
    {
        $ids = $this->getRequest()->getParam('source');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select sources to modify.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('xtento_trackingimport/source');
                $model->load($id);
                $model->setEnabled($this->getRequest()->getParam('enabled'));
                $model->save();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Total of %d record(s) were successfully updated', count($ids)));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
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
            ->_title(Mage::helper('xtento_trackingimport')->__('Tracking Import'))->_title(Mage::helper('xtento_trackingimport')->__('Sources'));
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/trackingimport/source');
    }
}