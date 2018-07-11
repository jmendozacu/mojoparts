<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

use Ess_M2ePro_Model_Ebay_Listing_Product_Action_DataBuilder_Description as BuilderDescription;

class Ess_M2ePro_Model_Ebay_Listing_Product_Variation_Resolver
{
    //########################################

    const MPN_SPECIFIC_NAME = 'MPN';

    /** @var Ess_M2ePro_Model_Listing_Product */
    protected $listingProduct;
    protected $isAllowedToSave = false;

    protected $moduleVariations  = array();
    protected $channelVariations = array();

    /** @var Ess_M2ePro_Model_Response_Message_Set */
    protected $messagesSet;

    //########################################

    public function process()
    {
        try {

            $this->getMessagesSet()->clearEntities();
            $this->validate();

            $this->moduleVariations  = $this->getModuleVariations();
            $this->channelVariations = $this->getChannelVariations();

            $this->validateModuleVariations();

            $this->processVariationsWhichDoNotExistOnTheChannel();
            $this->processVariationsWhichAreNotExistInTheModule();

        } catch (\Exception $exception) {

            $message = Mage::getModel('M2ePro/Response_Message');
            $message->initFromException($exception);

            $this->getMessagesSet()->addEntity($message);
        }
    }

    //########################################

    private function validate()
    {
        if (!($this->listingProduct instanceof Ess_M2ePro_Model_Listing_Product)) {
            throw new Ess_M2ePro_Model_Exception_Logic(sprintf(
                'Listing product is not provided [%s].', get_class($this->listingProduct)
            ));
        }

        if (!$this->listingProduct->getChildObject()->isVariationsReady()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Not a variation product.');
        }

        if (!$this->listingProduct->isRevisable()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Not a reviseble product.');
        }

        return true;
    }

    private function validateModuleVariations()
    {
        $skus = array();
        $options = array();

        $duplicatedSkus = array();
        $duplicatedOptions = array();

        foreach ($this->moduleVariations as $variation) {

            $sku = $variation['sku'];
            $option = $this->getVariationHash($variation);

            if (empty($sku)) {
                continue;
            }

            if (in_array($sku, $skus)) {
                $duplicatedSkus[] = $sku;
            } else {
                $skus[] = $sku;
            }

            if (in_array($option, $options)) {
                $duplicatedOptions[] = $option;
            } else {
                $options[] = $option;
            }
        }

        if (!empty($duplicatedSkus)) {

            throw new Ess_M2ePro_Model_Exception_Logic(sprintf(
                'Duplicated SKUs: ', implode(',', $duplicatedSkus)
            ));
        }

        if (!empty($duplicatedOptions)) {

            throw new Ess_M2ePro_Model_Exception_Logic(sprintf(
                'Duplicated Options: ', implode(',', $duplicatedOptions)
            ));
        }
    }

    //########################################

    private function getModuleVariations()
    {
        $variationUpdater = Mage::getModel('M2ePro/Ebay_Listing_Product_Variation_Updater');
        $variationUpdater->process($this->listingProduct);

        //--
        $trimmedSpecificsReplacements = array();
        $specificsReplacements = $this->listingProduct->getSetting(
            'additional_data', 'variations_specifics_replacements', array()
        );

        foreach ($specificsReplacements as $findIt => $replaceBy) {
            $trimmedSpecificsReplacements[trim($findIt)] = trim($replaceBy);
        }
        //--

        $variations = array();
        foreach ($this->listingProduct->getVariations(true) as $variation) {

            /**@var Ess_M2ePro_Model_Ebay_Listing_Product_Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            $tempVariation = array(
                'sku'           => $ebayVariation->getOnlineSku(),
                'price'         => $ebayVariation->getOnlinePrice(),
                'quantity'      => $ebayVariation->getOnlineQty(),
                'quantity_sold' => $ebayVariation->getOnlineQtySold(),
                'specifics'     => array(),
                'details'       => array()
            );

            //--------------------------------
            foreach ($variation->getOptions(true) as $option) {
                /**@var Ess_M2ePro_Model_Listing_Product_Variation_Option $option */

                $optionName  = trim($option->getAttribute());
                $optionValue = trim($option->getOption());

                if (array_key_exists($optionName, $trimmedSpecificsReplacements)) {
                    $optionName = $trimmedSpecificsReplacements[$optionName];
                }

                $tempVariation['specifics'][$optionName] = $optionValue;
            }

            $this->insertVariationMpn($variation, $tempVariation);
            //--------------------------------

            //-- MPN Specific has been changed
            //--------------------------------
            if (!empty($tempVariation['details']['mpn_previous']) && !empty($tempVariation['details']['mpn']) &&
                $tempVariation['details']['mpn_previous'] != $tempVariation['details']['mpn']) {

                $oneMoreVariation = array(
                    'qty'       => 0,
                    'price'     => $tempVariation['price'],
                    'sku'       => 'del-' . sha1(microtime(1) . $tempVariation['sku']),
                    'add'       => 0,
                    'delete'    => 1,
                    'specifics' => $tempVariation['specifics'],
                    'has_sales' => true,
                    'details'   => array(
                        'mpn' => $tempVariation['details']['mpn_previous']
                    )
                );

                if (!empty($trimmedSpecificsReplacements)) {
                    $oneMoreVariation['variations_specifics_replacements'] = $trimmedSpecificsReplacements;
                }
                //--------------------------------

                $variations[] = $oneMoreVariation;
            }
            unset($tempVariation['details']['mpn_previous']);
            //--------------------------------

            $variations[] = $tempVariation;
        }

