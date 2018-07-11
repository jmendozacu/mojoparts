<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Cron_Strategy_Parallel extends Ess_M2ePro_Model_Cron_Strategy_Abstract
{
    const GENERAL_LOCK_ITEM_PREFIX = 'cron_strategy_parallel_';

    const MAX_PARALLEL_EXECUTED_CRONS_COUNT = 10;

    const MAX_FIRST_SLOW_TASK_EXECUTION_TIME_FOR_CONTINUE = 60; // 1 minute

    /**
     * @var Ess_M2ePro_Model_Lock_Item_Manager
     */
    private $generalLockItemManager = NULL;

    /**
     * @var Ess_M2ePro_Model_Lock_Item_Manager
     */
    private $fastTasksLockItemManager = NULL;

    //########################################

    protected function getNick()
    {
        return Ess_M2ePro_Helper_Module_Cron::STRATEGY_PARALLEL;
    }

    //########################################

    protected function processTasks()
    {
        $result = true;

        /** @var Ess_M2ePro_Model_Lock_Transactional_Manager $transactionalManager */
        $transactionalManager = Mage::getModel('M2ePro/Lock_Transactional_Manager', array(
            'nick' => self::INITIALIZATION_TRANSACTIONAL_LOCK_NICK
        ));

        $transactionalManager->lock();

        if ($this->isSerialStrategyInProgress()) {
            $transactionalManager->unlock();
            return $result;
        }

        $this->getGeneralLockItemManager()->create();
        $this->makeLockItemShutdownFunction($this->getGeneralLockItemManager());

        if (!$this->getFastTasksLockItemManager()->isExist() ||
            ($this->getFastTasksLockItemManager()->isInactiveMoreThanSeconds(
                Ess_M2ePro_Model_Lock_Item_Manager::DEFAULT_MAX_INACTIVE_TIME
            ) && $this->getFastTasksLockItemManager()->remove())
        ) {

            $this->getFastTasksLockItemManager()->create($this->getGeneralLockItemManager()->getNick());
            $this->makeLockItemShutdownFunction($this->getFastTasksLockItemManager());

            $transactionalManager->unlock();

            $this->keepAliveStart($this->getFastTasksLockItemManager());
            $this->startListenProgressEvents($this->getFastTasksLockItemManager());

            $result = !$this->processFastTasks() ? false : $result;

            $this->keepAliveStop();
            $this->stopListenProgressEvents();

            $this->getFastTasksLockItemManager()->remove();
        }

        $transactionalManager->unlock();

        $result = !$this->processSlowTasks() ? false : $result;

        $this->getGeneralLockItemManager()->remove();

        return $result;
    }

    // ---------------------------------------

    private function processFastTasks()
    {
        $result = true;

        foreach ($this->getAllowedFastTasks() as $taskNick) {

            try {

                $taskObject = $this->getTaskObject($taskNick);
                $taskObject->setLockItemManager($this->getFastTasksLockItemManager());

                $tempResult = $taskObject->process();

                if (!is_null($tempResult) && !$tempResult) {
                    $result = false;
                }

                $this->getFastTasksLockItemManager()->activate();

            } catch (Exception $exception) {

                $result = false;

                $this->getOperationHistory()->addContentData('exceptions', array(
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString(),
                ));

                Mage::helper('M2ePro/Module_Exception')->process($exception);
            }
        }

        return $result;
    }

    private function processSlowTasks()
    {
        $helper = Mage::helper('M2ePro/Module_Cron');

        $result = true;

        $isFirstTask = true;

        for ($i = 0; $i < count($this->getAllowedSlowTasks()); $i++) {

            /** @var Ess_M2ePro_Model_Lock_Transactional_Manager $transactionalManager */
            $transactionalManager = Mage::getModel('M2ePro/Lock_Transactional_Manager', array(
                'nick' => self::GENERAL_LOCK_ITEM_PREFIX.'slow_task_switch'
            ));

            $transactionalManager->lock();

            $taskNick = $this->getNextSlowTask();
            $helper->setLastExecutedSlowTask($taskNick);

            $transactionalManager->unlock();

            $taskLockItemManager = Mage::getModel('M2ePro/Lock_Item_Manager', array(
                'nick' => 'cron_task_'.str_replace("/", "_", $taskNick)
            ));

            if ($taskLockItemManager->isExist()) {

                if (!$taskLockItemManager->isInactiveMoreThanSeconds(
                        Ess_M2ePro_Model_Lock_Item_Manager::DEFAULT_MAX_INACTIVE_TIME
                )) {
                    continue;
                }

                $taskLockItemManager->remove();
            }

            $taskLockItemManager->create($this->getGeneralLockItemManager()->getNick());
            $this->makeLockItemShutdownFunction($taskLockItemManager);

            $taskObject = $this->getTaskObject($taskNick);
            $taskObject->setLockItemManager($taskLockItemManager);

            if (!$taskObject->isPossibleToRun()) {
                continue;
            }

            $this->keepAliveStart($taskLockItemManager);
            $this->startListenProgressEvents($taskLockItemManager);

            $taskStartTime = time();

            try {
                $result = $taskObject->process();
            } catch (Exception $exception) {

                $result = false;

                $this->getOperationHistory()->addContentData('exceptions', array(
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString(),
                ));

                Mage::helper('M2ePro/Module_Exception')->process($exception);
            }

            $taskProcessTime = time() - $taskStartTime;

            $this->keepAliveStop();
            $this->stopListenProgressEvents();

            $taskLockItemManager->remove();

            if (!$isFirstTask || $taskProcessTime > self::MAX_FIRST_SLOW_TASK_EXECUTION_TIME_FOR_CONTINUE) {
                break;
            }

            $isFirstTask = false;
        }

        return $result;
    }

    //########################################

    private function getAllowedFastTasks()
    {
        return array_values(array_intersect($this->getAllowedTasks(), array(
            Ess_M2ePro_Model_Cron_Task_System_ArchiveOldOrders::NICK,
            Ess_M2ePro_Model_Cron_Task_System_ClearOldLogs::NICK,
            Ess_M2ePro_Model_Cron_Task_System_ConnectorCommandPending_ProcessPartial::NICK,
            Ess_M2ePro_Model_Cron_Task_System_ConnectorCommandPending_ProcessSingle::NICK,
            Ess_M2ePro_Model_Cron_Task_System_IssuesResolver_RemoveMissedProcessingLocks::NICK,
            Ess_M2ePro_Model_Cron_Task_System_Processing_ProcessResult::NICK,
            Ess_M2ePro_Model_Cron_Task_System_RequestPending_ProcessPartial::NICK,
            Ess_M2ePro_Model_Cron_Task_System_RequestPending_ProcessSingle::NICK,
            Ess_M2ePro_Model_Cron_Task_System_Servicing_Synchronize::NICK,
            Ess_M2ePro_Model_Cron_Task_Magento_Product_DetectDirectlyAdded::NICK,
            Ess_M2ePro_Model_Cron_Task_Magento_Product_DetectDirectlyDeleted::NICK,
            Ess_M2ePro_Model_Cron_Task_Listing_Product_InspectDirectChanges::NICK,
            Ess_M2ePro_Model_Cron_Task_Listing_Product_ProcessReviseTotal::NICK,
            Ess_M2ePro_Model_Cron_Task_Listing_Product_AutoActions_ProcessMagentoProductWebsitesUpdates::NICK,
            Ess_M2ePro_Model_Cron_Task_Listing_Product_StopQueue_Process::NICK,
            Ess_M2ePro_Model_Cron_Task_Listing_Product_StopQueue_RemoveOld::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_UpdateAccountsPreferences::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Template_RemoveUnused::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Channel_SynchronizeChanges::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Feedbacks_DownloadNew::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Feedbacks_SendResponse::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Listing_Other_ResolveSku::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Listing_Other_Channel_SynchronizeData::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Listing_Product_ProcessInstructions::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Listing_Product_ProcessScheduledActions::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Listing_Product_RemovePotentialDuplicates::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Order_CreateFailed::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Order_Update::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Order_Cancel::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_Order_ReserveCancel::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_PickupStore_ScheduleForUpdate::NICK,
            Ess_M2ePro_Model_Cron_Task_Ebay_PickupStore_UpdateOnChannel::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Other_ResolveTitle::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Other_Channel_SynchronizeData::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Other_Channel_SynchronizeData_Blocked::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_Channel_SynchronizeData::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_Channel_SynchronizeData_Blocked::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_Channel_SynchronizeData_Defected::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_RunVariationParentProcessors::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_ProcessInstructions::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_ProcessActions::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Listing_Product_ProcessActionsResults::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Receive::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_CreateFailed::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Update::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Update_SellerOrderId::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Refund::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Cancel::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_ReserveCancel::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Action_ProcessUpdate::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Action_ProcessRefund::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Action_ProcessCancel::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Order_Action_ProcessResults::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Repricing_InspectProducts::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Repricing_UpdateSettings::NICK
        )));
    }

    private function getAllowedSlowTasks()
    {
        return array_values(array_intersect($this->getAllowedTasks(), array(
            Ess_M2ePro_Model_Cron_Task_Ebay_Listing_Product_ProcessActions::NICK,
            Ess_M2ePro_Model_Cron_Task_Amazon_Repricing_Synchronize::NICK
        )));
    }

    // ---------------------------------------

    private function getNextSlowTask()
    {
        $helper = Mage::helper('M2ePro/Module_Cron');
        $lastExecutedTask = $helper->getLastExecutedSlowTask();

        $allowedSlowTasks = $this->getAllowedSlowTasks();
        $lastExecutedTaskIndex = array_search($lastExecutedTask, $allowedSlowTasks);

        if (empty($lastExecutedTask)
            || $lastExecutedTaskIndex === false
            || end($allowedSlowTasks) == $lastExecutedTask) {

            return reset($allowedSlowTasks);
        }

        return $allowedSlowTasks[$lastExecutedTaskIndex + 1];
    }

    /**
     * @return Ess_M2ePro_Model_Lock_Item_Manager
     * @throws Ess_M2ePro_Model_Exception
     */
    private function getGeneralLockItemManager()
    {
        if (!is_null($this->generalLockItemManager)) {
            return $this->generalLockItemManager;
        }

        for ($index = 1; $index <= self::MAX_PARALLEL_EXECUTED_CRONS_COUNT; $index++) {
            $lockItemManager = Mage::getModel(
                'M2ePro/Lock_Item_Manager', array('nick' => self::GENERAL_LOCK_ITEM_PREFIX.$index)
            );

            if (!$lockItemManager->isExist()) {
                return $this->generalLockItemManager = $lockItemManager;
            }

            if ($lockItemManager->isInactiveMoreThanSeconds(
                    Ess_M2ePro_Model_Lock_Item_Manager::DEFAULT_MAX_INACTIVE_TIME
            )) {
                $lockItemManager->remove();
                return $this->generalLockItemManager = $lockItemManager;
            }
        }

        throw new Ess_M2ePro_Model_Exception('Too many parallel lock items.');
    }

    /**
     * @return Ess_M2ePro_Model_Lock_Item_Manager
     */
    private function getFastTasksLockItemManager()
    {
        if (!is_null($this->fastTasksLockItemManager)) {
            return $this->fastTasksLockItemManager;
        }

        $this->fastTasksLockItemManager = Mage::getModel(
            'M2ePro/Lock_Item_Manager', array('nick' => 'cron_strategy_parallel_fast_tasks')
        );

        return $this->fastTasksLockItemManager;
    }

    /**
     * @return bool
     */
    private function isSerialStrategyInProgress()
    {
        $serialLockItemManager = Mage::getModel(
            'M2ePro/Lock_Item_Manager', array('nick' => Ess_M2ePro_Model_Cron_Strategy_Serial::LOCK_ITEM_NICK)
        );
        if (!$serialLockItemManager->isExist()) {
            return false;
        }

        if ($serialLockItemManager->isInactiveMoreThanSeconds(
                Ess_M2ePro_Model_Lock_Item_Manager::DEFAULT_MAX_INACTIVE_TIME
        )) {
            $serialLockItemManager->remove();
            return false;
        }

        return true;
    }

    //########################################
}