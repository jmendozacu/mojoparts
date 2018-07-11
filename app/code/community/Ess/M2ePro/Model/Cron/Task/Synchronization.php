<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

final class Ess_M2ePro_Model_Cron_Task_Synchronization extends Ess_M2ePro_Model_Cron_Task_Abstract
{
    const NICK = 'synchronization';

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return Ess_M2ePro_Model_Synchronization_Dispatcher::MAX_MEMORY_LIMIT;
    }

    //########################################

    protected function performActions()
    {
        /** @var $dispatcher Ess_M2ePro_Model_Synchronization_Dispatcher */
        $dispatcher = Mage::getModel('M2ePro/Synchronization_Dispatcher');

        $dispatcher->setParentLockItem($this->getLockItem());
        $dispatcher->setParentOperationHistory($this->getOperationHistory());

        $dispatcher->setAllowedComponents(array(
            Ess_M2ePro_Helper_Component_Ebay::NICK,
            Ess_M2ePro_Helper_Component_Amazon::NICK,
            Ess_M2ePro_Helper_Component_Buy::NICK
        ));

        $dispatcher->setAllowedTasksTypes(array(
            Ess_M2ePro_Model_Synchronization_Task_Global_Abstract::PROCESSING,
            Ess_M2ePro_Model_Synchronization_Task_Global_Abstract::MAGENTO_PRODUCTS,
            Ess_M2ePro_Model_Synchronization_Task_Global_Abstract::STOP_QUEUE,
            Ess_M2ePro_Model_Synchronization_Task_Component_Abstract::GENERAL,
            Ess_M2ePro_Model_Synchronization_Task_Component_Abstract::LISTINGS_PRODUCTS,
            Ess_M2ePro_Model_Synchronization_Task_Component_Abstract::TEMPLATES,
            Ess_M2ePro_Model_Synchronization_Task_Component_Abstract::ORDERS,
            Ess_M2ePro_Model_Synchronization_Task_Component_Abstract::OTHER_LISTINGS
        ));

        $dispatcher->setInitiator($this->getInitiator());
        $dispatcher->setParams(array());

        return $dispatcher->process();
    }

    //########################################
}