<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Adminhtml_MigrationToMagento2Controller
    extends Ess_M2ePro_Controller_Adminhtml_BaseController
{
    //########################################

    public function disableModuleAction()
    {
        return $this->_redirect('adminhtml/dashboard');
    }

    //########################################
}