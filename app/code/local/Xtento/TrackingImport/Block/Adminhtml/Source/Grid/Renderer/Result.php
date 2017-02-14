<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Grid/Renderer/Result.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Grid_Renderer_Result extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if ($row->getLastResult() === NULL) {
            return '<span class="grid-severity-major"><span>' . Mage::helper('xtento_trackingimport')->__('No Result') . '</span></span>';
        } else if ($row->getLastResult() == 0) {
            return '<span class="grid-severity-critical"><span>' . Mage::helper('xtento_trackingimport')->__('Failed') . '</span></span>';
        } else if ($row->getLastResult() == 1) {
            return '<span class="grid-severity-notice"><span>' . Mage::helper('xtento_trackingimport')->__('Success') . '</span></span>';
        }
    }
}