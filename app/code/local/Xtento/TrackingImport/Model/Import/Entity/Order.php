<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-08-14T17:39:18+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Entity/Order.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Entity_Order extends Xtento_TrackingImport_Model_Import_Entity_Abstract
{
    /*
     * Prepare import
     */
    public function prepareImport($updatesInFilesToProcess)
    {
        // Prepare actions to apply
        $actions = $this->getActions();
        $actionFields = $this->getActionFields();
        foreach ($actions as &$action) {
            $actionField = $action['field'];
            if (isset($actionFields[$actionField])) {
                $action['field_data'] = $actionFields[$actionField];
            } else {
                unset($action);
            }
        }
        $this->setActions($actions);
        return true;
    }

    protected function loadOrder($rowIdentifier)
    {
        $order = false;

        // Identify order and return $order
        $orderIdentifier = $this->getConfig('order_identifier');
        if ($orderIdentifier === 'order_increment_id') {
            $order = Mage::getModel('sales/order')->loadByIncrementId($rowIdentifier);
        }
        if ($orderIdentifier === 'order_entity_id') {
            $order = Mage::getModel('sales/order')->load($rowIdentifier);
        }
        if ($orderIdentifier === 'invoice_increment_id') {
            $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($rowIdentifier);
            if ($invoice->getId()) {
                $order = $invoice->getOrder();
            }
        }
        if ($orderIdentifier === 'shipment_increment_id') {
            $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($rowIdentifier);
            if ($shipment->getId()) {
                $order = $shipment->getOrder();
            }
        }
        if ($orderIdentifier === 'creditmemo_increment_id') {
            $creditmemo = Mage::getModel('sales/order_creditmemo')
                ->getCollection()
                ->addAttributeToFilter('increment_id', $rowIdentifier)
                ->getFirstItem();
            if ($creditmemo->getId()) {
                $order = $creditmemo->getOrder();
            }
        }

        return $order;
    }

    /*
     * Process
     */
    public function process($rowIdentifier, $updateData)
    {
        // Result (and debug information) returned to observer
        $importChanged = false;
        $importDebugMessages = array();

        // Load order
        $order = $this->loadOrder($rowIdentifier);
        if (!$order || !$order->getId()) {
            $importResult = array('changed' => false, 'debug' => Mage::helper('xtento_trackingimport')->__("Order '%s' could not be found in Magento. Skipping order.", $rowIdentifier));
            return $importResult;
        }

        // Get validation profile to see if order should be exported
        /* Alternative approach if conditions check fails, we've seen this happening in a 1.5.0.1 installation, the profile conditions were simply empty and the profile needed to be loaded again: */
        $validationProfile = $this->getProfile();
        $importConditions = $validationProfile->getData('conditions_serialized');
        if (strlen($importConditions) > 90) {
            // Force load profile for rule validation, as it fails on some stores if the profile is not re-loaded
            $validationProfile = Mage::getModel('xtento_trackingimport/profile')->load($this->getProfile()->getId());
        }
        // Check if order should be imported, matched by the "Settings & Filters" "Process order only if..." settings
        $collectionItemValidated = true;

        // Custom validation event
        Mage::dispatchEvent('xtento_trackingimport_custom_validation', array(
            'validationProfile' => $validationProfile,
            'collectionItem' => $order,
            'collectionItemValidated' => &$collectionItemValidated,
        ));

        // If not validated, skip object
        if (!($collectionItemValidated && $validationProfile->validate($order))) {
            $importDebugMessages[] = Mage::helper('xtento_trackingimport')->__("Order '%s' did not match import profile filters and will be skipped.", $rowIdentifier);
            $importChanged = false;
            unset($order);
            return $this->_returnDebugResult($importChanged, $importDebugMessages);
        }

        // Test mode - stop import
        if ($this->getTestMode()) {
            $importDebugMessages[] = Mage::helper('xtento_trackingimport')->__("Order '%s' (Row Identifier: %s) was found in Magento and would have been imported. (Test Mode)", $order->getIncrementId(), $rowIdentifier);
            return $this->_returnDebugResult(true, $importDebugMessages);
        } else {
            $importDebugMessages[] = Mage::helper('xtento_trackingimport')->__("Order '%s' was found in Magento and will be updated now.", $rowIdentifier);
        }

        // Set store and locale, so email templates and locales are sent correctly
        Mage::app()->setCurrentStore($order->getStoreId());
        Mage::app()->getLocale()->emulate($order->getStoreId());

        // Register update data for third party processing
        Mage::unregister('xtento_trackingimport_updatedata');
        Mage::register('xtento_trackingimport_updatedata', $updateData);

        // Apply actions
        #var_dump($this->getActions()); die();
        foreach (Mage::getModel('xtento_trackingimport/processor_mapping_action')->getImportActions() as $entity => $actions) {
            foreach ($actions as $actionId => $actionData) {
                if (isset($actionData['class']) && isset($actionData['method'])) {
                    $actionModel = Mage::getModel($actionData['class']);
                    if ($actionModel) {
                        try {
                            $actionModel->setData('update_data', $updateData);
                            $actionModel->setData('order', $order);
                            $actionModel->setData('actions', $this->getActions());
                            $actionModel->{$actionData['method']}();
                            $importDebugMessages = array_merge($importDebugMessages, $actionModel->getDebugMessages());
                            if ($actionModel->getHasUpdatedObject()) {
                                $importChanged = true;
                            }
                        } catch (Exception $e) {
                            // Don't break execution, but log the order related error.
                            $errorMessage = "Exception catched for order '" . $order->getId() . "' while executing action '" . $actionData['class'] . "::" . $actionData['method'] . "':\n" . $e->getMessage();
                            $importDebugMessages[] = $errorMessage;
                            Mage::registry('tracking_import_log')->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                            Mage::registry('tracking_import_log')->addResultMessage($errorMessage);
                            continue;
                        }
                    }
                }
            }
        }

        unset($order);

        // Reset locale.
        Mage::app()->getLocale()->revert();
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        return $this->_returnDebugResult($importChanged, $importDebugMessages);
    }

    private function _returnDebugResult($changed, $debugMessages)
    {
        $importResult = array('changed' => $changed, 'debug' => implode("\n", $debugMessages));
        return $importResult;
    }

    /*
     * After the import ran
     */
    public function afterRun()
    {
        // End of routine
        #$this->getLogEntry()->addDebugMessage('Done: afterRun()');
    }
}