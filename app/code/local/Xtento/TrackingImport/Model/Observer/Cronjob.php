<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-03T16:33:42+01:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Observer/Cronjob.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Observer_Cronjob
{
    const CRON_CUSTOM = 'custom';
    const CRON_1MINUTE = '* * * * *';
    const CRON_5MINUTES = '*/5 * * * *';
    const CRON_10MINUTES = '*/10 * * * *';
    const CRON_15MINUTES = '*/15 * * * *';
    const CRON_20MINUTES = '*/20 * * * *';
    const CRON_HALFHOURLY = '*/30 * * * *';
    const CRON_HOURLY = '0 * * * *';
    const CRON_2HOURLY = '0 */2 * * *';
    const CRON_DAILY = '0 0 * * *';
    const CRON_TWICEDAILY = '0 0,12 * * *';

    public function import($schedule)
    {
        try {
            if (!Mage::helper('xtento_trackingimport')->getModuleEnabled() || !Mage::helper('xtento_trackingimport')->isModuleProperlyInstalled()) {
                return;
            }
            if (!$schedule) {
                return;
            }
            $jobCode = $schedule->getJobCode();
            preg_match('/xtento_trackingimport_profile_(\d+)/', $jobCode, $jobMatch);
            if (!isset($jobMatch[1])) {
                Mage::throwException(Mage::helper('xtento_trackingimport')->__('No profile ID found in job_code.'));
            }
            $profileId = $jobMatch[1];
            $profile = Mage::getModel('xtento_trackingimport/profile')->load($profileId);
            if (!$profile->getId()) {
                Mage::throwException(Mage::helper('xtento_trackingimport')->__('Profile ID %d does not seem to exist anymore.', $profileId));
            }
            if (!$profile->getEnabled()) {
                return; // Profile not enabled
            }
            if (!$profile->getCronjobEnabled()) {
                return; // Cronjob not enabled
            }
            $importModel = Mage::getModel('xtento_trackingimport/import', array('profile' => $profile));
            $importModel->cronImport();
        } catch (Exception $e) {
            Mage::log('Cronjob exception for job_code ' . $jobCode . ': ' . $e->getMessage(), null, 'xtento_trackingimport_cron.log', true);
            return;
        }
    }
}