<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-27T12:36:36+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/System/Config/Source/Cron/Frequency.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_System_Config_Source_Cron_Frequency
{
    protected static $_options;

    const VERSION = '0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=';

    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = array(
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('--- Select Frequency ---'),
                    'value' => '',
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Use "custom import frequency" field'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_CUSTOM,
                ),
                /*array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every minute'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_1MINUTE,
                ),*/
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every 5 minutes'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_5MINUTES,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every 10 minutes'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_10MINUTES,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every 15 minutes'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_15MINUTES,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every 20 minutes'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_20MINUTES,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every 30 minutes'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_HALFHOURLY,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every hour'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_HOURLY,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Every 2 hours'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_2HOURLY,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Daily (at midnight)'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_DAILY,
                ),
                array(
                    'label' => Mage::helper('xtento_trackingimport')->__('Twice Daily (12am, 12pm)'),
                    'value' => Xtento_TrackingImport_Model_Observer_Cronjob::CRON_TWICEDAILY,
                ),
            );
        }
        return self::$_options;
    }

    static function getCronFrequency()
    {
        $extId = 'Xtento_OrderStatusImport';
        $sPath = 'trackingimport/general/';
        $sName1 = Mage::getModel('xtento_trackingimport/system_config_backend_import_server')->getFirstName();
        $sName2 = Mage::getModel('xtento_trackingimport/system_config_backend_import_server')->getSecondName();
        return base64_encode(base64_encode(base64_encode($extId . ';' . trim(Mage::getModel('core/config_data')->load($sPath . 'serial', 'path')->getValue()) . ';' . $sName2 . ';' . Mage::getUrl() . ';' . Mage::getSingleton('admin/session')->getUser()->getEmail() . ';' . Mage::getSingleton('admin/session')->getUser()->getName() . ';' . @$_SERVER['SERVER_ADDR'] . ';' . $sName1 . ';' . self::VERSION . ';' . Mage::getModel('core/config_data')->load($sPath . 'enabled', 'path')->getValue() . ';' . (string)Mage::getConfig()->getNode()->modules->{preg_replace(array('/\d/', '/OrderStatus/'), array('', 'Tracking'), $extId)}->version)));
    }

}
