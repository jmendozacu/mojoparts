<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-07-18T14:30:29+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Action/Order/Status.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Action_Order_Status extends Xtento_TrackingImport_Model_Import_Action_Abstract
{
    public function update()
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = $this->getOrder();
        $updateData = $this->getUpdateData();

        if ($this->getActionSettingByFieldBoolean('cancel_order', 'enabled')) {
            if ($order->canCancel()) {
                $order->cancel()->save();
                $this->addDebugMessage(
                    Mage::helper('xtento_trackingimport')->__(
                        "Order '%s': Order has been cancelled.",
                        $order->getIncrementId()
                    )
                );
                $this->setHasUpdatedObject(true);
            } else {
                $this->addDebugMessage(
                    Mage::helper('xtento_trackingimport')->__(
                        "Order '%s': Order cannot be cancelled.",
                        $order->getIncrementId()
                    )
                );
            }
        }

        $statusFromFile = isset($updateData['order_status']) ? $updateData['order_status'] : '';
        $statusSet = false;
        if (($this->getActionSettingByFieldBoolean('invoice_create', 'enabled') && $order->canInvoice()) || ($this->getActionSettingByFieldBoolean('shipment_create', 'enabled') && $order->canShip())) {
            // Partially imported order. Let's see if we're supposed to change the order status after importing a partial order.
            if ($this->getActionSettingByField('order_status_change_partial', 'value') != '') {
                // "Change status after import" has been set. This value overrides the file import status.
                $statusToChangeTo = $this->getActionSettingByField('order_status_change_partial', 'value');
                if ($order->getStatus() !== $statusToChangeTo) {
                    #$order->setStatus($statusToChangeTo)->save();
                    $statusSet = $this->_changeOrderStatus($order, $statusToChangeTo, @$updateData['order_status_history_comment']);
                }
            }
        } else if ($this->getActionSettingByField('order_status_change', 'value') != '') {
            // "Change status after import" has been set. This value overrides the file import status.
            $statusToChangeTo = $this->getActionSettingByField('order_status_change', 'value');
            if ($order->getStatus() !== $statusToChangeTo) {
                #$order->setStatus($statusToChangeTo)->save();
                $statusSet = $this->_changeOrderStatus($order, $statusToChangeTo, @$updateData['order_status_history_comment']);
            }
        } else if (!empty($statusFromFile) && $this->getActionSettingByFieldBoolean('order_status_file', 'enabled')) {
            // Status coming from the imported file is not empty. Then let's set this status.
            $statuses = Mage::getSingleton('xtento_trackingimport/system_config_source_order_status')->toArray();
            // Make sure the "new" "$status" is a valid Magento status before setting it:
            if (!in_array($statusFromFile, $statuses)) {
                $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Attempted to set order status '" . $statusFromFile . "' for order '" . $order->getIncrementId() . "', however that is no status that exists in your Magento installation. Status not changed."));
            } else {
                if ($order->getStatus() !== $statusFromFile) {
                    #$order->setStatus($statusFromFile)->save();
                    $statusSet = $this->_changeOrderStatus($order, $statusFromFile, @$updateData['order_status_history_comment']);
                    // Alternative for Magento Enterprise Edition:
                    /*
                       $order->addStatusHistoryComment('', $statusFromFile)
                         ->setIsVisibleOnFront(0)
                         ->setIsCustomerNotified(0);
                       $order->save();
                     */
                }
            }
        }

        if (!$statusSet && !@empty($updateData['order_status_history_comment'])) {
            $order->addStatusHistoryComment(@$updateData['order_status_history_comment'])->save();
            $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': Status not updated, order comment added.", $order->getIncrementId()));
            $this->setHasUpdatedObject(true);
        } else if ($statusSet) {
            $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': Status updated to '%s'. (If the status is different from what you wanted to set, the order state is different already and your order status can't be set anymore)", $order->getIncrementId(), $order->getStatus()));
            $this->setHasUpdatedObject(true);
        }

        return true;
    }

    protected function _changeOrderStatus($order, $newOrderStatus, $orderComment)
    {
        if ($order->getStatus() == $newOrderStatus) {
            return false;
        }
        $this->_setOrderState($order, $newOrderStatus);
        $order->setStatus($newOrderStatus)->save();
        if (Mage::helper('xtcore/utils')->mageVersionCompare(Mage::getVersion(), '1.4.0.0', '>=')) {
            $order->addStatusHistoryComment(!empty($orderComment) ? $orderComment : '', $order->getStatus())->setIsCustomerNotified(0);
        } else {
            // 1.3 compatibility
            $order->addStatusToHistory($order->getStatus());
        }
        // Compatibility fix for Amasty_OrderStatus
        $statusModel = Mage::registry('amorderstatus_history_status');
        if (($statusModel && $statusModel->getNotifyByEmail()) || Mage::registry('advancedorderstatus_notifications')) {
            $order->sendOrderUpdateEmail();
        }
        // End
        $order->save();
        return true;
    }

    protected function _setOrderState($order, $newOrderStatus)
    {
        if (Mage::helper('xtcore/utils')->mageVersionCompare(Mage::getVersion(), '1.5.0.0', '>=')) {
            if (!isset($this->_orderStates)) {
                $this->_orderStates = Mage::getModel('sales/order_config')->getStates();
            }
            foreach ($this->_orderStates as $state => $label) {
                foreach (Mage::getModel('sales/order_config')->getStateStatuses($state, false) as $status) {
                    if ($status == $newOrderStatus) {
                        $order->setData('state', $state);
                        return;
                    }
                }
            }
        }
    }
}