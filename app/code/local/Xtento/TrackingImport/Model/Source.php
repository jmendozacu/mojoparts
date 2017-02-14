<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-11-10T23:41:24+01:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Source.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Source extends Mage_Core_Model_Abstract
{
    /*
     * Source model containing information about "sources" where imported files will be loaded from
     */

    /*
     * Source Types
     */
    const TYPE_LOCAL = 'local';
    const TYPE_FTP = 'ftp';
    const TYPE_SFTP = 'sftp';
    const TYPE_HTTP = 'http';
    const TYPE_HTTPDOWNLOAD = 'httpdownload';
    const TYPE_WEBSERVICE = 'webservice';
    const TYPE_CUSTOM = 'custom';

    protected $_sourceClass = false;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('xtento_trackingimport/source');
    }

    /*
     * Return source types
     */
    public function getTypes()
    {
        $values = array();
        $values[self::TYPE_LOCAL] = Mage::helper('xtento_trackingimport')->__('Local Directory');
        $values[self::TYPE_FTP] = Mage::helper('xtento_trackingimport')->__('FTP Server');
        $values[self::TYPE_SFTP] = Mage::helper('xtento_trackingimport')->__('SFTP Server');
        $values[self::TYPE_HTTPDOWNLOAD] = Mage::helper('xtento_trackingimport')->__('HTTP URL Download');
        $values[self::TYPE_HTTP] = Mage::helper('xtento_trackingimport')->__('HTTP Server (Custom)');
        $values[self::TYPE_WEBSERVICE] = Mage::helper('xtento_trackingimport')->__('Webservice/API');
        $values[self::TYPE_CUSTOM] = Mage::helper('xtento_trackingimport')->__('Custom Class');
        return $values;
    }

    /*
     * Set last result message for this source
     */
    public function setLastResultMessage($message)
    {
        $this->setData('last_result_message', date('c', Mage::getModel('core/date')->timestamp(time())) . ": " . $message);
        return $this;
    }

    /*
     * Save files on source
     */
    public function loadFiles()
    {
        $this->_sourceClass = Mage::getModel('xtento_trackingimport/source_' . $this->getData('type'), array('source' => $this));
        if ($this->_sourceClass !== false) {
            return $this->_sourceClass->loadFiles();
        }
    }

    /*
     * Archive processed files
     */
    public function archiveFiles($filesToProcess)
    {
        if ($this->_sourceClass !== false) {
            return $this->_sourceClass->archiveFiles($filesToProcess);
        }
    }

    /*
     * Retrieve profiles which are using this source.
     */
    public function getProfileUsage()
    {
        $profileUsage = array();
        $profileCollection = Mage::getModel('xtento_trackingimport/profile')->getCollection();
        $profileCollection->addFieldToFilter('source_ids', array('neq' => ''));
        $profileCollection->getSelect()->order('entity ASC');
        foreach ($profileCollection as $profile) {
            $sourceIds = explode("&", $profile->getData('source_ids'));
            if (in_array($this->getId(), $sourceIds)) {
                $profileUsage[] = $profile;
            }
        }
        return $profileUsage;
    }

    /*
     * Overwrite ID when importing sources. Thanks to ST for the idea.
     */
    public function saveWithId()
    {
        // First check if the ID we've set exists as a model right now.
        $realId = $this->getId();
        $idExists = Mage::getModel($this->_resourceName)->setId(null)->load($realId)->getId();

        // Perform the regular saving routine as if it's a new model
        if (!$idExists) {
            $this->setId(null);
        }
        $this->save();

        // Update the new model we created with the original ID.
        if (!$idExists) {
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->update(
                $this->_getResource()->getMainTable(),
                array($this->_getResource()->getIdFieldName() => $realId),
                array("`{$this->_getResource()->getIdFieldName()}` = {$this->getId()}")
            );
            #$write->commit();
        }

        return $this;
    }

    /*
     * Fix bad user input for specific configuration values when requested by the module
     */
    public function getHostname() {
        $hostname = $this->getData('hostname');
        $hostname = str_replace(array('ftp://', 'http://'), '', $hostname);
        $hostname = trim($hostname);
        return $hostname;
    }

    public function getPort() {
        $port = $this->getData('port');
        $port = preg_replace('/[^0-9]/', '', $port);
        return $port;
    }

    public function getTimeout() {
        $timeout = $this->getData('timeout');
        $timeout = preg_replace('/[^0-9]/', '', $timeout);
        return $timeout;
    }
}