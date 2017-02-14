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

class Taxjar_SalesTax_Block_Adminhtml_Tax_Nexus extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup      = 'taxjar';
        $this->_controller      = 'adminhtml_tax_nexus';
        $this->_headerText      = Mage::helper('taxjar')->__('Nexus Addresses');
        $this->_addButtonLabel  = Mage::helper('taxjar')->__('Add New Nexus Address');
        
        parent::__construct();
        
        $this->_addButton('sync', array(
            'label'   => Mage::helper('taxjar')->__('Sync from TaxJar'),
            'onclick' => "setLocation('{$this->getUrl('*/tax_nexus/sync')}')",
        ), 0, -1);
    }
}
