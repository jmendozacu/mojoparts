<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Log/Grid/Renderer/Source.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Log_Grid_Renderer_Source extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    static $sources = array();

    public function render(Varien_Object $row)
    {
        $sourceIds = $row->getSourceIds();
        $sourceText = "";
        if (empty($sourceIds)) {
            return Mage::helper('xtento_trackingimport')->__('No source selected.');
        }
        foreach (explode("&", $sourceIds) as $sourceId) {
            if (!empty($sourceId) && is_numeric($sourceId)) {
                if (!isset(self::$sources[$sourceId])) {
                    $source = Mage::getModel('xtento_trackingimport/source')->load($sourceId);
                    self::$sources[$sourceId] = $source;
                } else {
                    $source = self::$sources[$sourceId];
                }
                if ($source->getId()) {
                    $sourceText .= $source->getName() . " (".Mage::getSingleton('xtento_trackingimport/system_config_source_source_type')->getName($source->getType()).")<br>";
                }
            }
        }
        return $sourceText;
    }
}