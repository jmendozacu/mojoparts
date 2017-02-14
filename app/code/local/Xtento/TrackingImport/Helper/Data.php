<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-09T15:18:08+01:00
 * File:          app/code/local/Xtento/TrackingImport/Helper/Data.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Helper_Data extends Mage_Core_Helper_Abstract
{
    static $_isModuleProperlyInstalled = null;
    const EDITION = 'CE';

    public function getDebugEnabled()
    {
        return Mage::getStoreConfigFlag('trackingimport/general/debug');
    }

    public function isDebugEnabled()
    {
        return Mage::getStoreConfigFlag('trackingimport/general/debug') && ($debug_email = Mage::getStoreConfig('trackingimport/general/debug_email')) && !empty($debug_email);
    }

    public function getDebugEmail()
    {
        return Mage::getStoreConfig('trackingimport/general/debug_email');
    }

    public function getModuleEnabled()
    {
        if (!Mage::getStoreConfigFlag('trackingimport/general/enabled')) {
            return 0;
        }
        $moduleEnabled = Mage::getModel('core/config_data')->load('trackingimport/general/' . str_rot13('frevny'), 'path')->getValue();
        if (empty($moduleEnabled) || !$moduleEnabled || (0x28 !== strlen(trim($moduleEnabled)))) {
            return 0;
        }
        if (!Mage::registry('moduleString')) {
            Mage::register('moduleString', 'false');
        }
        return true;
    }

    public function getMsg()
    {
        return Mage::helper('xtento_trackingimport')->__(str_rot13('Gur Genpxvat Vzcbeg Zbqhyr vf abg ranoyrq. Cyrnfr znxr fher lbh\'er hfvat n inyvq yvprafr xrl naq gung gur zbqhyr unf orra ranoyrq ng Flfgrz > KGRAGB Rkgrafvbaf > Genpxvat Vzcbeg Pbasvthengvba.'));
    }

    public function isModuleProperlyInstalled()
    {
        // Check if DB table(s) have been created.
        if (self::$_isModuleProperlyInstalled !== null) {
            return self::$_isModuleProperlyInstalled;
        } else {
            self::$_isModuleProperlyInstalled = (Mage::getSingleton('core/resource')->getConnection('core_read')->showTableStatus(Mage::getSingleton('core/resource')->getTableName('xtento_trackingimport_profile')) !== false);
            return self::$_isModuleProperlyInstalled;
        }
    }
}