<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Amazon_Processing_Action_Item
    extends Ess_M2ePro_Model_Mysql4_Abstract
{
    // ########################################

    public function _construct()
    {
        $this->_init('M2ePro/Amazon_Processing_Action_Item', 'id');
    }

    // ########################################

    public function incrementAttemptsCount(array $itemIds)
    {
        $this->_getWriteAdapter()->update(
            $this->getMainTable(),
            array(
                'attempts_count' => new Zend_Db_Expr('attempts_count + 1'),
            ),
            array('id IN (?)' => $itemIds)
        );
    }

    public function markAsInProgress(array $itemIds, Ess_M2ePro_Model_Request_Pending_Single $requestPendingSingle)
    {
        $this->_getWriteAdapter()->update(
            $this->getMainTable(),
            array(
                'request_pending_single_id' => $requestPendingSingle->getId(),
                'is_completed'              => 0,
            ),
            array('id IN (?)' => $itemIds)
        );
    }

    public function markAsSkippedProductAction(array $relatedIds)
    {
        $allowedActionTypes = array(
            Ess_M2ePro_Model_Amazon_Processing_Action::TYPE_PRODUCT_ADD,
            Ess_M2ePro_Model_Amazon_Processing_Action::TYPE_PRODUCT_UPDATE,
            Ess_M2ePro_Model_Amazon_Processing_Action::TYPE_PRODUCT_DELETE,
        );

        $allowedActionsSelect = $this->_getReadAdapter()->select()
            ->from($this->getTable('M2ePro/Amazon_Processing_Action'), 'id')
            ->where('type IN (?)', $allowedActionTypes);

        $this->_getWriteAdapter()->update(
            $this->getMainTable(),
            array('is_skipped' => 1),
            array(
                'action_id IN (?)'  => $allowedActionsSelect,
                'related_id IN (?)' => $relatedIds,
                'request_pending_single_id IS NULL',
                'is_completed = ?'  => 0,
            )
        );
    }

    public function getUniqueRequestPendingSingleIds()
    {
        $select = $this->_getReadAdapter()
            ->select()
            ->from($this->getMainTable(), new Zend_Db_Expr('DISTINCT `request_pending_single_id`'))
            ->where('is_completed = ?', 0)
            ->distinct(true);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function deleteByAction(Ess_M2ePro_Model_Amazon_Processing_Action $action)
    {
        return $this->_getWriteAdapter()->delete($this->getMainTable(), array('action_id = ?' => $action->getId()));
    }

    // ########################################
}