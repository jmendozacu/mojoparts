<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Ebay_Processing_Action_Item_Collection
    extends Ess_M2ePro_Model_Mysql4_Collection_Abstract
{
    // ########################################

    private $isActionDataJoined = false;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Ebay_Processing_Action_Item');
    }

    // ########################################

    public function setActionFilter(Ess_M2ePro_Model_Ebay_Processing_Action $action)
    {
        $this->addFieldToFilter('main_table.action_id', (int)$action->getId());
        return $this;
    }

    public function setAccountFilter(Ess_M2ePro_Model_Account $account)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.account_id', (int)$account->getId());

        return $this;
    }

    public function setMarketplaceFilter(Ess_M2ePro_Model_Marketplace $marketplace)
    {
        $this->joinActionData();
        $this->addFieldToFilter('mapa.marketplace_id', (int)$marketplace->getId());

        return $this;
    }

    public function setActionTypeFilter($actionType)
    {
        $this->joinActionData();

        if (is_array($actionType)) {
            $this->addFieldToFilter('mapa.type', array('in' => $actionType));
        } else {
            $this->addFieldToFilter('mapa.type', $actionType);
        }

        return $this;
    }

    // ########################################

    private function joinActionData()
    {
        if ($this->isActionDataJoined) {
            return;
        }

        $this->getSelect()->joinLeft(
            array('mapa' => Mage::getResourceModel('M2ePro/Ebay_Processing_Action')->getMainTable()),
            'main_table.action_id=mapa.id', array('type')
        );

        $this->isActionDataJoined = true;
    }

    // ########################################
}