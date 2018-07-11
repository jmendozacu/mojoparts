<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Processing_Action_Item extends Ess_M2ePro_Model_Abstract
{
    //####################################

    /** @var Ess_M2ePro_Model_Ebay_Processing_Action $action */
    private $action = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Ebay_Processing_Action_Item');
    }

    //####################################

    public function setAction(Ess_M2ePro_Model_Ebay_Processing_Action $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Processing_Action
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

        return $this->action = Mage::helper('M2ePro')->getObject('Ebay_Processing_Action', $this->getActionId());
    }

    //####################################

    public function getActionId()
    {
        return (int)$this->getData('action_id');
    }

    public function getRelatedId()
    {
        return (int)$this->getData('related_id');
    }

    public function getInputData()
    {
        return $this->getSettings('input_data');
    }

    public function isSkipped()
    {
        return (bool)$this->getData('is_skipped');
    }

    //####################################
}