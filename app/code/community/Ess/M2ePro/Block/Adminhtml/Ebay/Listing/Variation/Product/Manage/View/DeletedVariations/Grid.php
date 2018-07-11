<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Listing_Variation_Product_Manage_View_DeletedVariations_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    /** @var Ess_M2ePro_Model_Listing_Product $listingProduct  */
    private $listingProduct;

    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('deletedMagentoVariationsGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setPagerVisibility(false);
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        // ---------------------------------------
    }

    /**
     * @return Ess_M2ePro_Model_Listing_Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     */
    public function setListingProduct($listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    protected function _prepareCollection()
    {
        $data = $this->getListingProduct()->getSetting('additional_data', 'variations_that_can_not_be_deleted',array());

        $results = new Varien_Data_Collection();
        foreach ($data as $index => $item) {
            $temp = array(
                'id' => $index,
                'qty' => $item['qty'],
                'sku' => $item['sku'],
                'specifics' => $item['specifics'],
                'price' => $item['price'],
                'status' => Ess_M2ePro_Model_Listing_Product::STATUS_HIDDEN
            );

            $results->addItem(new Varien_Object($temp));
        }

        $this->setCollection($results);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('specifics', array(
            'header' => Mage::helper('M2ePro')->__('Specifics'),
            'align' => 'left',
            'width' => '180px',
            'sortable' => false,
            'index' => 'specifics',
            'frame_callback' => array($this, 'callbackColumnSpecifics'),
        ));

        $this->addColumn('online_sku', array(
            'header'    => Mage::helper('M2ePro')->__('SKU'),
            'align'     => 'left',
            'width'     => '150px',
            'index'     => 'sku',
            'sortable' => false,
            'frame_callback' => array($this, 'callbackColumnOnlineSku')
        ));

        $this->addColumn('qty', array(
            'header'    => Mage::helper('M2ePro')->__('Available QTY'),
            'align'     => 'right',
            'width'     => '60px',
            'index'     => 'qty',
            'sortable' => false,
            'frame_callback' => array($this, 'callbackColumnAvailableQty')
        ));

        $this->addColumn('price', array(
            'header' => Mage::helper('M2ePro')->__('Price'),
            'align' => 'right',
            'width' => '60px',
            'index' => 'price',
            'sortable' => false,
            'frame_callback' => array($this, 'callbackColumnPrice'),
        ));

        $this->addColumn('status', array(
            'header'=> Mage::helper('M2ePro')->__('Status'),
            'width' => '60px',
            'index' => 'status',
            'sortable' => false,
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

    }

    //########################################

    public function callbackColumnSpecifics($value, $row, $column, $isExport)
    {
        $html = '<div class="m2ePro-variation-attributes" style="margin-left: 5px;">';
        foreach ($row->getData('specifics') as $attribute => $option) {
            $optionHtml = '<b>' . Mage::helper('M2ePro')->escapeHtml($attribute) .
                '</b>:&nbsp;' . Mage::helper('M2ePro')->escapeHtml($option);

            $html .= $optionHtml . '<br/>';
        }
        $html .= '</div>';

        return $html;
    }

    public function callbackColumnOnlineSku($value, $row, $column, $isExport)
    {
        return $value;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $currency = $this->getListingProduct()->getMarketplace()->getChildObject()->getCurrency();

        $priceStr = Mage::app()->getLocale()->currency($currency)->toCurrency($value);

        return $priceStr;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        return '<span style="color: red;">'.Mage::helper('M2ePro')->__('Inactive').'</span>';
    }

    //########################################

    public function getGridUrl()
    {
        return false;
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}