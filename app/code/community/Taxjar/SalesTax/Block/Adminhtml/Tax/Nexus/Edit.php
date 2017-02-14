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

class Taxjar_SalesTax_Block_Adminhtml_Tax_Nexus_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId    = 'id';
        $this->_blockGroup  = 'taxjar';
        $this->_controller  = 'adminhtml_tax_nexus';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('taxjar')->__('Save Nexus Address'));
        $this->_updateButton('delete', 'label', Mage::helper('taxjar')->__('Delete Nexus Address'));
    }

    public function getHeaderText()
    {
        if (Mage::registry('taxjar/tax_nexus')->getId()) {
            $region = Mage::registry('taxjar/tax_nexus')->getRegion();
            $countryId = Mage::registry('taxjar/tax_nexus')->getCountryId();
            $country = Mage::getModel('directory/country')->load($countryId)->getName();
            $location = isset($region) ? $this->escapeHtml($region) : $this->escapeHtml($country);

            return Mage::helper('taxjar')->__("Edit Nexus Address for %s", $location);
        }
        else {
            return Mage::helper('taxjar')->__('New Nexus Address');
        }
    }

    public function setClassType($classType)
    {
        $this->getChild('form')->setClassType($classType);
        return $this;
    }
}
