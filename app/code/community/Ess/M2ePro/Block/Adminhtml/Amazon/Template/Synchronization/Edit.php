<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Template_Synchronization_Edit
    extends Ess_M2ePro_Block_Adminhtml_Amazon_Template_Edit
{
    //########################################

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateSynchronizationEdit');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_amazon_template_synchronization';
        $this->_mode = 'edit';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $headerTextEdit = Mage::helper('M2ePro')->__("Edit Synchronization Policy");
        $headerTextAdd = Mage::helper('M2ePro')->__("Add Synchronization Policy");

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
            'onclick'   => 'AmazonTemplateSynchronizationHandlerObj.back_click(\'' . $url . '\')',
            'class'     => 'back'
        ));
        // ---------------------------------------

        if (Mage::helper('M2ePro/Data_Global')->getValue('temp_data')
            && Mage::helper('M2ePro/Data_Global')->getValue('temp_data')->getId()
        ) {
            // ---------------------------------------
            $this->_addButton('duplicate', array(
                'label'     => Mage::helper('M2ePro')->__('Duplicate'),
                'onclick'   => 'AmazonTemplateSynchronizationHandlerObj.duplicate_click'
                               .'(\'amazon-template-synchronization\')',
                'class'     => 'add M2ePro_duplicate_button'
            ));
            // ---------------------------------------

            // ---------------------------------------
            $this->_addButton('delete', array(
                'label'     => Mage::helper('M2ePro')->__('Delete'),
                'onclick'   => 'AmazonTemplateSynchronizationHandlerObj.delete_click()',
                'class'     => 'delete M2ePro_delete_button'
            ));
            // ---------------------------------------
        }

        // ---------------------------------------
        $this->_addButton('save', array(
            'label'     => Mage::helper('M2ePro')->__('Save'),
            'onclick'   => 'AmazonTemplateSynchronizationHandlerObj.save_click('
                . '\'\','
                . '\'' . $this->getSaveConfirmationText() . '\','
                . '\'' . Ess_M2ePro_Block_Adminhtml_Amazon_Template_Grid::TEMPLATE_SYNCHRONIZATION . '\''
            . ')',
            'class'     => 'save'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->_addButton('save_and_continue', array(
            'label'     => Mage::helper('M2ePro')->__('Save And Continue Edit'),
            'onclick'   => 'AmazonTemplateSynchronizationHandlerObj.save_and_edit_click('
                . '\'\','
                . '\'amazonTemplateSynchronizationEditTabs\','
                . '\'' . $this->getSaveConfirmationText() . '\','
                . '\'' . Ess_M2ePro_Block_Adminhtml_Amazon_Template_Grid::TEMPLATE_SYNCHRONIZATION . '\''
            . ')',
            'class'     => 'save'
        ));
        // ---------------------------------------
    }

    //########################################
}