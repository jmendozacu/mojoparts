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

/**
 * Backup Dropdown Renderer
 * Handle state based on presence of API token
 */
class Taxjar_SalesTax_Block_Adminhtml_Backup extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $apiKey = trim(Mage::getStoreConfig('tax/taxjar/apikey'));
        
        if ($apiKey) {
            $this->_cacheElementValue($element);
        }

        return parent::_getElementHtml($element);
    }
    
    protected function _cacheElementValue(Varien_Data_Form_Element_Abstract $element)
    {
        $elementValue = (string) $element->getValue();
        Mage::app()->getCache()->save($elementValue, 'taxjar_salestax_config_backup', array('TAXJAR_SALESTAX_BACKUP'), null);
    }
}
