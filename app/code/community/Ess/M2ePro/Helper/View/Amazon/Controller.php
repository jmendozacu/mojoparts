<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Helper_View_Amazon_Controller extends Mage_Core_Helper_Abstract
{
    //########################################

    public function addMessages(Ess_M2ePro_Controller_Adminhtml_BaseController $controller)
    {
        if (Mage::helper('M2ePro/View_Amazon')->isInstallationWizardFinished()) {

            if (Mage::helper('M2ePro/Component_Amazon')->isActive()) {
                $this->addAmazonMarketplacesNotUpdatedNotificationMessage($controller);
            }
        }
    }

    //########################################

    private function addAmazonMarketplacesNotUpdatedNotificationMessage(
        Ess_M2ePro_Controller_Adminhtml_BaseController $controller)
    {
        $outdatedMarketplaces = Mage::helper('M2ePro/Data_Cache_Permanent')->getValue('amazon_outdated_marketplaces');

        if ($outdatedMarketplaces === false) {

            $resource = Mage::getSingleton('core/resource');
            $readConn = $resource->getConnection('core_read');
            $dictionaryTable = Mage::helper('M2ePro/Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

            $rows = $readConn->select()->from($dictionaryTable,'marketplace_id')
                ->where('client_details_last_update_date IS NOT NULL')
                ->where('server_details_last_update_date IS NOT NULL')
                ->where('client_details_last_update_date < server_details_last_update_date')
                ->query();

            $ids = array();
            foreach ($rows as $row) {
                $ids[] = $row['marketplace_id'];
            }

            $marketplacesCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Marketplace')
                ->addFieldToFilter('status', Ess_M2ePro_Model_Marketplace::STATUS_ENABLE)
                ->addFieldToFilter('id',array('in' => $ids))
                ->setOrder('sorder','ASC');

            $outdatedMarketplaces = array();
            /* @var $marketplace Ess_M2ePro_Model_Marketplace */
            foreach ($marketplacesCollection as $marketplace) {
                $outdatedMarketplaces[] = $marketplace->getTitle();
            }

            Mage::helper('M2ePro/Data_Cache_Permanent')->setValue('amazon_outdated_marketplaces',
                $outdatedMarketplaces,
                array('amazon','marketplace'),
                60*60*24);
        }

        if (count($outdatedMarketplaces) <= 0) {
            return;
        }

// M2ePro_TRANSLATIONS
// %marketplace_title% data was changed on Amazon. You need to synchronize it the Extension works properly. Please, go to %menu_label% > <a href="%url%" target="_blank">Marketplaces</a> and click the Update All Now Button.

        $message = '%marketplace_title% data was changed on Amazon. ' .
            'You need to resynchronize it for the proper Extension work. '.
            'Please, go to %menu_path% > <a href="%url%" target="_blank">Marketplaces</a> ' .
            'and press an <b>Update All Now</b> button.';

        $controller->getSession()->addNotice(Mage::helper('M2ePro')->__(
            $message,
            implode(', ',$outdatedMarketplaces),
            Mage::helper('M2ePro/View_Amazon')->getPageNavigationPath('configuration'),
            $controller->getUrl(
                '*/adminhtml_amazon_marketplace',
                array('tab' => Ess_M2ePro_Helper_Component_Amazon::NICK)
            )
        ));
    }

    //########################################
}