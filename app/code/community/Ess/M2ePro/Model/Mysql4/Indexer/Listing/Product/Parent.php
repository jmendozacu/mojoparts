<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Mysql4_Indexer_Listing_Product_Parent extends Ess_M2ePro_Model_Mysql4_Abstract
{
    //########################################

    public function _construct()
    {
        $this->_init('M2ePro/Indexer_Listing_Product_Parent', 'listing_product_id');
    }

    //########################################

    public function build($listingId, $component)
    {
        if (!in_array($component, array(
            Ess_M2ePro_Helper_Component_Ebay::NICK,
            Ess_M2ePro_Helper_Component_Amazon::NICK
        ))) {
            throw new Ess_M2ePro_Model_Exception_Logic("Wrong component provided [{$component}]");
        }

        $select = $component == Ess_M2ePro_Helper_Component_Amazon::NICK
             ? $this->getBuildIndexForAmazonSelect($listingId)
             : $this->getBuildIndexForEbaySelect($listingId);

        $createDate = new DateTime('now', new DateTimeZone('UTC'));
        $createDate = $createDate->format('Y-m-d H:i:s');

        $select->columns(array(
            new Zend_Db_Expr($this->_getWriteAdapter()->quote($component)),
            new Zend_Db_Expr($this->_getWriteAdapter()->quote($listingId)),
            new Zend_Db_Expr($this->_getWriteAdapter()->quote($createDate))
        ));

        $query = $this->_getWriteAdapter()->insertFromSelect(
            $select,
            $this->getMainTable(),
            array(
                'listing_product_id',
                'min_price',
                'max_price',
                'component_mode',
                'listing_id',
                'create_date'
            ),
            Varien_Db_Adapter_Pdo_Mysql::INSERT_IGNORE
        );
        $this->_getWriteAdapter()->query($query);
    }

    public function clear($listingId = null)
    {
        $conditions = array();
        $listingId && $conditions['listing_id = ?'] = (int)$listingId;

        $this->_getWriteAdapter()->delete($this->getMainTable(), $conditions);
    }

    //########################################

    public function getBuildIndexForAmazonSelect($listingId)
    {
        $select = $this->_getWriteAdapter()->select()
            ->from(
                array('malp' => Mage::getResourceModel('M2ePro/Amazon_Listing_Product')->getMainTable()),
                array(
                    'variation_parent_id',
                    new Zend_Db_Expr(
                        "MIN(
                            IF(
                                malp.online_sale_price_start_date IS NOT NULL AND
                                malp.online_sale_price_end_date IS NOT NULL AND
                                malp.online_sale_price_start_date <= CURRENT_DATE() AND
                                malp.online_sale_price_end_date >= CURRENT_DATE(),
                                malp.online_sale_price,
                                malp.online_price
                            )
                        ) as variation_min_price"
                    ),
                    new Zend_Db_Expr(
                        "MAX(
                            IF(
                                malp.online_sale_price_start_date IS NOT NULL AND
                                malp.online_sale_price_end_date IS NOT NULL AND
                                malp.online_sale_price_start_date <= CURRENT_DATE() AND
                                malp.online_sale_price_end_date >= CURRENT_DATE(),
                                malp.online_sale_price,
                                malp.online_price
                            )
                        ) as variation_max_price"
                    )
                )
            )
            ->joinInner(
                array('mlp' => Mage::getResourceModel('M2ePro/Listing_Product')->getMainTable()),
                'malp.listing_product_id = mlp.id',
                array()
            )
            ->where('mlp.status IN (?)', array(
                Ess_M2ePro_Model_Listing_Product::STATUS_LISTED,
                Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED,
                Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN
            ))
            ->where('mlp.listing_id = ?', (int)$listingId)
            ->where('malp.variation_parent_id IS NOT NULL')
            ->group('malp.variation_parent_id');

        return $select;
    }

    public function getBuildIndexForEbaySelect($listingId)
    {
        $select = $this->_getWriteAdapter()->select()
            ->from(
                array('mlpv' => Mage::getResourceModel('M2ePro/Listing_Product_Variation')->getMainTable()),
                array(
                    'listing_product_id'
                )
            )
            ->joinInner(
                array('melpv' => Mage::getResourceModel('M2ePro/Ebay_Listing_Product_Variation')->getMainTable()),
                'mlpv.id = melpv.listing_product_variation_id',
                array(
                    new Zend_Db_Expr('MIN(`melpv`.`online_price`) as variation_min_price'),
                    new Zend_Db_Expr('MAX(`melpv`.`online_price`) as variation_max_price')
                )
            )
            ->joinInner(
                array('mlp' => Mage::getResourceModel('M2ePro/Listing_Product')->getMainTable()),
                'mlpv.listing_product_id = mlp.id',
                array()
            )
            ->where('mlp.listing_id = ?', (int)$listingId)
            ->where('melpv.status != ?', Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED)
            ->group('mlpv.listing_product_id');

        return $select;
    }

    //########################################
}