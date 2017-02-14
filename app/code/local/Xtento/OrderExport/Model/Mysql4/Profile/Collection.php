<?php

/**
 * Product:       Xtento_OrderExport (1.2.5)
 * ID:            PTWLPPiznvrMtuGbT8nMuFIvIXeSBI7IJsisYvMWkIs=
 * Packaged:      2013-08-16T17:37:16+00:00
 * Last Modified: 2013-02-10T15:47:14+01:00
 * File:          app/code/local/Xtento/OrderExport/Model/Mysql4/Profile/Collection.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Model_Mysql4_Profile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('xtento_orderexport/profile');
    }
}