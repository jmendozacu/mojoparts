<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T21:47:41+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Condition/Combine.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('xtento_trackingimport/import_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        $attributes = array();
        $customAttributes = Mage::getSingleton('xtento_trackingimport/import_condition_custom')->getCustomAttributes();
        foreach ($customAttributes as $code => $label) {
            if (preg_match('/xt\_billing\_/', $code)) {
                $attributes[] = array('value' => 'xtento_trackingimport/import_condition_address_billing|' . str_replace('xt_billing_', '', $code), 'label' => $label);
            } else if (preg_match('/xt\_shipping\_/', $code)) {
                $attributes[] = array('value' => 'xtento_trackingimport/import_condition_address_shipping|' . str_replace('xt_shipping_', '', $code), 'label' => $label);
            } else {
                $attributes[] = array('value' => 'xtento_trackingimport/import_condition_object|' . $code, 'label' => $label);
            }
        }

        $otherAttributes = array();
        $customOtherAttributes = Mage::getSingleton('xtento_trackingimport/import_condition_custom')->getCustomNotMappedAttributes();
        foreach ($customOtherAttributes as $code => $label) {
            $otherAttributes[] = array('value' => 'xtento_trackingimport/import_condition_object|' . $code, 'label' => $label);
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, array(
            array('value' => 'xtento_trackingimport/import_condition_product_found', 'label' => Mage::helper('salesrule')->__('Product / Item attribute combination')),
            array('value' => 'xtento_trackingimport/import_condition_product_subselect', 'label' => Mage::helper('salesrule')->__('Products subselection')),
            array('value' => 'xtento_trackingimport/import_condition_combine', 'label' => Mage::helper('salesrule')->__('Conditions combination')),
            array('label' => Mage::helper('xtento_trackingimport')->__('%s Attributes', ucfirst(Mage::registry('tracking_import_profile')->getEntity())), 'value' => $attributes),
            array('label' => Mage::helper('xtento_trackingimport')->__('Misc. %s Attributes', ucfirst(Mage::registry('tracking_import_profile')->getEntity())), 'value' => $otherAttributes),
        ));

        $additional = new Varien_Object();
        Mage::dispatchEvent('xtento_trackingimport_rule_condition_combine', array('additional' => $additional));
        if ($additionalConditions = $additional->getConditions()) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }
}
