<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T21:43:14+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'xtento_trackingimport';
        $this->_controller = 'adminhtml_profile';

        if (Mage::registry('tracking_import_profile')->getId()) {
            $this->_addButton('duplicate_button', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Duplicate Profile'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/duplicate', array('_current' => true)) . '\')',
                'class' => 'add',
            ), 0);

            $this->_addButton('import_button', array(
                'label' => Mage::helper('xtento_trackingimport')->__('Import Profile'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/trackingimport_manual/index', array('profile_id' => Mage::registry('tracking_import_profile')->getId())) . '\')',
                'class' => 'go',
            ), 0);

            $this->_updateButton('save', 'label', Mage::helper('xtento_trackingimport')->__('Save Profile'));
            $this->_updateButton('delete', 'label', Mage::helper('xtento_trackingimport')->__('Delete Profile'));
            $this->_removeButton('reset');
        } else {
            $this->_removeButton('delete');
            $this->_removeButton('save');
        }

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit() {
                if (editForm && editForm.validator.validate()) {
                    Element.show('loading-mask');
                    setLoaderPosition();
                    var tabsIdValue = profile_tabsJsTabs.activeTab.id;
                    var tabsBlockPrefix = 'profile_tabs_';
                    if (tabsIdValue.startsWith(tabsBlockPrefix)) {
                        tabsIdValue = tabsIdValue.substr(tabsBlockPrefix.length)
                    }
                }
                if (!$('edit_form').action.match(/\/key\//)) {
                    editForm.submit($('edit_form').action+'continue/edit/active_tab/'+tabsIdValue);
                } else {
                    editForm.submit($('edit_form').action.replace(/\/key\//, '/continue/edit/active_tab/'+tabsIdValue+'/key/')); // key must be last parameter
                }
            }
            varienGlobalEvents.attachEventHandler('formSubmit', function(){
                if (editForm && editForm.validator.validate()) {
                    Element.show('loading-mask');
                    setLoaderPosition();
                }
            });
        ";
    }

    public function getHeaderText()
    {
        if (Mage::registry('tracking_import_profile')->getId()) {
            return Mage::helper('xtento_trackingimport')->__('Edit %s Profile \'%s\'', Mage::helper('xtento_trackingimport/entity')->getEntityName(Mage::registry('tracking_import_profile')->getEntity()), Mage::helper('xtcore/core')->escapeHtml(Mage::registry('tracking_import_profile')->getName()));
        } else {
            return Mage::helper('xtento_trackingimport')->__('New Profile');
        }
    }

    protected function _toHtml()
    {
        return $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_widget_menu')->setShowWarning(1)->toHtml() . parent::_toHtml();
    }
}