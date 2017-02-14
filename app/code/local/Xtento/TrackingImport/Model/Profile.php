<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-06-23T21:46:55+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Profile.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Profile extends Mage_Rule_Model_Rule
{
    /*
     * Profile model containing information about import profiles
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('xtento_trackingimport/profile');
    }

    public function getConditionsInstance()
    {
        Mage::register('tracking_import_profile', $this, true);
        return Mage::getModel('xtento_trackingimport/import_condition_combine');
    }


    public function getSources()
    {
        $logEntry = Mage::registry('tracking_import_log');
        $sourceIds = array_filter(explode("&", $this->getData('source_ids')));
        $sources = array();
        foreach ($sourceIds as $sourceId) {
            if (!is_numeric($sourceId)) {
                continue;
            }
            $source = Mage::getModel('xtento_trackingimport/source')->load($sourceId);
            if ($source->getId()) {
                $sources[] = $source;
            } else {
                #if ($logEntry) {
                #    $logEntry->setResult(Xtento_TrackingImport_Model_Log::RESULT_WARNING);
                #    $logEntry->addResultMessage(Mage::helper('xtento_trackingimport')->__('Source ID "%s" could not be found.', $sourceId));
                #}
            }
        }
        // Return sources
        return $sources;
    }

    protected function _beforeSave()
    {
        // Only call the "rule" model parents _beforeSave function if the profile is modified in the backend, as otherwise the "conditions" ("import filters") could be lost
        if (Mage::app()->getRequest()->getControllerName() == 'trackingimport_profile') {
            parent::_beforeSave();
        } else {
            if (!$this->getId()) {
                $this->isObjectNew(true);
            }
        }
    }
}