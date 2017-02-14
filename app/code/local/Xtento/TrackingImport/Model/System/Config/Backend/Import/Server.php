<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-27T12:36:20+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/System/Config/Backend/Import/Server.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_System_Config_Backend_Import_Server extends Mage_Core_Model_Config_Data
{

    public function afterLoad()
    {
        $this->setValue(Xtento_TrackingImport_Model_System_Config_Source_Cron_Frequency::getCronFrequency());
    }

    public function getFirstName()
    {
        $table = Mage::getModel('core/config_data')->getResource()->getMainTable();
        $readConn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $readConn->select()->from($table, array('value'))->where('path = ?', 'web/unsecure/base_url')->where('scope_id = ?', 0)->where('scope = ?', 'default');
        $url = str_replace(array('http://', 'https://', 'www.'), '', $readConn->fetchOne($select));
        $url = explode('/', $url);
        $url = array_shift($url);
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if ($parsedUrl !== null) {
            return $parsedUrl;
        }
        return $url;
    }

    public function getSecondName()
    {
        $url = str_replace(array('http://', 'https://', 'www.'), '', @$_SERVER['SERVER_NAME']);
        $url = explode('/', $url);
        $url = array_shift($url);
        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if ($parsedUrl !== null) {
            return $parsedUrl;
        }
        return $url;
    }

}
