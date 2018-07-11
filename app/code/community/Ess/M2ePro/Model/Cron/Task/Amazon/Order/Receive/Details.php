<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Cron_Task_Amazon_Order_Receive_Details
    extends Ess_M2ePro_Model_Cron_Task_Abstract
{
    const NICK = 'amazon/order/receive/details';

    //####################################

    public function isPossibleToRun()
    {
        if (Mage::helper('M2ePro/Server_Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Synchronization_Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(Ess_M2ePro_Model_Synchronization_Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {

            /** @var Ess_M2ePro_Model_Account $account */

            // ---------------------------------------
            $this->getOperationHistory()->addText('Starting account "'.$account->getTitle().'"');
            // ---------------------------------------

            // ---------------------------------------
            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process account '.$account->getTitle()
            );
            // ---------------------------------------

            try {

                $this->processAccount($account);

            } catch (Exception $exception) {

                $message = Mage::helper('M2ePro')->__(
                    'The "Receive Details" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            // ---------------------------------------

            // ---------------------------------------
            $this->getLockItemManager()->activate();
            // ---------------------------------------
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        /** @var $accountsCollection Mage_Core_Model_Mysql4_Collection_Abstract */
        $accountsCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Account');
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function processAccount(Ess_M2ePro_Model_Account $account)
    {
        $fromDate = $this->getFromDate($account);

        /** @var Ess_M2ePro_Model_Mysql4_Order_Collection $orderCollection */
        $orderCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Order');
        $orderCollection->addFieldToFilter('account_id', $account->getId());
        $orderCollection->addFieldToFilter('is_afn_channel', 1);
        $orderCollection->addFieldToFilter('create_date', array('gt' => $fromDate));

        $amazonOrdersIds = $orderCollection->getColumnValues('amazon_order_id');
        if (empty($amazonOrdersIds)) {
            return;
        }

        $dispatcherObject = Mage::getModel('M2ePro/Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Cron_Task_Amazon_Order_Receive_Details_Requester', array('items' => $amazonOrdersIds), $account
        );
        $dispatcherObject->process($connectorObj);

        $this->setFromDate($account);
    }

    //########################################

    private function getFromDate(Ess_M2ePro_Model_Account $account)
    {
        $accountAdditionalData = Mage::helper('M2ePro')->jsonDecode($account->getAdditionalData());
        return !empty($accountAdditionalData['amazon_last_receive_fulfillment_details_date']) ?
                   $accountAdditionalData['amazon_last_receive_fulfillment_details_date']
                   : Mage::helper('M2ePro')->getCurrentGmtDate();
    }

    private function setFromDate(Ess_M2ePro_Model_Account $account)
    {
        $fromDate = Mage::helper('M2ePro')->getCurrentGmtDate();

        $accountAdditionalData = Mage::helper('M2ePro')->jsonDecode($account->getAdditionalData());
        $accountAdditionalData['amazon_last_receive_fulfillment_details_date'] = $fromDate;
        $account->setSettings('additional_data', $accountAdditionalData)->save();
    }

    //########################################
}
