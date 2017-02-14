<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T21:46:55+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Condition/Item.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Condition_Item extends Mage_SalesRule_Model_Rule_Condition_Address
{
    public function loadAttributeOptions()
    {
        $attributes = array();
        $attributes = array_merge($attributes, Mage::getSingleton('xtento_trackingimport/import_condition_custom')->getCustomNotMappedAttributes('_item'));
        $this->setAttributeOption($attributes);
        return $this;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'stock_id':
                return 'numeric';
        }
        // Get type for custom
        return 'string';
    }

    public function getValueElementType()
    {
        /*switch ($this->getAttribute()) {
            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }*/
        return 'text';
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            $this->setData('value_select_options', array());
        }
        return $this->getData('value_select_options');
    }

    /**
     * Validate Address Rule Condition
     *
     * @param Varien_Object $object
     * @return bool
     */
    public function validate(Varien_Object $object)
    {
        #var_dump($this->validateAttribute($object->getData($this->getAttribute())), $object->getData($this->getAttribute()), $this->getAttribute(), $this->getValueParsed()); die();
        return $this->validateAttribute($object->getData($this->getAttribute()));
    }
}
