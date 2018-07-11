<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Adminhtml_Development_Tools_M2ePro_GeneralController
    extends Ess_M2ePro_Controller_Adminhtml_Development_CommandController
{
    //########################################

    /**
     * @title "Clear Cache"
     * @description "Clear extension cache"
     * @confirm "Are you sure?"
     */
    public function clearExtensionCacheAction()
    {
        Mage::helper('M2ePro/Module')->clearCache();
        $this->_getSession()->addSuccess('Extension cache was successfully cleared.');
        $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageToolsTabUrl());
    }

    /**
     * @title "Clear Config Cache"
     * @description "Clear config cache"
     * @confirm "Are you sure?"
     */
    public function clearConfigCacheAction()
    {
        Mage::helper('M2ePro/Module')->clearConfigCache();
        $this->_getSession()->addSuccess('Config cache was successfully cleared.');
        $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageToolsTabUrl());
    }

    /**
     * @title "Clear Variables Dir"
     * @description "Clear Variables Dir"
     * @confirm "Are you sure?"
     * @new_line
     */
    public function clearVariablesDirAction()
    {
        Mage::getModel('M2ePro/VariablesDir')->removeBaseForce();
        $this->_getSession()->addSuccess('Variables dir was successfully cleared.');
        $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageToolsTabUrl());
    }

    //########################################

    /**
     * @title "Repair Broken Tables"
     * @description "Command for show and repair broken horizontal tables"
     */
    public function checkTablesAction()
    {
        $tableNames = $this->getRequest()->getParam('table', array());

        if (!empty($tableNames)) {
            Mage::helper('M2ePro/Module_Database_Repair')->repairBrokenTables($tableNames);
            $this->_redirectUrl(Mage::helper('adminhtml')->getUrl('*/*/checkTables/'));
        }

        $brokenTables = Mage::helper('M2ePro/Module_Database_Repair')->getBrokenTablesInfo();

        if ($brokenTables['total_count'] <= 0) {
            return $this->getResponse()->setBody($this->getEmptyResultsHtml('No Broken Tables'));
        }

        $currentUrl = Mage::helper('adminhtml')->getUrl('*/*/*');
        $infoUrl = Mage::helper('adminhtml')->getUrl('*/*/showBrokenTableIds');

        $html = <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Broken Tables
            <span style="color: #808080; font-size: 15px;">({$brokenTables['total_count']} entries)</span>
        </h2>
        <br/>
        <form method="GET" action="{$currentUrl}">
            <input type="hidden" name="action" value="repair" />
            <table class="grid" cellpadding="0" cellspacing="0">
HTML;
        if (count($brokenTables['parent'])) {

            $html .= <<<HTML
<tr bgcolor="#E7E7E7">
    <td colspan="4">
        <h4 style="margin: 0 0 0 10px">Parent Tables</h4>
    </td>
</tr>
<tr>
    <th style="width: 400">Table</th>
    <th style="width: 50">Count</th>
    <th style="width: 50"></th>
    <th style="width: 50"></th>
</tr>
HTML;
            foreach ($brokenTables['parent'] as $parentTable => $brokenItemsCount) {

                $html .= <<<HTML
<tr>
    <td>
        <a href="{$infoUrl}?table[]={$parentTable}"
           target="_blank" title="Show Ids" style="text-decoration: none;">{$parentTable}</a>
    </td>
    <td>
        {$brokenItemsCount}
    </td>
    <td>
        <input type='button' value="Repair" onclick ="location.href='{$currentUrl}?table[]={$parentTable}'" />
    </td>
    <td>
        <input type="checkbox" name="table[]" value="{$parentTable}" />
    </td>
HTML;
            }
        }

        if (count($brokenTables['children'])) {

            $html .= <<<HTML
<tr height="100%">
    <td><div style="height: 10px;"></div></td>
</tr>
<tr bgcolor="#E7E7E7">
    <td colspan="4">
        <h4 style="margin: 0 0 0 10px">Children Tables</h4>
    </td>
</tr>
<tr>
    <th style="width: 400">Table</th>
    <th style="width: 50">Count</th>
    <th style="width: 50"></th>
    <th style="width: 50"></th>
</tr>
HTML;
            foreach ($brokenTables['children'] as $childrenTable => $brokenItemsCount) {

                $html .= <<<HTML
<tr>
    <td>
        <a href="{$infoUrl}?table[]={$childrenTable}"
           target="_blank" title="Show Ids" style="text-decoration: none;">{$childrenTable}</a>
    </td>
    <td>
        {$brokenItemsCount}
    </td>
    <td>
        <input type='button' value="Repair" onclick ="location.href='{$currentUrl}?table[]={$childrenTable}'" />
    </td>
    <td>
        <input type="checkbox" name="table[]" value="{$childrenTable}" />
    </td>
HTML;
            }
        }

        $html .= <<<HTML
                <tr>
                    <td colspan="4"><hr/></td>
                </tr>
                <tr>
                    <td colspan="4" align="right">
                        <input type="submit" value="Repair Checked">
                    <td>
                </tr>
            </table>
        </form>
    </body>
</html>
HTML;

        return $this->getResponse()->setBody($html);
    }

    /**
     * @title "Show Broken Table IDs"
     * @hidden
     */
    public function showBrokenTableIdsAction()
    {
        $tableNames = $this->getRequest()->getParam('table', array());

        if (empty($tableNames)) {
            $this->_redirectUrl(Mage::helper('adminhtml')->getUrl('*/*/checkTables/'));
        }

        $tableName = array_pop($tableNames);

        $info = Mage::helper('M2ePro/Module_Database_Repair')->getBrokenRecordsInfo($tableName);

        return $this->getResponse()->setBody('<pre>' .
             "<span>Broken Records '{$tableName}'<span><br>" .
             print_r($info, true));
    }

    // ---------------------------------------

    /**
     * @title "Repair Removed Stores"
     * @description "Command for show and repair removed magento stores"
     */
    public function showRemovedMagentoStoresAction()
    {
        $collection = Mage::getModel('core/store')->getCollection();
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('store_id');

        $existsStoreIds = array(Mage_Core_Model_App::ADMIN_STORE_ID);
        foreach ($collection as $item) {
            $existsStoreIds[] = (int)$item->getStoreId();
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $storeRelatedColumns = Mage::helper('M2ePro/Module_Database_Structure')->getStoreRelatedColumns();

        $usedStoresIds = array();

        foreach ($storeRelatedColumns as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {

                $tempResult = $connection->select()
                    ->distinct()
                    ->from(
                        Mage::helper('M2ePro/Module_Database_Structure')
                            ->getTableNameWithPrefix($tableName),
                        array($columnInfo['name'])
                    )
                    ->where("{$columnInfo['name']} IS NOT NULL")
                    ->query()
                    ->fetchAll(Zend_Db::FETCH_COLUMN);

                if ($columnInfo['type'] == 'int') {
                    $usedStoresIds = array_merge($usedStoresIds, $tempResult);
                    continue;
                }

                // json
                foreach ($tempResult as $itemRow) {
                    preg_match_all('/"(store|related_store)_id":"?([\d]+)"?/', $itemRow, $matches);
                    !empty($matches[2]) && $usedStoresIds = array_merge($usedStoresIds,$matches[2]);
                }
            }
        }

        $usedStoresIds = array_values(array_unique(array_map('intval',$usedStoresIds)));
        $removedStoreIds = array_diff($usedStoresIds, $existsStoreIds);

        if (count($removedStoreIds) <= 0) {
            return $this->getResponse()->setBody($this->getEmptyResultsHtml('No Removed Magento Stores'));
        }

        $html = $this->getStyleHtml();

        $removedStoreIds = implode(', ', $removedStoreIds);
        $repairStoresAction = Mage::helper('adminhtml')->getUrl('*/*/repairRemovedMagentoStore');

        $html .= <<<HTML
<h2 style="margin: 20px 0 0 10px">Removed Magento Stores
    <span style="color: #808080; font-size: 15px;">(%count% entries)</span>
</h2>

<span style="display:inline-block; margin: 20px 20px 20px 10px;">
    Removed Store IDs: {$removedStoreIds}
</span>

<form action="{$repairStoresAction}" method="get">
    <input name="replace_from" value="" type="text" placeholder="replace from id" required/>
    <input name="replace_to" value="" type="text" placeholder="replace to id" required />
    <button type="submit">Repair</button>
</form>
HTML;

        return $this->getResponse()->setBody(str_replace('%count%', count($removedStoreIds), $html));
    }

    /**
     * @title "Repair Removed Store"
     * @hidden
     */
    public function repairRemovedMagentoStoreAction()
    {
        $replaceIdFrom = $this->getRequest()->getParam('replace_from');
        $replaceIdTo = $this->getRequest()->getParam('replace_to');

        if (!$replaceIdFrom || !$replaceIdTo) {
            $this->_getSession()->addError('Required params are not presented.');
            $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageToolsTabUrl());
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $storeRelatedColumns = Mage::helper('M2ePro/Module_Database_Structure')->getStoreRelatedColumns();

        foreach ($storeRelatedColumns as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {

                if ($columnInfo['type'] == 'int') {

                    $connection->update(
                        Mage::helper('M2ePro/Module_Database_Structure')->getTableNameWithPrefix($tableName),
                        array($columnInfo['name'] => $replaceIdTo),
                        "`{$columnInfo['name']}` = {$replaceIdFrom}"
                    );

                    continue;
                }

                // json
                $bind = array($columnInfo['name'] => new Zend_Db_Expr(
                    "REPLACE(
                        REPLACE(
                            `{$columnInfo['name']}`,
                            'store_id\":{$replaceIdFrom}',
                            'store_id\":{$replaceIdTo}'
                        ),
                        'store_id\":\"{$replaceIdFrom}\"',
                        'store_id\":\"{$replaceIdTo}\"'
                    )"
                ));

                $connection->update(
                    Mage::helper('M2ePro/Module_Database_Structure')->getTableNameWithPrefix($tableName),
                    $bind,
                    "`{$columnInfo['name']}` LIKE '%store_id\":\"{$replaceIdFrom}\"%' OR
                     `{$columnInfo['name']}` LIKE '%store_id\":{$replaceIdFrom}%'"
                );
            }
        }

        $this->_redirect('*/*/showRemovedMagentoStores');
    }

    // ---------------------------------------

    /**
     * @title "Repair Listing Product Structure"
     * @description "Listing -> Listing Product -> Option -> Variation"
     */
    public function repairListingProductStructureAction()
    {
        ini_set('display_errors', 1);

        // -- Listing_Product_Variation_Option to un-existed Listing_Product_Variation
        $deletedOptions = 0;

        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::getModel('M2ePro/Listing_Product_Variation_Option')->getCollection();
        $collection->getSelect()->joinLeft(
            array('mlpv' => $collection->getResource()->getTable('M2ePro/Listing_Product_Variation')),
            'main_table.listing_product_variation_id=mlpv.id',
            array()
        );
        $collection->addFieldToFilter('mlpv.id', array('null' => true));
        $collection->getSelect()->group('main_table.id');

        /* @var $item Ess_M2ePro_Model_Listing_Product_Variation_Option */
        while ($item = $collection->fetchItem()) {
            $item->getResource()->delete($item);
            $deletedOptions++;
        }
        // --

        // -- Listing_Product_Variation to un-existed Listing_Product OR with no Listing_Product_Variation_Option
        $deletedVariations = 0;

        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::getModel('M2ePro/Listing_Product_Variation')->getCollection();
        $collection->getSelect()->joinLeft(
            array('mlp' => $collection->getResource()->getTable('M2ePro/Listing_Product')),
            'main_table.listing_product_id=mlp.id',
            array()
        );
        $collection->getSelect()->joinLeft(
            array('mlpvo' => $collection->getResource()->getTable('M2ePro/Listing_Product_Variation_Option')),
            'main_table.id=mlpvo.listing_product_variation_id',
            array()
        );

        $collection->getSelect()->where('mlp.id IS NULL OR mlpvo.id IS NULL');
        $collection->getSelect()->group('main_table.id');

        /* @var $item Ess_M2ePro_Model_Listing_Product_Variation */
        while ($item = $collection->fetchItem()) {
            $item->getResource()->delete($item);
            $deletedVariations++;
        }
        // --

        // -- Listing_Product to un-existed Listing
        $deletedProducts = 0;

        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::getModel('M2ePro/Listing_Product')->getCollection();
        $collection->getSelect()->joinLeft(
            array('ml' => $collection->getResource()->getTable('M2ePro/Listing')),
            'main_table.listing_id=ml.id',
            array()
        );
        $collection->addFieldToFilter('ml.id', array('null' => true));
        $collection->getSelect()->group('main_table.id');

        /* @var $item Ess_M2ePro_Model_Listing_Product */
        while ($item = $collection->fetchItem()) {
            $item->getResource()->delete($item);
            $deletedProducts++;
        }
        // --

        // -- Listing to un-existed Account
        $deletedListings = 0;

        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::getModel('M2ePro/Listing')->getCollection();
        $collection->getSelect()->joinLeft(
            array('ma' => $collection->getResource()->getTable('M2ePro/Account')),
            'main_table.account_id=ma.id',
            array()
        );
        $collection->addFieldToFilter('ma.id', array('null' => true));
        $collection->getSelect()->group('main_table.id');

        /* @var $item Ess_M2ePro_Model_Listing */
        while ($item = $collection->fetchItem()) {
            $item->getResource()->delete($item);
            $deletedListings++;
        }
        // --

        printf('Deleted options: %d <br/>', $deletedOptions);
        printf('Deleted variations: %d <br/>', $deletedVariations);
        printf('Deleted products: %d <br/>', $deletedProducts);
        printf('Deleted listings: %d <br/>', $deletedListings);

        printf('<br/>Please run repair broken tables feature.<br/>');
    }

    /**
     * @title "Repair OrderItem => Order Structure"
     * @description "OrderItem->getOrder() => remove OrderItem if is need"
     */
    public function repairOrderItemOrderStructureAction()
    {
        ini_set('display_errors', 1);

        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::getModel('M2ePro/Order_Item')->getCollection();
        $collection->getSelect()->joinLeft(
            array('mo' => $collection->getResource()->getTable('M2ePro/Order')),
            'main_table.order_id=mo.id',
            array()
        );
        $collection->addFieldToFilter('mo.id', array('null' => true));

        $deletedOrderItems = 0;

        /* @var $item Ess_M2ePro_Model_Order_Item */
        while ($item = $collection->fetchItem()) {
            $item->deleteInstance() && $deletedOrderItems++;
        }

        printf('Deleted OrderItems records: %d', $deletedOrderItems);
    }

    /**
     * @title "Repair eBay ItemID N\A"
     * @description "Repair Item is Listed but have N\A Ebay Item ID"
     */
    public function repairEbayItemIdStructureAction()
    {
        ini_set('display_errors', 1);

        $items = 0;

        $collection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Listing_Product');
        $collection->getSelect()->joinLeft(
            array('ei' => Mage::getResourceModel('M2ePro/Ebay_Item')->getMainTable()),
            '`second_table`.`ebay_item_id` = `ei`.`id`',
            array('item_id' => 'item_id')
        );
        $collection->addFieldToFilter('status',
                                      array('nin' => array(Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED,
                                                           Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN)));

        $collection->addFieldToFilter('item_id', array('null' => true));

        /* @var $item Ess_M2ePro_Model_Order_Item */
        while ($item = $collection->fetchItem()) {

            $item->setData('status', Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED)->save();
            $items++;
        }

        printf('Processed items %d', $items);
    }

    /**
     * @title "Repair Amazon Products without variations"
     * @description "Repair Amazon Products without variations"
     * @new_line
     */
    public function repairAmazonProductWithoutVariationsAction()
    {
        ini_set('display_errors', 1);

        $items = 0;

        $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Product');
        $collection->getSelect()->joinLeft(
            array('mlpv' => Mage::getResourceModel('M2ePro/Listing_Product_Variation')->getMainTable()),
            '`second_table`.`listing_product_id` = `mlpv`.`listing_product_id`',
            array()
        );
        $collection->addFieldToFilter('is_variation_product', 1);
        $collection->addFieldToFilter('is_variation_product_matched', 1);
        $collection->addFieldToFilter('mlpv.id', array('null' => true));

        /* @var $item Ess_M2ePro_Model_Listing_Product */
        while ($item = $collection->fetchItem()) {

            $item->getChildObject()->setData('is_variation_product_matched' , 0)->save();
            $items++;
        }

        printf('Processed items %d', $items);
    }

    //########################################

    /**
     * @title "Check Server Connection"
     * @description "Send test request to server and check connection"
     */
    public function serverCheckConnectionAction()
    {
        $resultHtml = '';

        try {

            $response = Mage::helper('M2ePro/Server_Request')->single(
                array('timeout' => 30), null, null, false, false
            );

        } catch (Ess_M2ePro_Model_Exception_Connection $e) {

            $resultHtml .= "<h2>{$e->getMessage()}</h2><pre><br/>";
            $additionalData = $e->getAdditionalData();

            if (!empty($additionalData['curl_info'])) {
                $resultHtml .= '</pre><h2>Report</h2><pre>';
                $resultHtml .= print_r($additionalData['curl_info'], true);
                $resultHtml .= '</pre>';
            }

            if (!empty($additionalData['curl_error_number']) && !empty($additionalData['curl_error_message'])) {
                $resultHtml .= '<h2 style="color:red;">Errors</h2>';
                $resultHtml .= $additionalData['curl_error_number'] .': '
                               . $additionalData['curl_error_message'] . '<br/><br/>';
            }

            return $this->getResponse()->setBody($resultHtml);

        } catch (Exception $e) {

            return $this->getResponse()->setBody("<h2>{$e->getMessage()}</h2><pre><br/>");
        }

        $resultHtml .= '<h2>Response</h2><pre>';
        $resultHtml .= print_r($response['body'], true);
        $resultHtml .= '</pre>';

        $resultHtml .= '</pre><h2>Report</h2><pre>';
        $resultHtml .= print_r($response['curl_info'], true);
        $resultHtml .= '</pre>';

        return $this->getResponse()->setBody($resultHtml);
    }

    //########################################

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = Mage::helper('M2ePro/View_Development')->getPageToolsTabUrl();

        return <<<HTML
<h2 style="margin: 20px 0 0 10px">
    {$messageText} <span style="color: grey; font-size: 10px;">
    <a href="{$backUrl}">[back]</a>
</h2>
HTML;
    }

    //########################################
}