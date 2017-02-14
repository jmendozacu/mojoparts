<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-06-26T15:46:30+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Action/Order/Invoice.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Action_Order_Invoice extends Xtento_TrackingImport_Model_Import_Action_Abstract
{
    public function invoice()
    {
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

        // Create Invoice
        if ($this->getActionSettingByFieldBoolean('invoice_create', 'enabled')) {
            if ($order->canInvoice()) {
                $invoice = false;
                $doInvoiceOrder = true;
                // Partial invoicing support:
                if ($this->getActionSettingByFieldBoolean('invoice_partial_import', 'enabled')) {
                    // Prepare items to invoice for prepareInvoices
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
                                $qtyToProcess = $itemsToProcess[$orderItemSku]['qty'];
                                $maxQty = $orderItem->getQtyToInvoice();
                                if ($qtyToProcess > $maxQty) {
                                    if ($orderItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && $orderItem->getParentItem() && $orderItem->getParentItem()->getQtyToInvoice() > 0) {
                                        // Has a parent item that must be invoiced instead
                                        $maxQty = $orderItem->getParentItem()->getQtyToInvoice();
                                        if ($qtyToProcess > $maxQty) {
                                            $qty = round($maxQty);
                                            $itemsToProcess[$orderItemSku]['qty'] -= $maxQty;
                                        } else {
                                            $qty = round($qtyToProcess);
                                        }
                                    } else {
                                        $qty = round($maxQty);
                                        $itemsToProcess[$orderItemSku]['qty'] -= $maxQty;
                                    }
                                } else {
                                    $qty = round($qtyToProcess);
                                }
                            }
                            if ($qty > 0) {
                                $qtys[$orderItem->getId()] = round($qty);
                            } else {
                                $qtys[$orderItem->getId()] = 0;
                            }
                        } else {
                            $qtys[$orderItem->getId()] = 0;
                        }
                    }
                    if (!empty($qtys)) {
                        $invoice = $order->prepareInvoice($qtys);
                    } else {
                        // We're supposed to import partial shipments, but no SKUs were found at all. Do not touch invoice.
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has NOT been invoiced. Partial invoicing enabled, however the items specified in the import file couldn't be found in the order.", $order->getIncrementId()));
                        $doInvoiceOrder = false;
                    }
                } else {
                    $invoice = $order->prepareInvoice();
                }

                if ($invoice && $doInvoiceOrder) {
                    if ($this->getActionSettingByFieldBoolean('invoice_capture_payment', 'enabled') && $invoice->canCapture()) {
                        // Capture order online
                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    } else if ($this->getActionSettingByFieldBoolean('invoice_mark_paid', 'enabled')) {
                        // Set invoice status to Paid
                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                    }

                    $invoice->register();
                    if ($this->getActionSettingByFieldBoolean('invoice_send_email', 'enabled')) {
                        $invoice->setEmailSent(true);
                    }
                    $invoice->getOrder()->setIsInProcess(true);

                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

                    $this->setHasUpdatedObject(true);

                    if ($this->getActionSettingByFieldBoolean('invoice_send_email', 'enabled')) {
                        $invoice->sendEmail(true, '');
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has been invoiced and the customer has been notified.", $order->getIncrementId()));
                    } else {
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has been invoiced and the customer has NOT been notified.", $order->getIncrementId()));
                    }

                    unset($invoice);
                }
            } else {
                $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has NOT been invoiced. Order already invoiced or order status not allowing invoicing.", $order->getIncrementId()));
            }
        }

        return true;
    }
}