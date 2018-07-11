<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Amazon_Processing_Action_Item_Collection
    extends Ess_M2ePro_Model_Mysql4_Collection_Abstract
{
    // ########################################

    private $isActionDataJoined = false;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Amazon_Processing_Action_Item');
    }

    // ########################################

    public function setActionFilter(Ess_M2ePro_Model_Amazon_Processing_Action $action)
    {
        $this->addFieldToFilter('main_table.action_id', (int)$action->getId());
        return $this;
    }

    public function setRequestPendingSingleIdFilter($requestPendingSingleIds)
    {
        if (!is_array($requestPendingSingleIds)) {
            $requestPendingSingleIds = array($requestPendingSingleIds);
        }

        $this->addFieldToFilter('main_table.request_pending_single_id', array('in' => $requestPendingSingleIds));
        return $this;
    }

    /**
     * @param Ess_M2ePro_Model_Account[] $accounts
     * @return $this
     */
    public function setAccountsFilter(array $accounts)
    {
        $accountIds = array();
        foreach ($accounts as $account) {
            $accountIds[] = $account->getId();
        }

        $this->joinActionData();
        $this->addFieldToFilter('mapa.account_id', array('in' => $accountIds));

        return $this;
    }

    public function setNotProcessedFilter()
    {
        $this->addFieldToFilter('main_table.is_completed', 0);
        $this->addFieldToFilter('main_table.request_pending_single_id', array('null' => true));

        return $this;
    }

    public function setInProgressFilter()
    {
        $this->addFieldToFilter('main_table.is_completed', 0);
        $this->addFieldToFilter('main_table.request_pending_single_id', array('notnull' => true));

        return $this;
    }

    public function setActionTypeFilter($actionType)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.type', $actionType);

        return $this;
    }

    public function setCreatedBeforeFilter($minutes)
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $dateTime->modify('- '.(int)$minutes.' minutes');

        $this->addFieldToFilter('main_table.create_date', array('lt' => $dateTime->format('Y-m-d H:i:s')));

        return $this;
    }

    // ########################################

    private function joinActionData()
    {
        if ($this->isActionDataJoined) {
            return;
        }

        $this->getSelect()->joinLeft(
            array('mapa' => Mage::getResourceModel('M2ePro/Amazon_Processing_Action')->getMainTable()),
            'main_table.action_id=mapa.id', array('type')
        );

        $this->isActionDataJoined = true;
    }

    // ########################################
}