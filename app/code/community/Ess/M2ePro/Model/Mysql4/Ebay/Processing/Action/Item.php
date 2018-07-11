<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Ebay_Processing_Action_Item
    extends Ess_M2ePro_Model_Mysql4_Abstract
{
    // ########################################

    public function _construct()
    {
        $this->_init('M2ePro/Ebay_Processing_Action_Item', 'id');
    }

    // ########################################

    public function markAsSkipped(array $relatedIds)
    {
        $this->_getWriteAdapter()->update(
            $this->getMainTable(),
            array('is_skipped' => 1),
            array('related_id IN (?)' => $relatedIds)
        );
    }

    // ########################################

    public function deleteByAction(Ess_M2ePro_Model_Ebay_Processing_Action $action)
    {
        return $this->_getWriteAdapter()->delete($this->getMainTable(), array('action_id = ?' => $action->getId()));
    }

    // ########################################
}