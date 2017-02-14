<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-20T13:57:07+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Processor/Mapping/Fields.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Processor_Mapping_Fields extends Xtento_TrackingImport_Model_Processor_Mapping_Abstract
{
    protected $_importFields = null;
    protected $_mappingType = 'fields';

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
        if ($this->_importFields !== NULL) {
            return $this->_importFields;
        }

        $importFields = array(
            'order_info' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Order Fields -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'order_identifier' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Order Identifier (Order #, ...) *'),
                'default_value_disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This is the field used to identify orders in Magento. Usually the order number, but based on the setting in the Import Settings tab, this may be the invoice number for example too.'),
            ),
            'order_status' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Order Status'),
                'default_values' => $this->getDefaultValues('order_status'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('This is mapped to the order status set for the order. This must be a valid order status code seen at System > Order Statuses.'),
            ),
            'order_status_history_comment' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Order History Comment'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Comment that is added into the order status history after the order was updated.'),
            ),
            /*'invoice_info' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Invoice Fields -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),*/
            'shipment_info' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Shipment Fields -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'tracking_number' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Tracking Number'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('The tracking number added to the imported/updated shpiment'),
                'group' => 'tracks'
            ),
            'carrier_code' => array(
                'label' => 'Shipping Carrier Code',
                'default_values' => $this->getDefaultValues('shipping_carriers'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('The carrier code for the tracking number imported. Make sure this is a valid tracker code, out of the box it is usps, ups, dhl or fedex. If you are using our Custom Carrier Trackers extension, its tracker codes are tracker1 through tracker15.'),
                'group' => 'tracks'
            ),
            'carrier_name' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Shipping Carrier Name'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Shipping Carrier Title for tracking number'),
                'group' => 'tracks'
            ),
            'creditmemo_info' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Credit Memo Fields -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_shipping_amount' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Credit Memo Shipping Amount'),
                'default_value_disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_adjustment_positive' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Adjustment Refund'),
                'default_value_disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'creditmemo_adjustment_negative' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Adjustment Fee'),
                'default_value_disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'item_info' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Item Fields -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'sku' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Product Identifier (SKU, ...)'),
                'default_value_disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Shipped/Invoiced SKU'),
                'group' => 'items'
            ),
            'qty' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Quantity'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Shipped/Invoiced SKU'),
                'group' => 'items'
            ),
            'custom_fields' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('-- Custom Fields -- '),
                'disabled' => true,
                'tooltip' => Mage::helper('xtento_trackingimport')->__(''),
            ),
            'custom1' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Field 1'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Used to store custom data. This can be used to map/check data in the actions tab for example.'),
            ),
            'custom2' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Field 2'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Used to store custom data. This can be used to map/check data in the actions tab for example.'),
            ),
            'custom3' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Field 3'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Used to store custom data. This can be used to map/check data in the actions tab for example.'),
            ),
            'custom4' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Field 4'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Used to store custom data. This can be used to map/check data in the actions tab for example.'),
            ),
            'custom5' => array(
                'label' => Mage::helper('xtento_trackingimport')->__('Custom Field 5'),
                'tooltip' => Mage::helper('xtento_trackingimport')->__('Used to store custom data. This can be used to map/check data in the actions tab for example.'),
            ),

            /*
            'custom_fields' => array(
                'label' => '-- Custom Import Fields -- ',
                'disabled' => true
            ),
            //'custom1' => array('label' => 'Custom Data 1'),
            //'custom2' => array('label' => 'Custom Data 2'),
            */
        );

        // Custom event to add fields
        Mage::dispatchEvent('xtento_trackingimport_mapping_get_fields', array(
            'importFields' => &$importFields,
        ));

        // @todo: merge fields from custom/fields.php so custom fields can be added

        $this->_importFields = $importFields;

        return $this->_importFields;
    }

    public function formatField($fieldName, $fieldValue)
    {
        if ($fieldName == 'qty') {
        }
        if ($fieldName == 'order_identifier') {
            $fieldValue = trim($fieldValue);
        }
        return $fieldValue;
    }
}
