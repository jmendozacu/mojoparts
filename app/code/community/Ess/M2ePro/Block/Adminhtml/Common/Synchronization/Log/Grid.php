<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Block_Adminhtml_Common_Synchronization_Log_Grid
    extends Ess_M2ePro_Block_Adminhtml_Synchronization_Log_Grid
{
    //########################################

    protected function getActionTitles()
    {
        return Mage::getModel('M2ePro/Synchronization_Log')->getActionsTitles();
    }

    //########################################
}