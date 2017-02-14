<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Helper/Import.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Helper_Import extends Mage_Core_Helper_Abstract
{
    public function getImportBkpDir()
    {
        return Mage::getBaseDir('var') . DS . "import_bkp" . DS;
    }

    public function getProcessorName($processor)
    {
        $processors = Mage::getSingleton('xtento_trackingimport/import')->getProcessors();
        $processorName = $processors[$processor];
        return $processorName;
    }
}