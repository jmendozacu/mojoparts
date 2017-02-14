<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Taxjar_SalesTax_Block_Adminhtml_Tax_Class_Edit_Form extends Mage_Adminhtml_Block_Tax_Class_Edit_Form
{
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $connected = Mage::getStoreConfig('tax/taxjar/connected');
        $fieldset = $this->getForm()->getElement('base_fieldset');
        $currentClass = Mage::registry('tax_class');
        
        if ($connected && $this->getClassType() == 'PRODUCT') {
            $fieldset->addField(
                'tj_salestax_code', 'select', array(
                    'name'  => 'tj_salestax_code',
                    'label' => Mage::helper('taxjar')->__('TaxJar Category'),
                    'value' => $currentClass->getTjSalestaxCode(),
                    'values' => Mage::getModel('taxjar/categories')->toOptionArray()
                )
            );    
        }

        return $this;
    }
}
