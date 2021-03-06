<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

use Ess_M2ePro_Model_Ebay_Template_Description_Source as DescriptionSource;

class Ess_M2ePro_Model_Ebay_Listing_Product_Action_Request_Variations
    extends Ess_M2ePro_Model_Ebay_Listing_Product_Action_Request_Abstract
{
    //########################################

    /**
     * @return array
     */
    public function getData()
    {
        $data = array(
            'is_variation_item' => $this->getIsVariationItem()
        );

        $this->logLimitationsAndReasons();

        if (!$this->getIsVariationItem() || !$this->getConfigurator()->isVariationsAllowed()) {
            return $data;
        }

        $data['variation'] = $this->getVariationsData();

        $this->getConfigurator()->tryToIncreasePriority(
            Ess_M2ePro_Model_Ebay_Listing_Product_Action_Configurator::PRIORITY_VARIATION
        );

        if ($sets = $this->getSetsData()) {
            $data['variations_sets'] = $sets;
        }

        $data['variation_image'] = $this->getImagesData();

        if ($variationsThatCanNotBeDeleted = $this->getVariationsThatCanNotBeDeleted()) {
            $data['variations_that_can_not_be_deleted'] = $variationsThatCanNotBeDeleted;
        }

        return $data;
    }

    //########################################

    /**
     * @return array
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function getVariationsData()
    {
        $data = array();

        $qtyMode = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtyMode();

        $productsIds = array();
        $variationIdsIndexes = array();

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {
            /** @var $variation Ess_M2ePro_Model_Listing_Product_Variation */
            /** @var $ebayVariation Ess_M2ePro_Model_Ebay_Listing_Product_Variation */

            $ebayVariation = $variation->getChildObject();

            if (isset($this->validatorsData['variation_fixed_price_'.$variation->getId()])) {
                $variationPrice = $this->validatorsData['variation_fixed_price_'.$variation->getId()];
            } else {
                $variationPrice = $ebayVariation->getPrice();
            }

            $item = array(
                '_instance_' => $variation,
                'price'      => $variationPrice,
                'qty'        => $ebayVariation->isDelete() ? 0 : $ebayVariation->getQty(),
                'sku'        => $ebayVariation->getSku(),
                'add'        => $ebayVariation->isAdd(),
                'delete'     => $ebayVariation->isDelete(),
                'specifics'  => array()
            );

            if (($qtyMode == Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED ||
                $qtyMode == Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT) && !$item['delete']) {

                foreach ($variation->getOptions(true) as $option) {
                    $productsIds[] = $option->getProductId();
                }
            }

            if ($this->getEbayListingProduct()->isPriceDiscountStp()) {

                $priceDiscountData = array(
                    'original_retail_price' => $ebayVariation->getPriceDiscountStp()
                );

                if ($this->getEbayMarketplace()->isStpAdvancedEnabled()) {
                    $priceDiscountData = array_merge(
                        $priceDiscountData,
                        $this->getEbayListingProduct()->getEbaySellingFormatTemplate()
                             ->getPriceDiscountStpAdditionalFlags()
                    );
                }

                $item['price_discount_stp'] = $priceDiscountData;
            }

            if ($this->getEbayListingProduct()->isPriceDiscountMap()) {
                $priceDiscountMapData = array(
                    'minimum_advertised_price' => $ebayVariation->getPriceDiscountMap(),
                );

                $exposure = $ebayVariation->getEbaySellingFormatTemplate()->getPriceDiscountMapExposureType();
                $priceDiscountMapData['minimum_advertised_price_exposure'] =
                    Ess_M2ePro_Model_Ebay_Listing_Product_Action_Request_Selling::
                        getPriceDiscountMapExposureType($exposure);

                $item['price_discount_map'] = $priceDiscountMapData;
            }

            $variationDetails = $this->getVariationDetails($variation);

            if (!empty($variationDetails)) {
                $item['details'] = $variationDetails;
            }

            foreach ($variation->getOptions(true) as $option) {
                /** @var $option Ess_M2ePro_Model_Listing_Product_Variation_Option */

                $item['specifics'][$option->getAttribute()] = $option->getOption();
            }

            $data[] = $item;

            $variationIdsIndexes[$variation->getId()] = count($data) - 1;
        }

        $this->addMetaData('variation_ids_indexes', $variationIdsIndexes);

        $this->checkQtyWarnings($productsIds);

        return $data;
    }

    /**
     * @return bool
     */
    public function getSetsData()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (isset($additionalData['variations_sets'])) {
            return $additionalData['variations_sets'];
        }

        return false;
    }

    public function getVariationsThatCanNotBeDeleted()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (isset($additionalData['variations_that_can_not_be_deleted'])) {
            return $additionalData['variations_that_can_not_be_deleted'];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getImagesData()
    {
        $attributeLabels = array();

        if ($this->getMagentoProduct()->isConfigurableType()) {
            $attributeLabels = $this->getConfigurableImagesAttributeLabels();
        }

        if ($this->getMagentoProduct()->isGroupedType()) {
            $attributeLabels = array(Ess_M2ePro_Model_Magento_Product_Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL);
        }

        if (count($attributeLabels) <= 0) {
            return array();
        }

        return $this->getImagesDataByAttributeLabels($attributeLabels);
    }

    //########################################

    private function logLimitationsAndReasons()
    {
        if ($this->getMagentoProduct()->isProductWithoutVariations()) {
            return;
        }

        if (!$this->getEbayMarketplace()->isMultivariationEnabled()) {
            $this->addWarningMessage(
                Mage::helper('M2ePro')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: eBay Site allows to list only Simple Items.'
                )
            );
            return;
        }

        $isVariationEnabled = Mage::helper('M2ePro/Component_Ebay_Category_Ebay')
                                    ->isVariationEnabled(
                                        (int)$this->getCategorySource()->getMainCategory(),
                                        $this->getMarketplace()->getId()
                                    );

        if (!is_null($isVariationEnabled) && !$isVariationEnabled) {
            $this->addWarningMessage(
                Mage::helper('M2ePro')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: eBay Primary Category allows to list only Simple Items.'
                )
            );
            return;
        }

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled()) {
            $this->addWarningMessage(
                Mage::helper('M2ePro')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: ignore Variation Option is enabled in Price, Quantity and Format Policy.'
                )
            );
            return;
        }

        if (!$this->getEbayListingProduct()->isListingTypeFixed()) {
            $this->addWarningMessage(
                Mage::helper('M2ePro')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: Listing type "Auction" does not support Multi-Variations.'
                )
            );
            return;
        }
    }

    // ---------------------------------------

    private function getConfigurableImagesAttributeLabels()
    {
        $descriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        if (!$descriptionTemplate->isVariationConfigurableImages()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $attributeCodes = $descriptionTemplate->getDecodedVariationConfigurableImages();
        $attributes = array();

        foreach ($attributeCodes as $attributeCode) {
            /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            $attribute = $product->getResource()->getAttribute($attributeCode);

            if (!$attribute) {
                continue;
            }

            $attribute->setStoreId($product->getStoreId());
            $attributes[] = $attribute;
        }

        if (empty($attributes)) {
            return array();
        }

        $attributeLabels = array();

        /** @var $productTypeInstance Mage_Catalog_Model_Product_Type_Configurable */
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();

        foreach ($productTypeInstance->getConfigurableAttributes() as $configurableAttribute) {

            /** @var $configurableAttribute Mage_Catalog_Model_Product_Type_Configurable_Attribute */
            $configurableAttribute->setStoteId($product->getStoreId());

            foreach ($attributes as $attribute) {

                if ((int)$attribute->getAttributeId() == (int)$configurableAttribute->getAttributeId()) {

                    $attributeLabels = array_values($attribute->getStoreLabels());
                    $attributeLabels[] = $configurableAttribute->getData('label');
                    $attributeLabels[] = $attribute->getFrontendLabel();

                    $attributeLabels = array_filter($attributeLabels);

                    break 2;
                }
            }
        }

        if (empty($attributeLabels)) {

            $this->addNotFoundAttributesMessages(
                Mage::helper('M2ePro')->__('Change Images for Attribute'),
                $attributes
            );

            return array();
        }

        return $attributeLabels;
    }

    private function getImagesDataByAttributeLabels(array $attributeLabels)
    {
        $images = array();
        $imagesLinks = array();
        $attributeLabel = false;

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {
            /** @var $variation Ess_M2ePro_Model_Listing_Product_Variation */

            if ($variation->getChildObject()->isDelete()) {
                continue;
            }

            foreach ($variation->getOptions(true) as $option) {

                /** @var $option Ess_M2ePro_Model_Listing_Product_Variation_Option */

                $foundAttributeLabel = false;
                foreach ($attributeLabels as $tempLabel) {
                    if (strtolower($tempLabel) == strtolower($option->getAttribute())) {
                        $foundAttributeLabel = $option->getAttribute();
                        break;
                    }
                }

                if ($foundAttributeLabel === false) {
                    continue;
                }

                if (!isset($imagesLinks[$option->getOption()])) {
                    $imagesLinks[$option->getOption()] = array();
                }

                $currentCountOfImages = count($imagesLinks[$option->getOption()]);
                if ($currentCountOfImages >= DescriptionSource::VARIATION_IMAGES_COUNT_MAX) {
                    break;
                }

                $attributeLabel = $foundAttributeLabel;

                $optionImages = $this->getEbayListingProduct()->getEbayDescriptionTemplate()
                                     ->getSource($option->getMagentoProduct())
                                     ->getVariationImages();

                $links = array();
                foreach ($optionImages as $image) {

                    if (!$image->getUrl()) {
                        continue;
                    }

                    if ($currentCountOfImages + count($links) >= DescriptionSource::VARIATION_IMAGES_COUNT_MAX) {
                        break;
                    }

                    $links[] = $image->getUrl();
                    $images[] = $image;
                }

                $imagesLinks[$option->getOption()] = array_merge($links, $imagesLinks[$option->getOption()]);
            }
        }

        if (!$attributeLabel || !$imagesLinks) {
            return array();
        }

        if (!empty($images)) {
            $this->addMetaData('ebay_product_variation_images_hash',
                               Mage::helper('M2ePro/Component_Ebay_Images')->getHash($images));
        }

        return array(
            'specific' => $attributeLabel,
            'images'   => $imagesLinks
        );
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Ebay_Template_Category_Source
     */
    private function getCategorySource()
    {
        return $this->getEbayListingProduct()->getCategoryTemplateSource();
    }

    //########################################

    public function checkQtyWarnings($productsIds)
    {
        $qtyMode = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtyMode();
        if ($qtyMode == Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED ||
            $qtyMode == Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT) {

            $productsIds = array_unique($productsIds);
            $qtyWarnings = array();

            $listingProductId = $this->getListingProduct()->getId();
            $storeId = $this->getListing()->getStoreId();

            foreach ($productsIds as $productId) {
                if (!empty(Ess_M2ePro_Model_Magento_Product::$statistics
                        [$listingProductId][$productId][$storeId]['qty'])) {

                    $qtys = Ess_M2ePro_Model_Magento_Product::$statistics
                        [$listingProductId][$productId][$storeId]['qty'];
                    $qtyWarnings = array_unique(array_merge($qtyWarnings, array_keys($qtys)));
                }

                if (count($qtyWarnings) === 2) {
                    break;
                }
            }

            foreach ($qtyWarnings as $qtyWarningType) {
                $this->addQtyWarnings($qtyWarningType);
            }
        }
    }

    /**
     * @param int $type
     */
    public function addQtyWarnings($type)
    {
        if ($type === Ess_M2ePro_Model_Magento_Product::FORCING_QTY_TYPE_MANAGE_STOCK_NO) {
        // M2ePro_TRANSLATIONS
        // During the Quantity Calculation the Settings in the "Manage Stock No" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Manage Stock No" '.
                                     'field were taken into consideration.');
        }

        if ($type === Ess_M2ePro_Model_Magento_Product::FORCING_QTY_TYPE_BACKORDERS) {
            // M2ePro_TRANSLATIONS
            // During the Quantity Calculation the Settings in the "Backorders" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Backorders" '.
                                     'field were taken into consideration.');
        }
    }

    //########################################

    private function getVariationDetails(Ess_M2ePro_Model_Listing_Product_Variation $variation)
    {
        $data = array();

        /** @var Ess_M2ePro_Model_Ebay_Template_Description $ebayDescriptionTemplate */
        $ebayDescriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        $options = NULL;
        $additionalData = $variation->getAdditionalData();

        foreach (array('isbn','upc','ean','mpn') as $tempType) {

            if ($tempType == 'mpn' && !empty($additionalData['ebay_mpn_value'])) {
                $data[$tempType] = $additionalData['ebay_mpn_value'];
                continue;
            }

            if (isset($additionalData['product_details'][$tempType])) {
                $data[$tempType] = $additionalData['product_details'][$tempType];
                continue;
            }

            if ($tempType == 'mpn') {

                if ($ebayDescriptionTemplate->isProductDetailsModeNone('brand')) {
                    continue;
                }

                if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply('brand')) {
                    $data[$tempType] = Ess_M2ePro_Model_Ebay_Listing_Product_Action_Request_Description::
                                                                            PRODUCT_DETAILS_DOES_NOT_APPLY;
                    continue;
                }
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeNone($tempType)) {
                continue;
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply($tempType)) {
                $data[$tempType] = Ess_M2ePro_Model_Ebay_Listing_Product_Action_Request_Description::
                                                                            PRODUCT_DETAILS_DOES_NOT_APPLY;
                continue;
            }

            if (!$this->getMagentoProduct()->isConfigurableType() &&
                !$this->getMagentoProduct()->isGroupedType()) {
                continue;
            }

            $attribute = $ebayDescriptionTemplate->getProductDetailAttribute($tempType);

            if (!$attribute) {
                continue;
            }

            if (is_null($options)) {
                $options = $variation->getOptions(true);
            }

            /** @var $option Ess_M2ePro_Model_Listing_Product_Variation_Option */
            $option = reset($options);

            $this->searchNotFoundAttributes();
            $tempValue = $option->getMagentoProduct()->getAttributeValue($attribute);

            if (!$this->processNotFoundAttributes(strtoupper($tempType)) || !$tempValue) {
                continue;
            }

            $data[$tempType] = $tempValue;
        }

        return $this->deleteNotAllowedIdentifier($data);
    }

    private function deleteNotAllowedIdentifier(array $data)
    {
        if (empty($data)) {
            return $data;
        }

        $categoryId = $this->getCategorySource()->getMainCategory();
        $marketplaceId = $this->getMarketplace()->getId();
        $categoryFeatures = Mage::helper('M2ePro/Component_Ebay_Category_Ebay')
                                  ->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return $data;
        }

        $statusDisabled = Ess_M2ePro_Helper_Component_Ebay_Category_Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (array('ean','upc','isbn') as $identifier) {

            $key = $identifier.'_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {

                unset($data[$identifier]);

                // M2ePro_TRANSLATIONS
                // The value of %type% was not sent because it is not allowed in this Category
                $this->addWarningMessage(
                    Mage::helper('M2ePro')->__(
                        'The value of %type% was not sent because it is not allowed in this Category',
                        Mage::helper('M2ePro')->__(strtoupper($identifier))
                    )
                );
            }
        }

        return $data;
    }

    //########################################
}