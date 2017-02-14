<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-07-18T14:11:32+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Processor/Mapping/Action.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Processor_Mapping_Action extends Xtento_TrackingImport_Model_Processor_Mapping_Abstract
{
    protected $_importFields = null;
    protected $_mappingType = 'action';

    /*
     * array(
     * 'label'
     * 'disabled'
     * 'tooltip'
     * 'default_value_disabled'
     * 'default_values'
     * )
     */
    public function getMappingFields()
    {
        if ($this->_importActions !== NULL) {
            return $this->_importActions;
        }

        $importActions = array(
            'invoice_settings' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Invoice Actions -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'invoice_create' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Create invoice for imported order'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'invoice_send_email' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Send invoice email to customer'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'invoice_capture_payment' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Capture payment for invoice (=Capture Online)'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This will try to capture the payment at the payment gateway, i.e. charge the credit card if you authorized the payment.'),
            ),
            'invoice_mark_paid' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Set invoice status to "Paid" (=Capture Offline)'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'invoice_partial_import' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Import partial invoices'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This requires the SKU and quantity fields in the import file to be filled with data. The order will then only get invoiced for the imported SKU and quantity. Otherwise the order will simply be invoiced completely.'),
            ),
            'shipment_settings' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Shipment Actions -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'shipment_create' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Create shipment for imported order'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'shipment_send_email' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Send shipment email to customer'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'shipment_not_without_trackingnumbers' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Import no shipments without tracking numbers'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('If set to "Yes" orders without tracking numbers will not be imported.'),
            ),
            'shipment_multiple_trackingnumbers' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Add tracking numbers to existing shipments & import multiple tracking numbers'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('If the import file contains more than one tracking number for one order or if the order has been already shipped, these tracking numbers will get added to the most recent shipment of the order.'),
            ),
            'shipment_partial_import' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Import partial shipments'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This requires the SKU and quantity fields in the import file to be filled with data. The order will then only get shipped for the imported SKU and quantity. Otherwise the order will simply be shipped completely.'),
            ),
            'creditmemo_settings' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Credit Memo Actions -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_create' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Create credit memo for imported order'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_send_email' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Send credit memo email to customer'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_back_to_stock' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Return refunded items to stock'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_partial_import' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Import partial credit memos'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => 0,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This requires the SKU and quantity fields in the import file to be filled with data. The order will then only get refunded for the imported SKU and quantity. Otherwise the order will simply be refunded completely.'),
            ),
            'order_status_settings' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Order (Status) Actions -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'cancel_order' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Cancel order'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => '',
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This will cancel the order and all items in the order, if it can be cancelled. This is only possible if it has not been invoiced/shipped yet.'),
            ),
            'order_status_file' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Change order status to the status defined in the "Order Status" column in the import file'),
                'default_values' => $this->getDefaultValues('yesno'),
                'default_value' => '',
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This will set the order status to whatever status is defined in the Order Status column in the import file.'),
            ),
            'order_status_change_partial' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Change order status after importing partial order'),
                'default_values' => $this->getDefaultValues('order_status'),
                'default_value' => '',
                'tooltip' => Mage::helper('xtento_trackingimport')->__('If a partial shipped/invoiced order gets imported, change the order status to a specific status.
                Attention: Do not use the "On Hold" status for partial shipments, as otherwise no further shipments can be created.'),
            ),
            'order_status_change' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Change order status after import (or when order has been completely invoiced/shipped)'),
                'default_values' => $this->getDefaultValues('order_status'),
                'default_value' => '',
                'tooltip' => Mage::helper('xtento_trackingimport')->__('You can either import the status from the file you are importing (see processor options), or statically change the order status to the status set here after the order has been completely shipped.'),
            ),
            /*'custom_actions' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Custom Actions -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'custom1' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Action 1'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'custom2' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Action 2'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'custom3' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Action 3'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),*/
        );

        // Custom event to add fields
        Mage::dispatchEvent('xtento_trackingimport_mapping_get_actions', array(
            'importActions' => &$importActions,
        ));
        // @todo: merge fields from custom/action.php so custom actions can be added

        $this->_importActions = $importActions;

        return $this->_importActions;
    }

    public function getImportActions()
    {
        // @todo: merge actions from custom/action.php so custom action models can be added
        return array(
            'order' => array(
                'creditmemo' => array(
                    'class' => 'xtento_trackingimport/import_action_order_creditmemo',
                    'method' => 'create'
                ),
                'invoice' => array(
                    'class' => 'xtento_trackingimport/import_action_order_invoice',
                    'method' => 'invoice'
                ),
                'shipment' => array(
                    'class' => 'xtento_trackingimport/import_action_order_shipment',
                    'method' => 'ship'
                ),
                'status' => array(
                    'class' => 'xtento_trackingimport/import_action_order_status',
                    'method' => 'update'
                ),
            )
        );
    }

    public function formatField($fieldName, $fieldValue)
    {
        if ($fieldName == 'qty') {
            if ($fieldValue[0] == '+') {
                $fieldValue = sprintf("%+.4f", $fieldValue);
            } else {
                $fieldValue = sprintf("%.4f", $fieldValue);
            }
        }
        if ($fieldName == 'product_identifier') {
            $fieldValue = trim($fieldValue);
        }
        return $fieldValue;
    }
}
