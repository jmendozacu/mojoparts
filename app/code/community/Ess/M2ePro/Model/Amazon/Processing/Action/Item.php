<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Amazon_Processing_Action_Item extends Ess_M2ePro_Model_Abstract
{
    //####################################

    /** @var Ess_M2ePro_Model_Amazon_Processing_Action $action */
    private $action = null;

    /** @var Ess_M2ePro_Model_Request_Pending_Single $requestPendingSingle */
    private $requestPendingSingle = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Amazon_Processing_Action_Item');
    }

    //####################################

    public function setAction(Ess_M2ePro_Model_Amazon_Processing_Action $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return Ess_M2ePro_Model_Amazon_Processing_Action
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function getAction()
    {
        if (!$this->getId()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Instance must be loaded first.');
        }

        if (!is_null($this->action)) {
            return $this->action;
        }

        return $this->action = Mage::helper('M2ePro')->getObject('Amazon_Processing_Action', $this->getActionId());
    }

    //------------------------------------

    public function setRequestPendingSingle(Ess_M2ePro_Model_Request_Pending_Single $requestPendingSingle)
    {
        $this->requestPendingSingle = $requestPendingSingle;
        return $this;
    }

    /**
     * @return Ess_M2ePro_Model_Request_Pending_Single
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function getRequestPendingSingle()
    {
        if (!$this->getId()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Instance must be loaded first.');
        }

        if (!$this->getRequestPendingSingleId()) {
            return null;
        }

        if (!is_null($this->requestPendingSingle)) {
            return $this->requestPendingSingle;
        }

        return $this->requestPendingSingle = Mage::helper('M2ePro')->getObject(
            'Request_Pending_Single', $this->getRequestPendingSingleId()
        );
    }

    //####################################

    public function getActionId()
    {
        return (int)$this->getData('action_id');
    }

    public function getRequestPendingSingleId()
    {
        return (int)$this->getData('request_pending_single_id');
    }

    public function getRelatedId()
    {
        return (int)$this->getData('related_id');
    }

    public function getInputData()
    {
        return $this->getSettings('input_data');
    }

    public function getOutputData()
    {
        return $this->getSettings('output_data');
    }

    public function getOutputMessages()
    {
        return $this->getSettings('output_messages');
    }

    public function getAttemptsCount()
    {
        return (int)$this->getData('attempts_count');
    }

    public function isCompleted()
    {
        return (bool)$this->getData('is_completed');
    }

    public function isSkipped()
    {
        return (bool)$this->getData('is_skipped');
    }

    //####################################
}