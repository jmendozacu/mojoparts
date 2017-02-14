<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-04-26T17:02:48+02:00
 * File:          app/code/local/Xtento/TrackingImport/Model/Observer/Cron/Config.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Model_Observer_Cron_Config extends Mage_Core_Model_Abstract
{
    /**
     * Add cronjobs to the Magento config dynamically before schedule generates/cron dispatches and ONLY for cron.php/cron.sh calls
     */
    public function addCronjobsToConfig($observer)
    {
        // Add import cronjobs to config
        $this->injectCronjobsIntoConfig(true);
        // Add cronjobs of other modules
        Mage::dispatchEvent('xtento_cronjob_dispatch_before', array());
        // Call original observer
        Mage::getModel('cron/observer')->dispatch($observer);
    }

    /**
     * Add cronjobs to the Magento config dynamically - just required if the AOE Scheduler extension is used, so we can see them in the backend
     */
    public function addCronjobsToConfigAoeScheduler($observer)
    {
        if (Mage::app()->getRequest() && in_array(Mage::app()->getRequest()->getControllerName(), array('scheduler', 'job', 'timeline', 'cron'))) {
            $this->injectCronjobsIntoConfig();
        }
    }

    /**
     * When the xtento_cronjob_dispatch_before event gets dispatched, add our cronjobs as well before the cron is dispatched
     */
    public function xtentoCronjobDispatchBefore($observer)
    {
        // Add import cronjobs to config
        $this->injectCronjobsIntoConfig(true);
    }

    public function injectCronjobsIntoConfig($cronExecution = false)
    {
        try {
            if (Mage::registry('xt_trackingimport_cron_injected') !== null) {
                return $this;
            }
            Mage::register('xt_trackingimport_cron_injected', true);
            if ($cronExecution) {
                // Dispatch "cron has been executed" event
                if (Mage::registry('xtento_cronjob_execution_called') === null) {
                    Mage::dispatchEvent('xtento_cronjob_execution', array());
                    Mage::register('xtento_cronjob_execution_called', true);
                }
            }
            if (!Mage::helper('xtento_trackingimport')->getModuleEnabled() || !Mage::helper('xtento_trackingimport')->isModuleProperlyInstalled()) {
                return $this;
            }
            $newJobs = new SimpleXMLElement('<?xml version="1.0"?><config><crontab><jobs></jobs></crontab></config>');
            $jobs = $newJobs->crontab->jobs;
            // Load profiles and add cronjobs
            $profileCollection = Mage::getModel('xtento_trackingimport/profile')->getCollection();
            $profileCollection->addFieldToFilter('enabled', 1); // Profile enabled
            $profileCollection->addFieldToFilter('cronjob_enabled', 1); // Cronjob enabled
            foreach ($profileCollection as $profile) {
                if ($profile->getCronjobFrequency() == Xtento_TrackingImport_Model_Observer_Cronjob::CRON_CUSTOM || ($profile->getCronjobFrequency() == '' && $profile->getCronjobCustomFrequency() !== '')) {
                    // Custom cron expression
                    $cronFrequencies = $profile->getCronjobCustomFrequency();
                    if (empty($cronFrequencies)) {
                        continue;
                    }
                    $cronFrequencies = array_unique(explode(";", $cronFrequencies));
                    $cronCounter = 0;
                    foreach ($cronFrequencies as $cronFrequency) {
                        if (empty($cronFrequency)) {
                            continue;
                        }
                        $cronCounter++;
                        $job = $jobs->addChild('xtento_trackingimport_profile_' . $profile->getId() . '_cron_' . $cronCounter);
                        $job->addChild('schedule')->addChild('cron_expr', $cronFrequency);
                        $job->addChild('run')->addChild('model', 'xtento_trackingimport/observer_cronjob::import');
                    }
                } else {
                    // No custom cron expression
                    $cronFrequency = $profile->getCronjobFrequency();
                    if (empty($cronFrequency)) {
                        continue;
                    }
                    $job = $jobs->addChild('xtento_trackingimport_profile_' . $profile->getId());
                    $job->addChild('schedule')->addChild('cron_expr', $cronFrequency);
                    $job->addChild('run')->addChild('model', 'xtento_trackingimport/observer_cronjob::import');
                }
            }
            // Done adding cronjobs, extend original cron config
            $origJobs = new Varien_Simplexml_Config('<?xml version="1.0"?><config><crontab>' . Mage::getConfig()->getNode('crontab/jobs')->asXML() . '</crontab></config>');
            $newCronConfig = new Varien_Simplexml_Config($newJobs->asXML());
            $newCronConfig->extend($origJobs);
        } catch (Exception $e) {
            Mage::log('Exception for _addCronjobsToConfig(): ' . $e->getMessage(), null, 'xtento_trackingimport_cron.log', true);
            return $this;
        }
        // Reset original cronjobs and inject our custom cron config with our cronjobs on first position
        $node = Mage::getConfig()->getNode('crontab');
        unset($node->jobs);
        Mage::getConfig()->extend($newCronConfig, true);
        // Double check cronjob configuration
        $jobNode = Mage::getConfig()->getNode('crontab/jobs');
        if (!$jobNode || !$jobNode->children() || (method_exists($jobNode->children(), 'count') && $jobNode->children()->count() === 0)) {
            // There was a problem extending the config, restore original config.
            Mage::getConfig()->extend($origJobs, true);
        }
    }
}
