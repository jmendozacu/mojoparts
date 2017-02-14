<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-06-24T12:02:10+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Action/Order/Creditmemo.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Action_Order_Creditmemo extends Xtento_TrackingImport_Model_Import_Action_Abstract
{
    public function create()
    {
        if ($this->getActionSettingByFieldBoolean('creditmemo_create', 'enabled')) {
            $order = $this->getOrder();

            // Prepare items to process
            $itemsToProcess = array();
            $updateData = $this->getUpdateData();
            if (isset($updateData['items']) && !empty($updateData['items'])) {
                foreach ($updateData['items'] as $itemRecord) {
                    $itemRecord['sku'] = strtolower($itemRecord['sku']);
                    if (isset($itemsToProcess[$itemRecord['sku']])) {
                        $itemsToProcess[$itemRecord['sku']]['qty'] = $itemsToProcess[$itemRecord['sku']]['qty'] + $itemRecord['qty'];
                    } else {
                        $itemsToProcess[$itemRecord['sku']]['sku'] = $itemRecord['sku'];
                        $itemsToProcess[$itemRecord['sku']]['qty'] = $itemRecord['qty'];
                    }
                }
            }

            // Create Credit Memo
            if ($order->canCreditmemo()) {
                $service = Mage::getModel('sales/service_order', $order);
                $creditmemo = false;
                $doRefundOrder = true;
                $data = array();
                if (@$updateData['creditmemo_shipping_amount'] != '') $data['shipping_amount'] = @$updateData['creditmemo_shipping_amount'];
                if (@$updateData['creditmemo_adjustment_positive'] != '') $data['adjustment_positive'] = @$updateData['creditmemo_adjustment_positive'];
                if (@$updateData['creditmemo_adjustment_negative'] != '') $data['adjustment_negative'] = @$updateData['creditmemo_adjustment_negative'];
                $backToStock = array();
                if ($this->getActionSettingByFieldBoolean('creditmemo_partial_import', 'enabled')) {
                    // Prepare items to invoice for prepareInvoices.. but only if there is SKU info in the import file.
                    $qtys = array();
                    foreach ($order->getAllItems() as $orderItem) {
                        // How should the item be identified in the import file?
                        if ($this->getProfileConfiguration()->getProductIdentifier() == 'sku') {
                            $orderItemSku = strtolower(trim($orderItem->getSku()));
                        } else if ($this->getProfileConfiguration()->getProductIdentifier() == 'entity_id') {
                            $orderItemSku = trim($orderItem->getProductId());
                        } else if ($this->getProfileConfiguration()->getProductIdentifier() == 'attribute') {
                            $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
                            if ($product->getId()) {
                                $orderItemSku = strtolower(trim($product->getData($this->getProfileConfiguration()->getProductIdentifierAttributeCode())));
                            } else {
                                $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': Product SKU '%s', product does not exist anymore and cannot be matched for importing.", $order->getIncrementId(), $orderItem->getSku()));
                                continue;
                            }
                        } else {
                            $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': No method found to match products.", $order->getIncrementId()));
                            return true;
                        }
                        // Item matched?
                        if (isset($itemsToProcess[$orderItemSku])) {
                            if ($itemsToProcess[$orderItemSku]['qty'] == '' || $itemsToProcess[$orderItemSku]['qty'] < 0) {
                                $qty = $orderItem->getQtyOrdered();
                            } else {
                                $qty = round($itemsToProcess[$orderItemSku]['qty']);
                            }
                            if ($qty > 0) {
                                $qtys[$orderItem->getId()] = round($qty);
                                $backToStock[$orderItem->getId()] = true;
                            } else {
                                #$qtys[$orderItem->getId()] = 0;
                            }
                        } else {
                            #$qtys[$orderItem->getId()] = 0;
                        }
                    }
                    if (!empty($qtys)) {
                        $data['qtys'] = $qtys;
                        $creditmemo = $service->prepareCreditmemo($data);
                    } else {
                        // We're supposed to import partial shipments, but no SKUs were found at all. Do not touch invoice.
                        $doRefundOrder = false;
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s', no credit memo was created. Partial credit memo creation enabled, however the items specified in the import file couldn't be found in the order.", $order->getIncrementId()));
                    }
                } else {
                    $creditmemo = $service->prepareCreditmemo($data);
                }

                if ($creditmemo && $doRefundOrder) {
                    /**
                     * Process back to stock flags
                     */
                    foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                        $orderItem = $creditmemoItem->getOrderItem();
                        $parentId = $orderItem->getParentItemId();
                        if (isset($backToStock[$orderItem->getId()])) {
                            $creditmemoItem->setBackToStock(true);
                        } elseif ($orderItem->getParentItem() && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
                            $creditmemoItem->setBackToStock(true);
                        } elseif (empty($savedData)) {
                            $creditmemoItem->setBackToStock(Mage::helper('cataloginventory')->isAutoReturnEnabled());
                        } else {
                            $creditmemoItem->setBackToStock(false);
                        }
                    }
                    $creditmemo->setRefundRequested(true);

                    $creditmemo->register();
                    if ($this->getActionSettingByFieldBoolean('creditmemo_send_email', 'enabled')) {
                        $creditmemo->setEmailSent(true);
                    }

                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($creditmemo)
                        ->addObject($creditmemo->getOrder());
                    if ($creditmemo->getInvoice()) {
                        $transactionSave->addObject($creditmemo->getInvoice());
                    }
                    $transactionSave->save();

                    if ($this->getActionSettingByFieldBoolean('creditmemo_send_email', 'enabled')) {
                        $creditmemo->sendEmail(true, '');
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has been refunded and the customer has been notified.", $order->getIncrementId()));
                    } else {
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has been refunded and the customer has NOT been notified.", $order->getIncrementId()));
                    }

                    $this->setHasUpdatedObject(true);

                    unset($creditmemo);
                }
            } else {
                $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has NOT been refunded. Order already refunded or order status not allowing credit memo creation.", $order->getIncrementId()));
            }

            return true;
        }
    }
}