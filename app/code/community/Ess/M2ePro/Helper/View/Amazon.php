<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Helper_View_Amazon extends Mage_Core_Helper_Abstract
{
    // M2ePro_TRANSLATIONS
    // Sell On Amazon

    const NICK  = 'amazon';

    const WIZARD_INSTALLATION_NICK = 'installationAmazon';
    const MENU_ROOT_NODE_NICK = 'm2epro_amazon';

    //########################################

    public function getTitle()
    {
        return Mage::helper('M2ePro')->__('Sell On Amazon');
    }

    //########################################

    public function getMenuRootNodeLabel()
    {
        return $this->getTitle();
    }

    //########################################

    public function getPageNavigationPath($pathNick, $tabName = NULL, $additionalEnd = NULL)
    {
        $resultPath = array();

        $rootMenuNode = Mage::getConfig()->getNode('adminhtml/menu/m2epro_amazon');
        $menuLabel = Mage::helper('M2ePro/View')->getMenuPath($rootMenuNode, $pathNick, $this->getMenuRootNodeLabel());

        if (!$menuLabel) {
            return '';
        }

        $resultPath['menu'] = $menuLabel;

        if ($tabName) {
            $resultPath['tab'] = Mage::helper('M2ePro')->__($tabName) . ' ' . Mage::helper('M2ePro')->__('Tab');
        }

        if ($additionalEnd) {
            $resultPath['additional'] = Mage::helper('M2ePro')->__($additionalEnd);
        }

        return join($resultPath, ' > ');
    }

    //########################################

    public function getAutocompleteMaxItems()
    {
        $temp = (int)Mage::helper('M2ePro/Module')->getConfig()
            ->getGroupValue('/view/amazon/autocomplete/','max_records_quantity');
        return $temp <= 0 ? 100 : $temp;
    }

    //########################################

    public function getWizardInstallationNick()
    {
        return self::WIZARD_INSTALLATION_NICK;
    }

    public function isInstallationWizardFinished()
    {
        return Mage::helper('M2ePro/Module_Wizard')->isFinished(
            $this->getWizardInstallationNick()
        );
    }

    //########################################

    public function prepareMenu(array $menuArray)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed(self::MENU_ROOT_NODE_NICK)) {
            return $menuArray;
        }

        if (count(Mage::helper('M2ePro/View_Amazon_Component')->getActiveComponents()) <= 0) {
            unset($menuArray[self::MENU_ROOT_NODE_NICK]);
            return $menuArray;
        }

        $tempTitle = $this->getMenuRootNodeLabel();
        !empty($tempTitle) && $menuArray[self::MENU_ROOT_NODE_NICK]['label'] = $tempTitle;

        /* @var $wizardHelper Ess_M2ePro_Helper_Module_Wizard */
        $wizardHelper = Mage::helper('M2ePro/Module_Wizard');

        $activeBlocker = $wizardHelper->getActiveBlockerWizard(Ess_M2ePro_Helper_View_Amazon::NICK);
        $isModuleDisabled = Mage::helper('M2ePro/Module')->isDisabled();

        if (!$activeBlocker && !$isModuleDisabled) {
            return $menuArray;
        }

        unset($menuArray[self::MENU_ROOT_NODE_NICK]['children']);
        unset($menuArray[self::MENU_ROOT_NODE_NICK]['click']);

        if (!$isModuleDisabled) {
            $menuArray[self::MENU_ROOT_NODE_NICK]['url'] = Mage::helper('adminhtml')->getUrl(
                'M2ePro/adminhtml_wizard_'.$wizardHelper->getNick($activeBlocker).'/index'
            );
        }

        $menuArray[self::MENU_ROOT_NODE_NICK]['last'] = true;

        return $menuArray;
    }

    //########################################

    public function is3rdPartyShouldBeShown()
    {
        $sessionCache = Mage::helper('M2ePro/Data_Cache_Session');

        if (!is_null($sessionCache->getValue('is_3rd_party_should_be_shown'))) {
            return $sessionCache->getValue('is_3rd_party_should_be_shown');
        }

        $accountCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Account');
        $accountCollection->addFieldToFilter(
            'other_listings_synchronization', Ess_M2ePro_Model_Amazon_Account::OTHER_LISTINGS_SYNCHRONIZATION_YES
        );

        if ((bool)$accountCollection->getSize()) {
            $result = true;
        } else {
            $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Other');

            $logCollection = Mage::getModel('M2ePro/Listing_Other_Log')->getCollection();
            $logCollection->addFieldToFilter(
                'component_mode', Ess_M2ePro_Helper_Component_Amazon::NICK
            );

            $result = $collection->getSize() || $logCollection->getSize();
        }

        $sessionCache->setValue('is_3rd_party_should_be_shown', $result);

        return $result;
    }

    //########################################
}