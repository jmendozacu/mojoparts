<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Template_ProductTaxCode_Edit
    extends Ess_M2ePro_Block_Adminhtml_Amazon_Template_Edit
{
    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateProductTaxCodeEdit');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_amazon_template_productTaxCode';
        $this->_mode = 'edit';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $helper = Mage::helper('M2ePro');
        $headerTextEdit = $helper->__("Edit Product Tax Code Policy");
        $headerTextAdd = $helper->__("Add Product Tax Code Policy" );

        if (Mage::helper('M2ePro/Data_Global')->getValue('temp_data')
            && Mage::helper('M2ePro/Data_Global')->getValue('temp_data')->getId()
        ) {
            $this->_headerText = $headerTextEdit;
            $this->_headerText .= ' "'.$this->escapeHtml(
                    Mage::helper('M2ePro/Data_Global')->getValue('temp_data')->getTitle()).'"';
        } else {
            $this->_headerText = $headerTextAdd;
        }
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $url = Mage::helper('M2ePro')->getBackUrl('list');
        $this->_addButton('back', array(
            'label'     => Mage::helper('M2ePro')->__('Back'),
            'onclick'   => 'AmazonTemplateProductTaxCodeHandlerObj.back_click(\'' . $url . '\')',
            'class'     => 'back'
        ));
        // ---------------------------------------

        if (Mage::helper('M2ePro/Data_Global')->getValue('temp_data')
            && Mage::helper('M2ePro/Data_Global')->getValue('temp_data')->getId()
        ) {
            // ---------------------------------------
            $this->_addButton('duplicate', array(
                'label'   => Mage::helper('M2ePro')->__('Duplicate'),
                'onclick' => 'AmazonTemplateProductTaxCodeHandlerObj.duplicate_click'
                    .'(\'amazon-template-TaxCode\')',
                'class'   => 'add M2ePro_duplicate_button'
            ));
            // ---------------------------------------

            // ---------------------------------------
            $this->_addButton('delete', array(
                'label'     => Mage::helper('M2ePro')->__('Delete'),
                'onclick'   => 'AmazonTemplateProductTaxCodeHandlerObj.delete_click()',
                'class'     => 'delete M2ePro_delete_button'
            ));
            // ---------------------------------------
        }

        // ---------------------------------------
        $this->_addButton('save', array(
            'label'     => Mage::helper('M2ePro')->__('Save'),
            'onclick'   => 'AmazonTemplateProductTaxCodeHandlerObj.save_click('
                . '\'\','
                . '\'' . $this->getSaveConfirmationText() . '\','
                . '\'' . Ess_M2ePro_Block_Adminhtml_Amazon_Template_Grid::TEMPLATE_PRODUCT_TAX_CODE . '\''
            . ')',
            'class'     => 'save'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->_addButton('save_and_continue', array(
            'label'     => Mage::helper('M2ePro')->__('Save And Continue Edit'),
            'onclick'   => 'AmazonTemplateProductTaxCodeHandlerObj.save_and_edit_click('
                . '\'\','
                . 'undefined,'
                . '\'' . $this->getSaveConfirmationText() . '\','
                . '\'' . Ess_M2ePro_Block_Adminhtml_Amazon_Template_Grid::TEMPLATE_PRODUCT_TAX_CODE . '\''
                . ')',
            'class'     => 'save'
        ));
        // ---------------------------------------
    }

    //########################################
}