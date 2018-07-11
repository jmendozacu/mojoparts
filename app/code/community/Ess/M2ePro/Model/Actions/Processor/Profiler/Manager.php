<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Actions_Processor_Profiler_Manager
{
    /** @var Ess_M2ePro_Model_Actions_Processor_Profiler $profiler */
    private $profiler = NULL;

    //########################################

    public function __construct($arguments)
    {
        if (!isset($arguments['component'])) {
            throw new Ess_M2ePro_Model_Exception_Logic('Component is missed.');
        }

        $component = $arguments['component'];

        if (!in_array($component, Mage::helper('M2ePro/Component')->getComponents())) {
            throw new Ess_M2ePro_Model_Exception_Logic(sprintf('Invalid component "%s"', $component));
        }

        $this->profiler = Mage::getModel('M2ePro/Actions_Processor_Profiler');
        $this->profiler->setData('component', $component);
    }

    //########################################

    public function start()
    {
        $this->profiler->setData('start_date', Mage::helper('M2ePro')->getCurrentGmtDate());
        $this->profiler->save();
    }

    public function end()
    {
        $this->profiler->setData('end_date', Mage::helper('M2ePro')->getCurrentGmtDate());
        $this->profiler->save();
    }

    //########################################

    public function addData($key, $value)
    {
        $profilerData = $this->profiler->getProfilerData();
        $profilerData[$key] = $value;
        $this->profiler->setSettings('profiler_data', $profilerData);
        $this->profiler->save();
    }

    //########################################
}