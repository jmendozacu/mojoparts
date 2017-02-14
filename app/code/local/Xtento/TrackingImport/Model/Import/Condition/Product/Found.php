<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T21:57:31+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Condition/Product/Found.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Condition_Product_Found extends Mage_SalesRule_Model_Rule_Condition_Product_Found
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('xtento_trackingimport/import_condition_product_found');
    }

    public function getNewChildSelectOptions()
    {
        $productCondition = Mage::getModel('xtento_trackingimport/import_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $pAttributes = array();
        #$iAttributes = array();
        foreach ($productAttributes as $code => $label) {
            /*if (strpos($code, 'quote_item_') === 0) {
                $iAttributes[] = array('value' => 'xtento_trackingimport/import_condition_product|' . $code, 'label' => $label);
            } else {*/
            $pAttributes[] = array('value' => 'xtento_trackingimport/import_condition_product|' . $code, 'label' => $label);
            /*}*/
        }

        $itemAttributes = array();
        $customItemAttributes = Mage::getModel('xtento_trackingimport/import_condition_custom')->getCustomNotMappedAttributes('_item');
        foreach ($customItemAttributes as $code => $label) {
            $itemAttributes[] = array('value' => 'xtento_trackingimport/import_condition_item|' . $code, 'label' => $label);
        }

        $conditions = array(
            array('value' => 'xtento_trackingimport/import_condition_combine', 'label' => Mage::helper('salesrule')->__('Conditions combination')),
            array('label' => Mage::helper('catalog')->__('Product Attribute'), 'value' => $pAttributes),
            array('label' => Mage::helper('catalog')->__('Item Attribute'), 'value' => $itemAttributes),
        );
        return $conditions;
    }
}