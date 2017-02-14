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
 * Product Tax Classes
 *
 * @author Taxjar (support@taxjar.com)
 */
class Taxjar_SalesTax_Model_Tax_Class_Source_Product
{
    public function toOptionArray()
    {
        $output = array();
        $productClasses = Mage::getModel('tax/class')
            ->getCollection()
            ->addFieldToFilter('class_type', 'PRODUCT')
            ->load();

        foreach($productClasses as $productClass) {
            $output[] = array('value' => $productClass->getClassId(), 'label' => $productClass->getClassName());
        }

        return $output;
    }
}
