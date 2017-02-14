<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Model/System/Config/Source/Import/Profile.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_System_Config_Source_Import_Profile
{
    public function toOptionArray($all = false, $entity = false, $getLastImportedId = false)
    {
        $profileCollection = Mage::getModel('xtento_trackingimport/profile')->getCollection();
        if (!$all) {
            $profileCollection->addFieldToFilter('enabled', 1);
        }
        if ($entity) {
            $profileCollection->addFieldToFilter('entity', $entity);
        }
        $profileCollection->getSelect()->order('entity ASC');
        $returnArray = array();
        foreach ($profileCollection as $profile) {
            $returnArray[] = array(
                'profile' => $profile,
                'value' => $profile->getId(),
                'label' => $profile->getName(),
                'entity' => $profile->getEntity(),
            );
        }
        if (empty($returnArray)) {
            $returnArray[] = array(
                'profile' => new Varien_Object(),
                'value' => '',
                'label' => Mage::helper('xtento_trackingimport')->__('No profiles available. Add and enable import profiles for the %s entity first.', $entity),
                'entity' => '',
            );
        }
        return $returnArray;
    }
}
