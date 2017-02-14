<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T22:33:16+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Condition/Custom.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Condition_Custom extends Mage_Core_Model_Abstract
{
    protected $_customAttributes = array();
    protected $_customNotMappedAttributes = array();
    protected $_omitAttributes = array();

    public function getCustomAttributes()
    {
        // Omitted attributes
        $this->_omitAttributes = array(
            #'status' => 'Status',
            #'state' => 'State',
        );
        // Custom ready to use attributes
        $attributes = array(
            'status' => Mage::helper('salesrule')->__('Status'),
            'payment_method' => Mage::helper('salesrule')->__('Payment Method'),
            'shipping_method' => Mage::helper('salesrule')->__('Shipping Method'),
            'xt_billing_postcode' => Mage::helper('xtento_trackingimport')->__('Billing Postcode'),
            'xt_billing_region' => Mage::helper('xtento_trackingimport')->__('Billing Region'),
            'xt_billing_region_id' => Mage::helper('xtento_trackingimport')->__('Billing State/Province'),
            'xt_billing_country_id' => Mage::helper('xtento_trackingimport')->__('Billing Country'),
            'xt_shipping_postcode' => Mage::helper('salesrule')->__('Shipping Postcode'),
            'xt_shipping_region' => Mage::helper('salesrule')->__('Shipping Region'),
            'xt_shipping_region_id' => Mage::helper('salesrule')->__('Shipping State/Province'),
            'xt_shipping_country_id' => Mage::helper('salesrule')->__('Shipping Country'),
        );
        $this->_customAttributes = $attributes;
        return $attributes;
    }

    /*
     * Further attributes from this entity
     */
    public function getCustomNotMappedAttributes($type = '')
    {
        if (empty($this->_customAttributes)) {
            $this->_customAttributes = $this->getCustomAttributes();
        }
        if (!empty($this->_customNotMappedAttributes)) {
            return $this->_customNotMappedAttributes;
        }
        $entity = Mage::registry('tracking_import_profile')->getEntity();
        $resource = Mage::getSingleton('core/resource');
        $columns = array_keys($resource->getConnection('core_read')->describeTable($resource->getTableName('sales/' . $entity . $type)));
        sort($columns);
        /*$fields = Mage::getModel('sales/order')->getCollection();
        $fields->getSelect()->limit(1, 0);
        $fields->getSelect()->where('subtotal = 123');
        $fields = $fields->getData();
        $fields = array_keys($fields[0]);
        sort($fields);*/

        $attributes = array();
        foreach ($columns as $column) {
            if (isset($this->_customAttributes[$column]) || isset($this->_omitAttributes[$column])) {
                continue;
            }
            $attributes[$column] = $column;
        }
        $this->_customNotMappedAttributes = $attributes;
        return $attributes;
    }
}