<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Common_Amazon_Listing_Template_ShippingTemplate_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $marketplaceId;
    protected $productsIds;

    //########################################

    public function __construct()
    {
        parent::__construct();

        $this->setId('amazonTemplateShippingTemplateGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);

        // ---------------------------------------
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getMarketplaceId()
    {
        return $this->marketplaceId;
    }

    /**
     * @param mixed $marketplaceId
     */
    public function setMarketplaceId($marketplaceId)
    {
        $this->marketplaceId = $marketplaceId;
    }

    /**
     * @param mixed $productsIds
     */
    public function setProductsIds($productsIds)
    {
        $this->productsIds = $productsIds;
    }

    /**
     * @return mixed
     */
    public function getProductsIds()
    {
        return $this->productsIds;
    }

    // ---------------------------------------

    protected function _prepareCollection()
    {
        $this->setNoTemplatesText();

        /** @var Ess_M2ePro_Model_Mysql4_Amazon_Template_ShippingTemplate_Collection $collection */
        $collection = Mage::getModel('M2ePro/Amazon_Template_ShippingTemplate')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', array(
            'header'       => Mage::helper('M2ePro')->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnTitle')
        ));

        $this->addColumn('action', array(
            'header'       => Mage::helper('M2ePro')->__('Action'),
            'align'        => 'left',
            'type'         => 'number',
            'width'        => '55px',
            'index'        => 'id',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnAction')
        ));
    }

    protected function _prepareLayout()
    {
        $shippingMode = $this->getRequest()->getParam('shipping_mode');
        $this->setChild('refresh_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'id' => 'shipping_template_refresh_btn',
                    'label'     => Mage::helper('M2ePro')->__('Refresh'),
                    'onclick'   => "ListingGridHandlerObj.templateShippingHandler.loadGrid('{$shippingMode}')"
                ))
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getRefreshButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    //########################################

    public function getMainButtonsHtml()
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $templateEditUrl = $this->getUrl('*/adminhtml_common_amazon_template_shippingTemplate/edit', array(
            'id' => $row->getData('id')
        ));

        $title = Mage::helper('M2ePro')->escapeHtml($value);

        return <<<HTML
<a target="_blank" href="{$templateEditUrl}">{$title}</a>
HTML;

    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $assignText = Mage::helper('M2ePro')->__('Assign');

        return <<<HTML
<a href="javascript:void(0)"
    class="assign-shipping-template"
    templateShippingId="{$value}">
    {$assignText}
</a>
HTML;

    }

    //########################################

    protected function _toHtml()
    {
        $productsIdsStr = implode(',', $this->getProductsIds());

        $javascriptsMain = <<<HTML
<script type="text/javascript">

    $$('#amazonTemplateShippingTemplateGrid div.grid th').each(function(el) {
        el.style.padding = '5px 5px';
    });

    $$('#amazonTemplateShippingTemplateGrid div.grid td').each(function(el) {
        el.style.padding = '5px 5px';
    });

    ListingGridHandlerObj.templateShippingHandler.newTemplateUrl='{$this->getNewTemplateShippingUrl()}';

    {$this->getJsObjectName()}.reloadParams = {$this->getJsObjectName()}.reloadParams || {};
    {$this->getJsObjectName()}.reloadParams['products_ids'] = '{$productsIdsStr}';

</script>
HTML;

        // ---------------------------------------
        $data = array(
            'label' => Mage::helper('M2ePro')->__('Add New Shipping Template Policy'),
            'class' => 'new-shipping-template',
            'style' => 'float: right;'
        );

        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        // ---------------------------------------

        $buttonBlockHtml = ($this->canDisplayContainer()) ? $buttonBlock->toHtml(): '';

        return parent::_toHtml() . $buttonBlockHtml . $javascriptsMain;
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewTemplateShippingGrid', array(
            '_current' => true,
            'shipping_mode' => Ess_M2ePro_Model_Amazon_Account::SHIPPING_MODE_TEMPLATE,
            '_query' => array(
                'marketplace_id' => $this->getMarketplaceId()
            )
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function setNoTemplatesText()
    {
        $messageTxt = Mage::helper('M2ePro')->__('Shipping Template Policies are not found.');
        $linkTitle = Mage::helper('M2ePro')->__('Create New Shipping Template Policy.');

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    class="new-shipping-template">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    protected function getNewTemplateShippingUrl()
    {
        return $this->getUrl('*/adminhtml_common_amazon_template_shippingTemplate/new');
    }

    //########################################
}