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

class Taxjar_SalesTax_Block_Adminhtml_Tax_Nexus_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('nexus_grid');
        $this->setDefaultSort('region');
        $this->setDefaultDir('ASC');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('taxjar/tax_nexus')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('street', array(
            'header'        => Mage::helper('taxjar')->__('Street Address'),
            'align'         => 'left',
            'index'         => 'street',
            'filter_index'  => 'main_table.street',
        ));

        $this->addColumn('city', array(
            'header'        => Mage::helper('taxjar')->__('City'),
            'align'         => 'left',
            'index'         => 'city',
            'filter_index'  => 'main_table.city'
        ));

        $this->addColumn('region', array(
            'header'        => Mage::helper('taxjar')->__('State/Region'),
            'align'         => 'left',
            'index'         => 'region',
            'filter_index'  => 'main_table.region'
        ));

        $this->addColumn('country_id', array(
            'header'        => Mage::helper('taxjar')->__('Country'),
            'type'          => 'country',
            'align'         => 'left',
            'index'         => 'country_id',
            'filter_index'  => 'main_table.country_id',
            'sortable'      => false
        ));

        $this->addColumn('postcode', array(
            'header'        => Mage::helper('taxjar')->__('Zip/Post Code'),
            'align'         => 'left',
            'index'         => 'postcode'
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
