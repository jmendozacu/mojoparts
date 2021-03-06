<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Listing_View_Settings_Grid_Motor_EditMode
    extends Mage_Adminhtml_Block_Widget
{
    protected $listingId;

    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewSettingsGridMotorEditMode');
        // ---------------------------------------

        $this->setTemplate('M2ePro/ebay/listing/view/settings/motor/edit_mode.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $data = array(
            'label'   => Mage::helper('M2ePro')->__('Save'),
            'class'   => 'save done',
            'onclick' => 'EditCompatibilityModeObj.saveListingMode();'
        );
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button')->setData($data);
        $this->setChild('save', $buttonBlock);
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################

    public function setListingId($id)
    {
        $this->listingId = $id;
        return $this;
    }

    public function getListing()
    {
        if (empty($this->listingId)) {
            throw new Exception('Listing ID is not set.');
        }

        return Mage::helper('M2ePro/Component_Ebay')->getCachedObject('Listing', $this->listingId);
    }

    //########################################
}