<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-08-04T14:08:21+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Action/Order/Shipment.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Action_Order_Shipment extends Xtento_TrackingImport_Model_Import_Action_Abstract
{
    public function ship()
    {
        $order = $this->getOrder();
        $updateData = $this->getUpdateData();

        // Prepare items to process
        $itemsToProcess = array();
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
        // Customization: Only ship invoiced items
        /*$itemsToProcess = array();
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getQtyInvoiced() > $orderItem->getQtyShipped() && $orderItem->getQtyToShip() > 0) {
                $itemsToProcess[strtolower($orderItem->getSku())] = ($orderItem->getQtyInvoiced() - $orderItem->getQtyShipped());
            }
        }*/
        // Prepare tracking numbers to import
        $tracksToImport = array();
        if (isset($updateData['tracks']) && !empty($updateData['tracks'])) {
            foreach ($updateData['tracks'] as $trackRecord) {
                $tracksToImport[$trackRecord['tracking_number']] = array(
                    'tracking_number' => $trackRecord['tracking_number'],
                    'carrier_code' => (isset($trackRecord['carrier_code'])) ? $trackRecord['carrier_code'] : '',
                    'carrier_name' => (isset($trackRecord['carrier_name'])) ? $trackRecord['carrier_name'] : '',
                );
            }
        }
        #var_dump($updateData, $tracksToImport); die();

        // Create Shipment
        if ($this->getActionSettingByFieldBoolean('shipment_create', 'enabled')) {
            $doShipOrder = true;
            if ($this->getActionSettingByFieldBoolean('shipment_not_without_trackingnumbers', 'enabled') && empty($tracksToImport)) {
                $doShipOrder = false;
                $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': No tracking numbers to import found, not shipping order.", $order->getIncrementId()));
            }
            if ($doShipOrder && $order->canShip()) {
                // Partial shipment support:
                $shipment = false;
                if ($this->getActionSettingByFieldBoolean('shipment_partial_import', 'enabled')) {
                    // Prepare items to ship for prepareShipment.. but only if there is SKU info in the import file.
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
                                $maxQty = $orderItem->getQtyToShip();
                                if ($qtyToProcess > $maxQty) {
                                    if ($orderItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && $orderItem->getParentItem() && $orderItem->getParentItem()->getQtyToShip() > 0) {
                                        // Has a parent item that must be shipped instead
                                        $maxQty = $orderItem->getParentItem()->getQtyToShip();
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
                            }
                        }
                    }
                    #var_dump($qtys);
                    #die();
                    if (!empty($qtys)) {
                        $shipment = $order->prepareShipment($qtys);
                        // Ship whole order if no items could be found in $qtys
                        if (!$shipment->getTotalQty()) {
                            $shipment = $order->prepareShipment();
                        }
                    } else {
                        // We're supposed to import partial shipments, but no SKUs were found at all. Do not touch shipment.
                        $doShipOrder = false;
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has NOT been shipped. Partial shipping enabled, however the items specified in the import file couldn't be found in the order.", $order->getIncrementId()));
                    }
                } else {
                    $shipment = $order->prepareShipment();
                }

                /* @var $shipment Mage_Sales_Model_Order_Shipment */
                if ($shipment && $doShipOrder) {
                    $shipment->register();
                    if ($this->getActionSettingByFieldBoolean('shipment_send_email', 'enabled')) {
                        $shipment->setEmailSent(true);
                    }
                    #if (isset($updateData['custom1']) && !empty($updateData['custom1'])) $shipment->addComment($updateData['custom1'], true);
                    $shipment->getOrder()->setIsInProcess(true);

                    $trackCount = 0;
                    foreach ($tracksToImport as $trackingNumber => $trackData) {
                        $trackCount++;
                        if (!$this->getActionSettingByFieldBoolean('shipment_multiple_trackingnumbers', 'enabled') && $trackCount > 1) {
                            // Do not import more than one tracking number.
                            continue;
                        }
                        $carrierCode = $trackData['carrier_code'];
                        $carrierName = $trackData['carrier_name'];
                        if (empty($carrierCode) && !empty($carrierName)) {
                            $carrierCode = $carrierName;
                        }
                        /*if (empty($carrierName) && !empty($carrierCode)) {
                            $carrierName = $carrierCode;
                        }*/
                        if (!empty($trackingNumber)) {
                            $trackingNumber = str_replace("'", "", $trackingNumber);
                            $track = Mage::getModel('sales/order_shipment_track')
                                ->setCarrierCode($this->_determineCarrierCode($carrierCode))
                                ->setTitle($this->_determineCarrierName($carrierName, $carrierCode));

                            // Starting with Magento CE 1.6 / EE 1.10 Magento renamed the tracking number attribute to track_number.
                            if (Mage::helper('xtcore/utils')->mageVersionCompare(Mage::getVersion(), '1.6.0.0', '>=')) {
                                $track->setTrackNumber($trackingNumber);
                            } else {
                                $track->setNumber($trackingNumber);
                            }

                            $shipment->addTrack($track);
                        }
                    }

                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();

                    $this->setHasUpdatedObject(true);

                    if ($this->getActionSettingByFieldBoolean('shipment_send_email', 'enabled')) {
                        $shipment->sendEmail(true, @$updateData['order_status_history_comment']);
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has been shipped and the customer has been notified.", $order->getIncrementId()));
                    } else {
                        $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has been shipped and the customer has NOT been notified.", $order->getIncrementId()));
                    }

                    $this->setHasUpdatedObject(true);

                    unset($shipment);
                }
            } else {
                $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s' has NOT been shipped. Already shipped or order status not allowing shipping.", $order->getIncrementId()));
            }
        }

        // All items of that order have been shipped but there are more tracking numbers? Try to load the last shipment and still add the tracking number.
        if (!$order->canShip() && !empty($tracksToImport)) {
            if ($this->getActionSettingByFieldBoolean('shipment_multiple_trackingnumbers', 'enabled')) {
                // Add a second/third/whatever tracking number to the shipment - if possible.
                /* @var $shipments Mage_Sales_Model_Mysql4_Order_Shipment_Collection */
                $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($order)
                    ->addAttributeToSelect('entity_id')
                    ->addAttributeToSort('entity_id', 'desc')
                    ->setPage(1, 1);
                $lastShipment = $shipments->getFirstItem();
                if ($lastShipment->getId()) {
                    $lastShipment = Mage::getModel('sales/order_shipment')->load($lastShipment->getId());

                    $newTrackAdded = false;

                    foreach ($tracksToImport as $trackingNumber => $trackData) {
                        $carrierCode = $trackData['carrier_code'];
                        $carrierName = $trackData['carrier_name'];
                        if (empty($carrierCode) && !empty($carrierName)) {
                            $carrierCode = $carrierName;
                        }
                        /*if (empty($carrierName) && !empty($carrierCode)) {
                            $carrierName = $carrierCode;
                        }*/
                        $trackAlreadyAdded = false;
                        foreach ($lastShipment->getAllTracks() as $trackInfo) {
                            if (Mage::helper('xtcore/utils')->mageVersionCompare(Mage::getVersion(), '1.6.0.0', '>=')) {
                                if ($trackInfo->getTrackNumber() == $trackingNumber) {
                                    $trackAlreadyAdded = true;
                                    break;
                                }
                            } else {
                                if ($trackInfo->getNumber() == $trackingNumber) {
                                    $trackAlreadyAdded = true;
                                    break;
                                }
                            }
                        }
                        if (!$trackAlreadyAdded) {
                            if (!empty($trackingNumber)) {
                                // Determine carrier and add tracking number
                                $trackingNumber = str_replace("'", "", $trackingNumber);
                                $track = Mage::getModel('sales/order_shipment_track')
                                    ->setCarrierCode($this->_determineCarrierCode($carrierCode))
                                    ->setTitle($this->_determineCarrierName($carrierName, $carrierCode));

                                // Starting with Magento CE 1.6 / EE 1.10 Magento renamed the tracking number attribute to track_number.
                                if (Mage::helper('xtcore/utils')->mageVersionCompare(Mage::getVersion(), '1.6.0.0', '>=')) {
                                    $track->setTrackNumber($trackingNumber);
                                } else {
                                    $track->setNumber($trackingNumber);
                                }

                                $lastShipment->addTrack($track)->save();
                                $newTrackAdded = true;
                            }
                        }
                    }
                    if ($newTrackAdded) {
                        if ($this->getActionSettingByFieldBoolean('shipment_send_email', 'enabled')) {
                            // Re-send shipment email when another tracking number was added.
                            $lastShipment->sendEmail(true, @$updateData['order_status_history_comment']);
                            $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': Another tracking number was added for the last shipment (Multi-Tracking) and the customer has been notified.", $order->getIncrementId()));
                        } else {
                            $this->addDebugMessage(Mage::helper('xtento_trackingimport')->__("Order '%s': Another tracking number was added for the last shipment (Multi-Tracking) and the customer has NOT been notified.", $order->getIncrementId()));
                        }
                        $this->setHasUpdatedObject(true);
                    }
                }
            }
        }

        return true;
    }

    private function _determineCarrierCode($carrierCode = '')
    {
        // In case the XTENTO Custom Carrier Trackers extension is installed, make sure no disabled carriers are used here.
        $disabledCarriers = explode(",", Mage::getStoreConfig('customtrackers/general/disabled_carriers'));
        if (!in_array($carrierCode, $disabledCarriers)) {
            // Try to find the carrier code and see if a title is assigned to it
            $carrierTitle = $this->_getCarrierTitle($carrierCode, true);
            if (!empty($carrierTitle)) {
                // Valid carrier code
                return $carrierCode;
            }
        }

        /*
         * Add your custom tracking method mapping here.
         *
         * Just copy one the if conditions and replace the values with your mapping.
         *
         * The returnedCarrierCode variable must hold a valid carrierCode. Examples are ups, usps, fedex, dhl
         *
         * If you're using the XTENTO Custom Carrier Trackers extension, you can also use your custom trackers. The number relates to the Custom Carrier Trackers configuration. Examples:
         * tracker1, tracker2, tracker3, ... tracker10
         */
        $returnedCarrierCode = 'custom';
        if (preg_match("/UPS/i", $carrierCode)) {
            $returnedCarrierCode = 'ups';
        }
        if (preg_match("/FedEx/i", $carrierCode) || preg_match("/Federal Express/i", $carrierCode)) {
            $returnedCarrierCode = 'fedex';
        }
        if (preg_match("/USPS/i", $carrierCode) || preg_match("/United States Postal Service/i", $carrierCode)) {
            $returnedCarrierCode = 'usps';
        }
        if (in_array($returnedCarrierCode, $disabledCarriers)) {
            $returnedCarrierCode = 'custom';
        }

        // No custom mapping was found
        if ($returnedCarrierCode == 'custom') {
            // Try to get the carrier code by the tracker description
            if (!isset($this->_allCarriers)) {
                $this->_allCarriers = Mage::getModel('shipping/config')->getAllCarriers();
            }
            foreach ($this->_allCarriers as $carrierCodeLoop => $carrierConfig) {
                if (in_array($carrierCodeLoop, $disabledCarriers)) {
                    continue;
                }
                $carrierLoopName = $carrierConfig->getConfigData('name');
                $carrierLoopTitle = $carrierConfig->getConfigData('title');
                if ($carrierConfig->isTrackingAvailable() && (@strpos(strtolower($carrierLoopTitle), strtolower($carrierCode)) !== false || strtolower($carrierLoopTitle) == strtolower($carrierCode) || @strpos(strtolower($carrierLoopName), strtolower($carrierCode)) !== false)) {
                    return $carrierCodeLoop;
                }
            }
        }

        return $returnedCarrierCode;
    }

    private function _determineCarrierName($carrierName, $carrierCode)
    {
        if (empty($carrierName)) {
            $carrierCode = $this->_determineCarrierCode($carrierCode);
            return $this->_getCarrierTitle($carrierCode);
        } else {
            return $carrierName;
        }
    }

    private function _getCarrierTitle($carrierCode, $returnEmpty = false)
    {
        $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');
        if (empty($carrierTitle)) {
            $carrierTitle = Mage::getStoreConfig('customtrackers/' . $carrierCode . '/title');
        }
        if (!$returnEmpty && empty($carrierTitle)) {
            return $carrierCode;
        }
        return $carrierTitle;
    }
}