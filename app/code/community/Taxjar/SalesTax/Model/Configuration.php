<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Configuration Model
 * Set default config values in Magento
 */
class Taxjar_SalesTax_Model_Configuration
{
    /**
     * Sets shipping taxability in Magento
     *
     * @param string $configJson
     * @return void
     */
    public function setShippingTaxability($configJson)
    {
        $taxClass = 0;

        if ($configJson['freight_taxable']) {
            $taxClass = 4;
        }

        $this->_setConfig('tax/classes/shipping_tax_class', $taxClass);
    }

    /**
     * Sets tax basis in Magento
     *
     * @param string $configJson
     * @return void
     */
    public function setTaxBasis($configJson)
    {
        $basis = 'shipping';

        if ($configJson['tax_source'] === 'origin') {
            $basis = 'origin';
        }

        $this->_setConfig('tax/calculation/based_on', $basis);
    }

    /**
     * Set display settings for tax in Magento
     *
     * @param void
     * @return void
     */
    public function setDisplaySettings()
    {
        $settings = array(
            'tax/display/type', 
            'tax/display/shipping', 
            'tax/cart_display/price',
            'tax/cart_display/subtotal',
            'tax/cart_display/shipping'
        );

        foreach ($settings as $setting) {
            $this->_setConfig($setting, 1);
        }
    }

    /**
    * Setup the TaxJar API user
    *
    * @param string $apiKey
    * @return void
    */
    public function setApiSettings($apiKey)
    {
        $apiUser = Mage::getModel('api/user');
        $existingUserId = $apiUser->load('taxjar', 'username')->getUserId();

        if (!$existingUserId) {
            $apiUserId = $this->_createApiUser($apiKey);
            $parentRoleId = $this->_createApiRoles($apiUserId);
            $this->_createApiRules($parentRoleId);
        }
    }

    /**
    * Set the API resources for our API user
    *
    * @param integer $parentRoleId
    * @return void
    */
    private function _createApiRules($parentRoleId)
    {
        foreach ($this->_resourcesToAllow() as $resource) {
            $apiRule = Mage::getModel('api/rules');
            $apiRule->setRoleId($parentRoleId);
            $apiRule->setResourceId($resource);
            $apiRule->setRoleType('G');
            $apiRule->setApiPermission('allow');
            $apiRule->save();
        }

        foreach ($this->_resourcesToDeny() as $resource) {
            $apiRule = Mage::getModel('api/rules');
            $apiRule->setRoleId($parentRoleId);
            $apiRule->setResourceId($resource);
            $apiRule->setRoleType('G');
            $apiRule->setApiPermission('deny');
            $apiRule->save();
        }
    }

    /**
     * Set the roles for our API User
     *
     * @param integer $apiUserId
     * @return integer
     */
    private function _createApiRoles($apiUserId)
    {
        $parentApiRole = Mage::getModel('api/role');
        $parentApiRole->setRoleName('taxjar_api');
        $parentApiRole->setTreeLevel(1);
        $parentApiRole->setRoleType('G');
        $parentApiRole->save();
        $parentRoleId = $parentApiRole->getId();

        $childApiRole = Mage::getModel('api/role');
        $childApiRole->setRoleName('TaxJar');
        $childApiRole->setTreeLevel(1);
        $childApiRole->setParentId($parentRoleId);
        $childApiRole->setRoleType('U');
        $childApiRole->setUserId($apiUserId);
        $childApiRole->save();

        return $parentRoleId;
    }

    /**
     * Set the API resources for our API user
     *
     * @param void
     * @return void
     */
    private function _createApiUser($apiKey)
    {
        $apiUser = Mage::getModel('api/user');
        $apiUser->setUsername('taxjar');
        $apiUser->setFirstname('TaxJar');
        $apiUser->setLastname('Magento');
        $apiUser->setEmail('support@taxjar.com');
        $apiUser->setApiKey($apiKey);
        $apiUser->setIsActive(1);
        $apiUser->save();

        return $apiUser->getUserId(); 
    }

    /**
     * Store config
     *
     * @param string $path
     * @param string $value
     * @return void
     */
    private function _setConfig($path, $value)
    {
        Mage::getConfig()->saveConfig($path, $value, 'default', 0);
    }

