<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Ebay_Motor_Filter_Collection
    extends Ess_M2ePro_Model_Mysql4_Collection_Abstract
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Ebay_Motor_Filter');
    }

    //########################################
}