        //--------------------------------
        $variationsThatCanNoBeDeleted = $this->listingProduct->getSetting(
            'additional_data', 'variations_that_can_not_be_deleted', array()
        );

        foreach ($variationsThatCanNoBeDeleted as $canNoBeDeleted) {

            $variations[] = array(
                'sku'           => $canNoBeDeleted['sku'],
                'price'         => $canNoBeDeleted['price'],
                'quantity'      => $canNoBeDeleted['qty'],
                'quantity_sold' => $canNoBeDeleted['qty'],
                'specifics'     => $canNoBeDeleted['specifics'],
                'details'       => $canNoBeDeleted['details']
            );
        }
        //--------------------------------

        return $variations;
    }

    private function insertVariationMpn(Ess_M2ePro_Model_Listing_Product_Variation $variation, &$tempVariation)
    {
        $additionalData = $variation->getAdditionalData();
        if (!empty($additionalData['ebay_mpn_value'])) {

            $tempVariation['details']['mpn'] = $additionalData['ebay_mpn_value'];

            $isMpnCanBeChanged = Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
                '/component/ebay/variation/', 'mpn_can_be_changed'
            );

            if (!$isMpnCanBeChanged) {
                return;
            }

            $tempVariation['details']['mpn_previous'] = $additionalData['ebay_mpn_value'];
        }

        if (isset($additionalData['product_details']['mpn'])) {

            $tempVariation['details']['mpn'] = $additionalData['product_details']['mpn'];
            return;
        }

        /** @var Ess_M2ePro_Model_Ebay_Listing_Product $ebayListingProduct */
        $ebayListingProduct = $this->listingProduct->getChildObject();
        $ebayDescriptionTemplate = $ebayListingProduct->getEbayDescriptionTemplate();

        if ($ebayDescriptionTemplate->isProductDetailsModeNone('brand')) {
            return;
        }

        if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply('brand')) {

            $tempVariation['details']['mpn']
                = Ess_M2ePro_Model_Ebay_Listing_Product_Action_DataBuilder_General::PRODUCT_DETAILS_DOES_NOT_APPLY;
            return;
        }

        if (!$this->listingProduct->getMagentoProduct()->isConfigurableType() &&
            !$this->listingProduct->getMagentoProduct()->isGroupedType()) {
            return;
        }

        $attribute = $ebayDescriptionTemplate->getProductDetailAttribute('mpn');

        if (!$attribute) {
            return;
        }

        /** @var $option Ess_M2ePro_Model_Ebay_Listing_Product_Variation_Option */
        $options = $variation->getOptions(true);
        $option = reset($options);

        $tempValue = $option->getMagentoProduct()->getAttributeValue($attribute);
        if (!$tempValue) {
            return;
        }

        $tempVariation['details']['mpn'] = $tempValue;
    }

    private function getChannelVariations()
    {
        /** @var Ess_M2ePro_Model_Connector_Command_RealTime_Virtual $connector */
        $connector = Mage::getModel('M2ePro/Ebay_Connector_Dispatcher')->getVirtualConnector(
            'item', 'get', 'info',
            array(
                'item_id'              => $this->listingProduct->getChildObject()->getEbayItemIdReal(),
                'parser_type'          => 'standard',
                'full_variations_mode' => true
            ),
            'result',
            $this->listingProduct->getMarketplace(), $this->listingProduct->getAccount()
        );

        $connector->process();
        $result = $connector->getResponseData();

        if (empty($result['variations'])) {
            throw new Ess_M2ePro_Model_Exception_Logic('Unable to retrieve variations from channel.');
        }

        $variations = array();
        foreach ($result['variations'] as $variation) {

            $tempVariation = array(
                'sku'           => $variation['sku'],
                'price'         => $variation['price'],
                'quantity'      => $variation['quantity'],
                'quantity_sold' => $variation['quantity_sold'],
                'specifics'     => $variation['specifics'],
                'details'       => array()
            );

            if (isset($tempVariation['specifics'][self::MPN_SPECIFIC_NAME])) {

                $tempVariation['details']['mpn'] = $tempVariation['specifics'][self::MPN_SPECIFIC_NAME];
                unset($tempVariation['specifics'][self::MPN_SPECIFIC_NAME]);
            }

            $variations[] = $tempVariation;
        }

        return $variations;
    }

    //########################################

    private function getVariationsWhichDoNotExistOnChannel()
    {
        $variations = array();

        foreach ($this->moduleVariations as $moduleVariation) {
            foreach ($this->channelVariations as $channelVariation) {
                if ($this->isVariationEqualWithCurrent($channelVariation, $moduleVariation)) {
                    continue 2;
                }
            }
            $variations[] = $moduleVariation;
        }

        return $variations;
    }

    private function getVariationsWhichDoNotExistInModule()
    {
        $variations = array();

        foreach ($this->channelVariations as $channelVariation) {
            foreach ($this->moduleVariations as $moduleVariation) {
                if ($this->isVariationEqualWithCurrent($channelVariation, $moduleVariation)) {
                    continue 2;
                }
            }
            $variations[] = $channelVariation;
        }

        return $variations;
    }

    //########################################

    private function processVariationsWhichDoNotExistOnTheChannel()
    {
        $variations = $this->getVariationsWhichDoNotExistOnChannel();
        if (empty($variations)) {
            return;
        }

        foreach ($variations as $variation) {

            $this->addNotice(sprintf(
                "SKU %s will be added to the Channel. Hash: %s",
                $variation['sku'], $this->getVariationHash($variation)
            ));
        }

        if (!$this->isAllowedToSave) {
            return;
        }
    }

    /**
     * variations_that_can_not_be_deleted will be filled up
     */
    private function processVariationsWhichAreNotExistInTheModule()
    {
        $variations = $this->getVariationsWhichDoNotExistInModule();
        if (empty($variations)) {
            return;
        }

        foreach ($variations as $variation) {

            $this->addWarning(sprintf(
                "SKU %s will be added to the Module. Hash: %s",
                $variation['sku'], $this->getVariationHash($variation)
            ));
        }

        if (!$this->isAllowedToSave) {
            return;
        }

        $variationsThatCanNoBeDeleted = $this->listingProduct->getSetting(
            'additional_data', 'variations_that_can_not_be_deleted', array()
        );

        foreach ($variations as $variation) {

            $variationsThatCanNoBeDeleted[] = array(
                'qty'       => 0,
                'price'     => $variation['price'],
                'sku'       => !empty($variation['sku']) ? 'del-' . sha1(microtime(1).$variation['sku']) : '',
                'add'       => 0,
                'delete'    => 1,
                'specifics' => $variation['specifics'],
                'details'   => $variation['details'],
                'has_sales' => true,
            );
        }

        $this->listingProduct->setSetting(
            'additional_data', 'variations_that_can_not_be_deleted', $variationsThatCanNoBeDeleted
        );
        $this->listingProduct->save();
    }

    //########################################

    private function isVariationEqualWithCurrent(array $channelVariation, array $moduleVariation)
    {
        if (count($channelVariation['specifics']) != count($moduleVariation['specifics'])) {
            return false;
        }

        $channelMpn = isset($channelVariation['details']['mpn']) ? $channelVariation['details']['mpn'] : NULL;
        $moduleMpn  = isset($moduleVariation['details']['mpn'])  ? $moduleVariation['details']['mpn']  : NULL;

        if ($channelMpn != $moduleMpn) {
            return false;
        }

        foreach ($moduleVariation['specifics'] as $moduleVariationOptionName => $moduleVariationOptionValue) {

            $haveOption = false;
            foreach ($channelVariation['specifics'] as $channelVariationOptionName => $channelVariationOptionValue) {

                if (trim($moduleVariationOptionName)  == trim($channelVariationOptionName) &&
                    trim($moduleVariationOptionValue) == trim($channelVariationOptionValue))
                {
                    $haveOption = true;
                    break;
                }
            }

            if ($haveOption === false) {
                return false;
            }
        }

        return true;
    }

    private function getVariationHash($variation)
    {
        $hash = array();

        foreach ($variation['specifics'] as $name => $value) {
            $hash[] = trim($name) .'-'.trim($value);
        }

        if (!empty($variation['details']['mpn'])) {
            $hash[] = 'MPN' .'-'. $variation['details']['mpn'];
        }

        return implode('##', $hash);
    }

    //########################################

    public function setListingProduct(Ess_M2ePro_Model_Listing_Product $lp)
    {
        $this->listingProduct = $lp;
        return $this;
    }

    public function setIsAllowedToSave($value)
    {
        $this->isAllowedToSave = $value;
        return $this;
    }

    //----------------------------------------

    public function getMessagesSet()
    {
        if (is_null($this->messagesSet)) {
            $this->messagesSet = Mage::getModel('M2ePro/Response_Message_Set');
        }

        return $this->messagesSet;
    }

    //########################################

    protected function addError($messageText)
    {
        $message = Mage::getModel('M2ePro/Response_Message');
        $message->initFromPreparedData($messageText, $message::TYPE_ERROR);

        $this->getMessagesSet()->addEntity($message);
    }

    protected function addWarning($messageText)
    {
        $message = Mage::getModel('M2ePro/Response_Message');
        $message->initFromPreparedData($messageText, $message::TYPE_WARNING);

        $this->getMessagesSet()->addEntity($message);
    }

    protected function addNotice($messageText)
    {
        $message = Mage::getModel('M2ePro/Response_Message');
        $message->initFromPreparedData($messageText, $message::TYPE_NOTICE);

        $this->getMessagesSet()->addEntity($message);
    }

    //########################################
}