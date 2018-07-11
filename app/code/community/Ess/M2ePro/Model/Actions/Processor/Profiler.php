<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Actions_Processor_Profiler extends Ess_M2ePro_Model_Abstract
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Actions_Processor_Profiler');
    }

    //########################################

    public function getComponent()
    {
        return $this->getData('component');
    }

    public function getStartDate()
    {
        return $this->getData('start_date');
    }

    public function getEndDate()
    {
        return $this->getData('end_date');
    }

    public function getProfilerData()
    {
        return $this->getSettings('profiler_data');
    }

    //########################################
}