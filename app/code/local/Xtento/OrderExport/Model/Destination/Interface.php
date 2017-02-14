<?php

/**
 * Product:       Xtento_OrderExport (1.2.5)
 * ID:            PTWLPPiznvrMtuGbT8nMuFIvIXeSBI7IJsisYvMWkIs=
 * Packaged:      2013-08-16T17:37:16+00:00
 * Last Modified: 2012-11-23T19:26:35+01:00
 * File:          app/code/local/Xtento/OrderExport/Model/Destination/Interface.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

interface Xtento_OrderExport_Model_Destination_Interface
{
    public function testConnection();
    public function saveFiles($fileArray);
}