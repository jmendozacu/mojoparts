<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2014-07-22T19:08:43+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Mapping/Action.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Mapping_Action extends Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Mapping_Abstract
{
    protected $MAPPING_ID = 'action';
    protected $MAPPING_MODEL = 'xtento_trackingimport/processor_mapping_action';
    protected $FIELD_LABEL = 'Action';
    protected $VALUE_FIELD_LABEL = 'Value';
    protected $HAS_DEFAULT_VALUE_COLUMN = true;
    protected $HAS_VALUE_COLUMN = false;
    protected $DEFAULT_VALUE_FIELD_LABEL = 'Value';
    protected $ADD_FIELD_LABEL = 'Add new action';
    protected $ADD_ALL_FIELD_LABEL = 'Add all actions';
    protected $SELECT_LABEL = '--- Select action ---';
}
