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

class Taxjar_SalesTax_Block_Adminhtml_Tax_Class_Grid extends Mage_Adminhtml_Block_Tax_Class_Grid
{
    protected function _prepareColumns()
    {
        parent::_prepareColumns();   

        $connected = Mage::getStoreConfig('tax/taxjar/connected');

        if ($connected && $this->getClassType() == 'PRODUCT') {
            $this->addColumn(
                'tj_salestax_code', array(
                    'header' => Mage::helper('taxjar')->__('TaxJar Category Code'),
                    'align'  => 'left',
                    'index'  => 'tj_salestax_code',
                    'width'  => '150px',
                    'type'   => 'text'
                )
            );
        }

        return $this;
    }
}
