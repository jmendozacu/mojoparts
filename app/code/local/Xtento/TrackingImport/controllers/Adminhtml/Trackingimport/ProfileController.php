<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-21T23:57:12+02:00
 * File:          app/code/local/Xtento/TrackingImport/controllers/Adminhtml/Trackingimport/ProfileController.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Adminhtml_TrackingImport_ProfileController extends Xtento_TrackingImport_Controller_Abstract
{
    public function indexAction()
    {
        if (!Xtento_TrackingImport_Model_System_Config_Source_Order_Status::isEnabled() || !Mage::helper('xtento_trackingimport')->getModuleEnabled()) {
            return $this->_redirect('*/trackingimport_index/disabled');
        }
        $this->_initAction()->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('xtento_trackingimport/profile');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This profile no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
            if (!$model->getEntity()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('No import entity has been set for this profile.'));
                $this->_redirect('*/*/');
                return;
            }
        } else {
            // Default values
            #$model->setSaveFilesLocalCopy(1);
        }
        #var_dump($model->getData()); die();

        $this->_title($model->getId() ? $model->getName() : Mage::helper('xtento_trackingimport')->__('New Profile'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        } else {
            // Handle certain fields
            $fields = array('store_ids', 'customer_groups', 'event_observers', 'import_filter_status', 'import_filter_product_type');
            foreach ($fields as $field) {
                $value = $model->getData($field);
                if (!is_array($value)) {
                    $model->setData($field, explode(',', $value));
                }
            }
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        Mage::unregister('tracking_import_profile');
        Mage::register('tracking_import_profile', $model);

        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('xtento_trackingimport')->__('Edit Profile') : Mage::helper('xtento_trackingimport')->__('New Profile'), $id ? Mage::helper('xtento_trackingimport')->__('Edit Profile') : Mage::helper('xtento_trackingimport')->__('New Profile'))
            ->_addContent($this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit')->setData('action', $this->getUrl('*/*/save')))
            ->_addLeft($this->getLayout()->createBlock('xtento_trackingimport/adminhtml_profile_edit_tabs'));

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(1);
        $this->getLayout()->getBlock('head')->setCanLoadRulesJs(1);

        $this->renderLayout();

        if (Mage::getSingleton('adminhtml/session')->getProfileDuplicated()) {
            Mage::getSingleton('adminhtml/session')->setProfileDuplicated(0);
        }
    }

    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            $model = Mage::getModel('xtento_trackingimport/profile');
            if (isset($postData['rule']['conditions'])) {
                $postData['conditions'] = $postData['rule']['conditions'];
                unset($postData['rule']);
            }
            $model->setData($postData);
            if ($model->getId()) {
                $profile = Mage::getModel('xtento_trackingimport/profile')->load($model->getId());
                Mage::unregister('tracking_import_profile');
                Mage::register('tracking_import_profile', $profile);
                try {
                    $model->loadPost($postData);
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('An error occurred while saving this import profile: ' . $e->getMessage()));
                }
            }
            $model->setLastModification(now());

            if (!$model->getId()) {
                $model->setEnabled(1);
            }

            // Prepare mapping
            if (isset($postData['mapping'])) {
                $mapping = $this->_prepareMappingForSave($postData['mapping']);
                if ($mapping !== false) {
                    $postData['mapping'] = $mapping;
                } else {
                    unset($postData['mapping']);
                }
            }
            // Prepare actions
            if (isset($postData['action'])) {
                $actions = $this->_prepareMappingForSave($postData['action']);
                if ($actions !== false) {
                    $postData['action'] = $actions;
                } else {
                    unset($postData['action']);
                }
            }

            $skippedFields = array('form_key', 'page', 'limit', 'log_id');
            $configurationToSave = array();
            $tableFields = Mage::getSingleton('core/resource')->getConnection('core_read')->describeTable(Mage::getSingleton('core/resource')->getTableName('xtento_trackingimport_profile'));
            foreach ($postData as $confKey => $confValue) {
                if (!isset($tableFields[$confKey]) && !in_array($confKey, $skippedFields) && !preg_match('/col_/', $confKey)) {
                    if (is_array($confValue) && isset($confValue['from']) && isset($confValue['to'])) {
                        continue;
                    }
                    $configurationToSave[$confKey] = $confValue;
                }
            }
            $model->setConfiguration($configurationToSave);

            try {
                $model->save();

                Mage::getSingleton('adminhtml/session')->setFormData(false);
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('The import profile has been saved.'));
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
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('An error occurred while saving this import profile: ' . $e->getMessage()));
            }

            Mage::getSingleton('adminhtml/session')->setFormData($postData);
            $this->_redirectReferer();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Could not find any data to save in the POST request. POST request too long maybe?'));
            $this->_redirect('*/*');
        }
    }

    private function _prepareMappingForSave($mapping)
    {
        if (is_array($mapping)) {
            if (!isset($mapping['__save_data']) && isset($mapping['__type'])) {
                // save_data was not set by our Javascript.. let's better load the fail-safe database configuration instead of risking losing the mapping
                return false;
            } else {
                unset($mapping['__empty']);
                unset($mapping['__type']);
                unset($mapping['__save_data']);
                foreach ($mapping as $id => $data) {
                    if (!isset($data['field'])) {
                        unset($mapping[$id]);
                        continue;
                    }
                    if ($data['field'] == '') {
                        unset($mapping[$id]);
                    }
                }
            }
        }
        return $mapping;
    }

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $model = Mage::getModel('xtento_trackingimport/profile');
        $model->load($id);

        if ($id && !$model->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This profile does not exist anymore.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('Profile has been successfully deleted.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    public function duplicateAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select a profile to duplicate.'));
            return $this->_redirect('*/*');
        }

        try {
            $model = Mage::getModel('xtento_trackingimport/profile');
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('This profile does not exist anymore.'));
                return $this->_redirect('*/*');
            }

            $profile = clone $model;
            $profile->setEnabled(0);
            $profile->setId(null);
            $profile->setLastModification(now());
            $profile->setLastExecution(null);
            $profile->save();

            Mage::getSingleton('adminhtml/session')->setProfileDuplicated(1);
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('xtento_trackingimport')->__('The profile has been duplicated. Please make sure to enable it.'));
            $this->_redirect('*/*/edit', array('id' => $profile->getId()));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*');
        }
    }

    public function newConditionHtmlAction()
    {
        $profileId = $this->getRequest()->getParam('profile_id');
        $profile = Mage::getModel('xtento_trackingimport/profile')->load($profileId);
        Mage::unregister('tracking_import_profile');
        Mage::register('tracking_import_profile', $profile);

        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('salesrule/rule'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function editFieldConfigurationAction()
    {
        $id = $this->getRequest()->getParam('profile_id');
        $rowId = $this->getRequest()->getParam('row_id');
        if (!$id) {
            $this->getResponse()->setBody(Mage::helper('xtento_trackingimport')->__('No profile ID supplied.'));
            return;
        }

        try {
            $model = Mage::getModel('xtento_trackingimport/profile');
            $model->load($id);
            if (!$model->getId()) {
                $this->getResponse()->setBody(Mage::helper('xtento_trackingimport')->__('This profile does not exist anymore.'));
                return;
            }
            Mage::unregister('tracking_import_profile');
            Mage::register('tracking_import_profile', $model);

            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
            return;
        }
    }

    public function editActionConditionAction()
    {
        $id = $this->getRequest()->getParam('profile_id');
        $field = $this->getRequest()->getParam('field');
        if (!$id) {
            $this->getResponse()->setBody(Mage::helper('xtento_trackingimport')->__('No profile ID supplied.'));
            return;
        }

        try {
            $model = Mage::getModel('xtento_trackingimport/profile');
            $model->load($id);
            if (!$model->getId()) {
                $this->getResponse()->setBody(Mage::helper('xtento_trackingimport')->__('This profile does not exist anymore.'));
                return;
            }
            Mage::unregister('tracking_import_profile');
            Mage::register('tracking_import_profile', $model);

            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
            return;
        }
    }

    public function sourceAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function sourceGridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function logGridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function historyGridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('profile');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select profiles to delete.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('xtento_trackingimport/profile');
                $model->load($id);
                $model->delete();
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($ids)));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    public function massUpdateStatusAction()
    {
        $ids = $this->getRequest()->getParam('profile');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('xtento_trackingimport')->__('Please select profiles to modify.'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            foreach ($ids as $id) {
                $model = Mage::getModel('xtento_trackingimport/profile');
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

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/trackingimport')
            ->_title(Mage::helper('xtento_trackingimport')->__('Tracking Import'))->_title(Mage::helper('xtento_trackingimport')->__('Profiles'));
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/trackingimport/profile');
    }
}