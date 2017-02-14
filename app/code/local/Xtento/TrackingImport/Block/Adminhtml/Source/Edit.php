<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-05-22T18:25:09+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'xtento_trackingimport';
        $this->_controller = 'adminhtml_source';

        if (Mage::registry('source')->getId()) {
            $this->_updateButton('save', 'label', Mage::helper('xtento_trackingimport')->__('Save Source'));
            $this->_removeButton('delete');
            $this->_addButton('delete', array(
                'label' => Mage::helper('adminhtml')->__('Delete Source'),
                'class' => 'delete',
                'onclick' => 'deleteConfirm(\'' . Mage::helper('xtento_trackingimport')->__('Are you sure you want to do this? This source is in use by %d profiles.', (Mage::registry('source')) ? count(Mage::registry('source')->getProfileUsage()) : 0)
                    . '\', \'' . $this->getDeleteUrl() . '\')',
            ));
        }

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = <<<EOT
            function saveAndContinueEdit() {
                if (editForm && editForm.validator.validate()) {
                    var tabsIdValue = source_tabsJsTabs.activeTab.id;
                    var tabsBlockPrefix = 'source_tabs_';
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
EOT;
        if (Mage::registry('source') && Mage::registry('source')->getId()) {
            $this->_formScripts[] = <<<EOT
            varienGlobalEvents.attachEventHandler("formSubmit", function(){
                if (editForm && editForm.validator.validate()) {
                    Element.show('loading-mask');
                    setLoaderPosition();
                    $('loading_mask_loader').setStyle({width: 'auto'});
                    $('loading_mask_loader').innerHTML = $('loading_mask_loader').innerHTML + '<br/><br/>' + '{$this->__('The connection is being tested...')}';
                }
            });
EOT;
        }

        if (!Mage::registry('source') || !Mage::registry('source')->getId()) {
            $this->_removeButton('save');
            $this->_removeButton('delete');
        }
    }

    public function getHeaderText()
    {
        if (Mage::registry('source')->getId()) {
            return Mage::helper('xtento_trackingimport')->__('Edit Source \'%s\'', Mage::helper('xtcore/core')->escapeHtml(Mage::registry('source')->getName()));
        } else {
            return Mage::helper('xtento_trackingimport')->__('New Source');
        }
    }

    protected function _toHtml()
    {
        return $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_widget_menu')->setShowWarning(1)->toHtml() . parent::_toHtml();
    }
}