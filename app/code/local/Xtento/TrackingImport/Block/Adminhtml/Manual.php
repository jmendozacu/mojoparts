<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-15T14:02:16+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Manual.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Manual extends Mage_Adminhtml_Block_Template
{
    protected $_importResult = false;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('xtento/trackingimport/manual_import.phtml');
    }

    public function getProfileSelectorHtml()
    {
        $html = '<select class="select" name="profile_id" id="profile_id" style="width: 320px;">';
        $html .= '<option value="">' . Mage::helper('xtento_trackingimport')->__('--- Select Profile---') . '</option>';
        $enabledProfiles = Mage::getModel('xtento_trackingimport/system_config_source_import_profile')->toOptionArray();
        $profilesByGroup = array();
        foreach ($enabledProfiles as $profile) {
            $profilesByGroup[$profile['entity']][] = $profile;
        }
        foreach ($profilesByGroup as $entity => $profiles) {
            $html .= '<optgroup label="' . Mage::helper('xtento_trackingimport')->__('%s Import', Mage::helper('xtento_trackingimport/entity')->getEntityName($entity)) . '">';
            foreach ($profiles as $profile) {
                $html .= '<option value="' . $profile['value'] . '" entity="' . $entity . '">' . $profile['label'] . ' (' . Mage::helper('xtento_trackingimport')->__('ID: %d', $profile['value']) . ')</option>';
            }
            $html .= '</optgroup>';
        }
        $html .= '</select>';
        return $html;
    }

    public function getImportResult()
    {
        if (!$this->_importResult) {
            $this->_importResult = Mage::getSingleton('adminhtml/session')->getData('xtento_trackingimport_debug_log');
            Mage::getSingleton('adminhtml/session')->setData('xtento_trackingimport_debug_log', false);
        }
        if (empty($this->_importResult)) {
            return false;
        }
        return $this->_importResult;
    }

    protected function _toHtml()
    {
        $messagesBlock = <<<EOT
<div id="messages_import">
    <ul class="messages">
        <li class="warning-msg" id="warning-msg" style="display:none">
            <ul>
                <li>
                    <span id="warning-msg-text"></span>
                </li>
            </ul>
        </li>
        <li class="success-msg" id="success-msg" style="display:none">
            <ul>
                <li>
                    <span id="success-msg-text"></span>
                </li>
            </ul>
        </li>
    </ul>
</div>
EOT;
        return $messagesBlock . $this->getLayout()->createBlock('xtento_trackingimport/adminhtml_widget_menu')->toHtml() . parent::_toHtml();
    }
}