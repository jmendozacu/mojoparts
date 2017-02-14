<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T21:46:55+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Import/Condition/Product/Subselect.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Import_Condition_Product_Subselect extends Mage_SalesRule_Model_Rule_Condition_Product_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('xtento_trackingimport/import_condition_product_subselect')->setValue(null);
    }

    public function loadArray($arr, $key = 'conditions')
    {
        $this->setAttribute($arr['attribute']);
        $this->setOperator($arr['operator']);
        parent::loadArray($arr, $key);
        return $this;
    }

    public function asXml($containerKey = 'conditions', $itemKey = 'condition')
    {
        $xml = '<attribute>' . $this->getAttribute() . '</attribute>'
            . '<operator>' . $this->getOperator() . '</operator>'
            . parent::asXml($containerKey, $itemKey);
        return $xml;
    }

    public function loadAttributeOptions()
    {
        $hlp = Mage::helper('salesrule');
        $this->setAttributeOption(array(
            'qty_ordered' => $hlp->__('total quantity ordered (for order imports)'),
            'qty_invoiced' => $hlp->__('total quantity invoiced (for order imports)'),
            'qty_refunded' => $hlp->__('total quantity refunded (for order imports)'),
            'qty' => $hlp->__('total quantity (for invoice/shipment/credit memo imports)'),
            'base_row_total' => $hlp->__('total amount'),
        ));
        return $this;
    }

    public function loadOperatorOptions()
    {
        $this->setOperatorOption(array(
            '==' => Mage::helper('rule')->__('is'),
            '!=' => Mage::helper('rule')->__('is not'),
            '>=' => Mage::helper('rule')->__('equals or greater than'),
            '<=' => Mage::helper('rule')->__('equals or less than'),
            '>' => Mage::helper('rule')->__('greater than'),
            '<' => Mage::helper('rule')->__('less than'),
            '()' => Mage::helper('rule')->__('is one of'),
            '!()' => Mage::helper('rule')->__('is not one of'),
        ));
        return $this;
    }

    public function getValueElementType()
    {
        return 'text';
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            Mage::helper('salesrule')->__("If %s %s %s for a subselection of items matching %s of these conditions:",
                $this->getAttributeElement()->getHtml(),
                $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml(),
                $this->getAggregatorElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }
        return $html;
    }

    /**
     * validate
     *
     * @param Varien_Object $object Quote
     * @return boolean
     */
    public function validate(Varien_Object $object)
    {
        if (!$this->getConditions()) {
            return false;
        }

        #var_dump($object->getAllItems()); die();

        $attr = $this->getAttribute();
        $total = 0;
        foreach ($object->getAllItems() as $item) {
            if (parent::validate($item)) {
                $total += $item->getData($attr);
            }
        }

        #var_dump($attr, $total); die();

        return $this->validateAttribute($total);
    }
}
