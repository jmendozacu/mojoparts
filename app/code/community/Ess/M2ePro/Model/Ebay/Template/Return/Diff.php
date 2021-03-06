<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Template_Return_Diff extends Ess_M2ePro_Model_Template_Diff_Abstract
{
    //########################################

    public function isDifferent()
    {
        return $this->isReturnDifferent();
    }

    //########################################

    public function isReturnDifferent()
    {
        $keys = array(
            'accepted',
            'option',
            'within',
            'holiday_mode',
            'shipping_cost',
            'restocking_fee',
            'description',
        );

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}