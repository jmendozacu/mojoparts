<?php

/**
 * Product:       Xtento_OrderExport (1.2.5)
 * ID:            PTWLPPiznvrMtuGbT8nMuFIvIXeSBI7IJsisYvMWkIs=
 * Packaged:      2013-08-16T17:37:16+00:00
 * Last Modified: 2013-02-10T17:00:53+01:00
 * File:          app/code/local/Xtento/OrderExport/Block/Adminhtml/History/Grid/Renderer/Increment.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Block_Adminhtml_History_Grid_Renderer_Increment extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Options
{
    public function render(Varien_Object $row)
    {
        $incrementIdFields = array('increment_id', 'order_increment_id', 'invoice_increment_id', 'shipment_increment_id', 'creditmemo_increment_id');
        foreach ($incrementIdFields as $incrementIdField) {
            if ($row->getData($incrementIdField) !== NULL) {
                return $row->getData($incrementIdField);
            }
        }
        return '';
    }
}