    /**
     * Resources to allow for our API user
     *
     * @param void
     * @return array
     */
    private function _resourcesToAllow()
    {
        return array(
            'sales',
            'sales/order',
            'sales/order/change',
            'sales/order/info',
            'sales/order/shipment',
            'sales/order/shipment/create',
            'sales/order/shipment/comment',
            'sales/order/shipment/track',
            'sales/order/shipment/info',
            'sales/order/shipment/send',
            'sales/order/invoice',
            'sales/order/invoice/create',
            'sales/order/invoice/comment',
            'sales/order/invoice/capture',
            'sales/order/invoice/void',
            'sales/order/invoice/cancel',
            'sales/order/invoice/info',
            'sales/order/creditmemo',
            'sales/order/creditmemo/create',
            'sales/order/creditmemo/comment',
            'sales/order/creditmemo/cancel',
            'sales/order/creditmemo/info',
            'sales/order/creditmemo/list'
        );
    }

    /**
     * Resources to deny for our API user
     *
     * @param void
     * @return array
     */
    private function _resourcesToDeny()
    {
        return array(
            'core',
            'core/store',
            'core/store/info',
            'core/store/list',
            'core/magento',
            'core/magento/info',
            'directory',
            'directory/country',
            'directory/region',
            'customer',
            'customer/create',
            'customer/update',
            'customer/delete',
            'customer/info',
            'customer/address',
            'customer/address/create',
            'customer/address/update',
            'customer/address/delete',
            'customer/address/info',
            'catalog',
            'catalog/category',
            'catalog/category/create',
            'catalog/category/update',
            'catalog/category/move',
            'catalog/category/delete',
            'catalog/category/tree',
            'catalog/category/info',
            'catalog/category/attributes',
            'catalog/category/product',
            'catalog/category/product/assign',
            'catalog/category/product/update',
            'catalog/category/product/remove',
            'catalog/product',
            'catalog/product/create',
            'catalog/product/update',
            'catalog/product/delete',
            'catalog/product/update_tier_price',
            'catalog/product/info',
            'catalog/product/listOfAdditionalAttributes',
            'catalog/product/attributes',
            'catalog/product/attribute',
            'catalog/product/attribute/read',
            'catalog/product/attribute/write',
            'catalog/product/attribute/types',
            'catalog/product/attribute/create',
            'catalog/product/attribute/update',
            'catalog/product/attribute/remove',
            'catalog/product/attribute/info',
            'catalog/product/attribute/option',
            'catalog/product/attribute/option/add',
            'catalog/product/attribute/option/remove',
            'catalog/product/attribute/set',
            'catalog/product/attribute/set/list',
            'catalog/product/attribute/set/create',
            'catalog/product/attribute/set/remove',
            'catalog/product/attribute/set/attribute_add',
            'catalog/product/attribute/set/attribute_remove',
            'catalog/product/attribute/set/group_add',
            'catalog/product/attribute/set/group_rename',
            'catalog/product/attribute/set/group_remove',
            'catalog/product/link',
            'catalog/product/link/assign',
            'catalog/product/link/update',
            'catalog/product/link/remove',
            'catalog/product/media',
            'catalog/product/media/create',
            'catalog/product/media/update',
            'catalog/product/media/remove',
            'catalog/product/option',
            'catalog/product/option/add',
            'catalog/product/option/update',
            'catalog/product/option/types',
            'catalog/product/option/info',
            'catalog/product/option/list',
            'catalog/product/option/remove',
            'catalog/product/option/value',
            'catalog/product/option/value/list',
            'catalog/product/option/value/info',
            'catalog/product/option/value/add',
            'catalog/product/option/value/update',
            'catalog/product/option/value/remove',
            'catalog/product/tag',
            'catalog/product/tag/list',
            'catalog/product/tag/info',
            'catalog/product/tag/add',
            'catalog/product/tag/update',
            'catalog/product/tag/remove',
            'catalog/product/downloadable_link',
            'catalog/product/downloadable_link/add',
            'catalog/product/downloadable_link/list',
            'catalog/product/downloadable_link/remove',
            'cataloginventory',
            'cataloginventory/update',
            'cataloginventory/info',
            'cart',
            'cart/create',
            'cart/order',
            'cart/info',
            'cart/totals',
            'cart/license',
            'cart/product',
            'cart/product/add',
            'cart/product/update',
            'cart/product/remove',
            'cart/product/list',
            'cart/product/moveToCustomerQuote',
            'cart/customer',
            'cart/customer/set',
            'cart/customer/addresses',
            'cart/shipping',
            'cart/shipping/method',
            'cart/shipping/list',
            'cart/payment',
            'cart/payment/method',
            'cart/payment/list',
            'cart/coupon',
            'cart/coupon/add',
            'cart/coupon/remove',
            'giftmessage',
            'giftmessage/set',
            'all'
        );
    }
}
