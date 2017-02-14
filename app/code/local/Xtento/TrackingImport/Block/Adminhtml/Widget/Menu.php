<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-05-16T21:39:37+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Widget/Menu.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Widget_Menu extends Mage_Core_Block_Abstract
{
    protected $_menuBar;

    protected $_menu = array(
        'trackingimport_manual' => array(
            'name' => 'Manual Import',
            'action_name' => '',
            'last_link' => false,
            'is_link' => true
        ),
        'trackingimport_log' => array(
            'name' => 'Execution Log',
            'action_name' => '',
            'last_link' => false,
            'is_link' => true
        ),
        /*'trackingimport_history' => array(
            'name' => 'Import History',
            'action_name' => '',
            'last_link' => false,
            'is_link' => true
        ),*/
        'trackingimport_configuration' => array(
            'name' => 'Configuration',
            'last_link' => false,
            'is_link' => false,
        ),
        'trackingimport_profile' => array(
            'name' => 'Import Profiles',
            'action_name' => '',
            'last_link' => false,
            'is_link' => true
        ),
        'trackingimport_source' => array(
            'name' => 'Import Sources',
            'action_name' => '',
            'last_link' => false,
            'is_link' => true
        ),
        'trackingimport_tools' => array(
            'name' => 'Tools',
            'action_name' => '',
            'last_link' => false,
            'is_link' => true
        ),
    );

    public function getMenu()
    {
        return $this->_menu;
    }

    protected function _toHtml()
    {
        $title = Mage::helper('xtento_trackingimport')->__('Tracking Import Navigation');
        $this->_menuBar = <<<EOT
        <style>
        .icon-head { padding-left: 0px; }
        </style>
        <script>
            function xtHasChanges() {
                var elements = $$(".changed");
                for (var i in elements) {
                    if (typeof elements[i].id !== "undefined" && elements[i].id !== "") {
                        return true;
                    }
                }
                return false;
            }
        </script>
        <div style="padding:8px; margin-bottom: 10px; border: 1px solid #CDDDDD; background: #E7EFEF; font-size:12px;">
            <i>{$title}</i>&nbsp;-&nbsp;
EOT;
        foreach ($this->getMenu() as $controllerName => $entryConfig) {
            if ($entryConfig['is_link']) {
                if (!Mage::getSingleton('admin/session')->isAllowed('sales/trackingimport/' . str_replace('trackingimport_', '', $controllerName))) {
                    // No rights to see
                    continue;
                }
                $this->addMenuLink($entryConfig['name'], $controllerName, $entryConfig['action_name'], $entryConfig['last_link']);
            } else {
                $this->_menuBar .= '<i>' . $entryConfig['name'] . '</i>';
                if (!$entryConfig['last_link']) {
                    $this->_menuBar .= '&nbsp;|&nbsp;';
                }
            }
        }
        $this->_menuBar .= '<a id="page-help-link" href="http://support.xtento.com/wiki/Magento_Extensions:Tracking_Number_Import_Module" target="_blank" style="color: #EA7601; text-decoration: underline; line-height: 16px;">' . Mage::helper('xtento_trackingimport')->__('Help') . '</a>';
        $this->_menuBar .= '<div style="float:right;"><a href="http://www.xtento.com/" target="_blank" style="text-decoration:none;font-weight:bold;color:#57585B;"><img src="//www.xtento.com/media/images/extension_logo.png" alt="XTENTO" height="20" style="vertical-align:middle;"/> XTENTO Magento Extensions</a></div></div>';

        return $this->_menuBar;
    }

    private function addMenuLink($name, $controllerName, $actionName = '', $lastLink = false)
    {
        $isActive = '';
        if ($this->getRequest()->getControllerName() == $controllerName) {
            if ($actionName == '' || $this->getRequest()->getActionName() == $actionName) {
                $isActive = 'font-weight: bold;';
            }
        }
        $showWarning = '';
        if ($this->getShowWarning()) {
            $showWarning = "if (xtHasChanges()) { if (!confirm('" . Mage::helper('xtento_trackingimport')->__('You have unsaved changes. Click OK to leave without saving your changes.') . "')) { return false; } }";
        }
        $this->_menuBar .= '<a href="' . Mage::helper('adminhtml')->getUrl('*/' . $controllerName . '/' . $actionName) . '" onclick="' . $showWarning . '" style="' . $isActive . '">' . Mage::helper('xtento_trackingimport')->__($name) . '</a>';
        if (!$lastLink) {
            $this->_menuBar .= '&nbsp;|&nbsp;';
        }
    }

    public function isEnabled()
    {
        return Xtento_TrackingImport_Model_System_Config_Source_Order_Status::isEnabled();
    }
}