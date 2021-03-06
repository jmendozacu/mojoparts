<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Model_Ebay_Connector_Item_Single_Responser
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Responser
{
    /**
     * @var Ess_M2ePro_Model_Listing_Product
     */
    protected $listingProduct = array();

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Configurator
     */
    protected $configurator = NULL;

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Response
     */
    protected $responseObject = NULL;

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected $requestDataObject = NULL;

    protected $isSuccess = false;

    protected $isSkipped = false;

    //########################################

    public function __construct(array $params = array(), Ess_M2ePro_Model_Connector_Connection_Response $response)
    {
        parent::__construct($params, $response);

        $listingProductId = $this->params['product']['id'];
        $this->listingProduct = Mage::helper('M2ePro/Component_Ebay')->getObject('Listing_Product', $listingProductId);
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $messageText,
            Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_ERROR
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct,
            $message,
            Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH
        );
    }

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (empty($this->params['is_realtime'])) {
            $this->inspectProduct();
        }
    }

    protected function inspectProduct()
    {
        if (!$this->isSuccess && !$this->isSkipped) {
            return;
        }

        $runner = Mage::getModel('M2ePro/Synchronization_Templates_Synchronization_Runner');
        $runner->setConnectorModel('Ebay_Connector_Item_Dispatcher');
        $runner->setMaxProductsPerStep(100);

        $inspector = Mage::getModel('M2ePro/Ebay_Synchronization_Templates_Synchronization_Inspector');

        /** @var Ess_M2ePro_Model_Ebay_Listing_Product $ebayListingProduct */
        $ebayListingProduct = $this->listingProduct->getChildObject();

        if ($this->listingProduct->isListed()) {
            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');

            if ($inspector->isMeetStopRequirements($this->listingProduct)) {

                $action = Ess_M2ePro_Model_Listing_Product::ACTION_STOP;

                if ($ebayListingProduct->isOutOfStockControlEnabled()) {

                    $action = Ess_M2ePro_Model_Listing_Product::ACTION_REVISE;

                    $configurator->setParams(array('replaced_action' => Ess_M2ePro_Model_Listing_Product::ACTION_STOP));
                    $configurator->setPartialMode();
                    $configurator->allowQty()->allowVariations();
                }

                $runner->addProduct(
                    $this->listingProduct, $action, $configurator
                );

                $runner->execute();
                return;
            }

            $configurator->setPartialMode();

            $needRevise = false;

            if ($inspector->isMeetReviseQtyRequirements($this->listingProduct)) {
                $configurator->allowQty();
                $needRevise = true;
            }

            if ($inspector->isMeetRevisePriceRequirements($this->listingProduct)) {
                $configurator->allowPrice();
                $needRevise = true;
            }

            if (!$needRevise) {
                return;
            }

            if ($ebayListingProduct->isVariationsReady()) {
                $configurator->allowVariations();
            }

            $runner->addProduct(
                $this->listingProduct, Ess_M2ePro_Model_Listing_Product::ACTION_REVISE, $configurator
            );

            $runner->execute();
            return;
        }

        if ($this->listingProduct->isStopped() || $this->listingProduct->isHidden()) {

            if (!$inspector->isMeetRelistRequirements($this->listingProduct)) {
                return;
            }

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
            $action = Ess_M2ePro_Model_Listing_Product::ACTION_RELIST;

            if ($this->listingProduct->isHidden()) {

                $configurator->setParams(array('replaced_action' => Ess_M2ePro_Model_Listing_Product::ACTION_RELIST));
                $action = Ess_M2ePro_Model_Listing_Product::ACTION_REVISE;
            }

            if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isRelistSendData()) {
                $configurator->setPartialMode();
                $configurator->allowQty()->allowPrice()->allowVariations();
            }

            $runner->addProduct(
                $this->listingProduct, $action, $configurator
            );
            $runner->execute();
        }
    }

    //########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        $this->processMessages($this->getResponse()->getMessages()->getEntities());
    }

    protected function processResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            if (!empty($responseData['is_skipped'])) {
                $this->isSkipped = true;
            }

            return;
        }

        $responseData = $this->getPreparedResponseData();
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        $this->processCompleted($responseData, array(
            'is_images_upload_error' => $this->isImagesUploadFailed($responseMessages)
        ));
    }

    protected function processMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }
    }

    protected function processCompleted(array $data = array(), array $params = array())
    {
        $this->getResponseObject()->processSuccess($data, $params);

        $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $this->getSuccessfulMessage(),
            Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage(
            $this->listingProduct, $message
        );

        $this->isSuccess = true;
    }

    //----------------------------------------

    abstract protected function getSuccessfulMessage();

    //########################################

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * eBay internal error. The operation was not completed (code:34) (returned by M2e Pro server)
     */
    protected function isEbayApplicationErrorAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if (strpos($message->getText(), 'code:34') !== false) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 32704531: Can't upload product image on eBay (returned by M2e Pro server)
     */
    protected function isImagesUploadFailed(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 32704531) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 17: This item cannot be accessed because the listing has been deleted, is a Half.com listing,
     *     or you are not the seller.
     */
    protected function isItemCanNotBeAccessed(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 17) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 21919301: (UPC/EAN/ISBN) is missing a value. Enter a value and try again.
     */
    protected function isNewRequiredSpecificNeeded(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21919301) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 21916587: The multi-variation titles have been changed and were not updated on the eBay.
     * 21916626: Variations Specifics and Item Specifics entered for a Multi-SKU item should be different.
     * 21916603: Variation specifics cannot be changed in restricted revise
     * 21916664: Variation Specifics provided does not match with the variation specifics of the variations on the item.
     * 21916585: Duplicate custom variation label.
     * 21916582: Duplicate VariationSpecifics trait value in the VariationSpecificsSet container.
     * 21916672: The tags (MPN) is/are disabled as Variant.
     */
    protected function isVariationErrorAppeared(array $messages)
    {
        $errorCodes = array(
            21916587,
            21916626,
            21916603,
            21916664,
            21916585,
            21916582,
            21916672,
        );

        foreach ($messages as $message) {
            if (in_array($message->getCode(), $errorCodes)) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 21916884: Condition is required for this category.
     */
    protected function isConditionErrorAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21916884) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 488: The specified UUID has already been used; ListedByRequestAppId=1, item ID=%ited_id%.
     */
    protected function isDuplicateErrorByUUIDAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 488) {
                return $message;
            }
        }

        return false;
    }

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Response_Message[] $messages
     * @return Ess_M2ePro_Model_Connector_Connection_Response_Message|bool
     *
     * 21919067: This Listing is a duplicate of your item: %tem_title% (%item_id%).
     */
    protected function isDuplicateErrorByEbayEngineAppeared(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->getCode() == 21919067) {
                return $message;
            }
        }

        return false;
    }

    //########################################

    protected function tryToResolveVariationMpnErrors()
    {
        if (!$this->canPerformGetItemCall()) {
            return;
        }

        $variationMpnValues = $this->getVariationMpnDataFromEbay();
        if ($variationMpnValues === false) {
            return;
        }

        $isVariationMpnFilled = !empty($variationMpnValues);

        $this->listingProduct->setSetting('additional_data', 'is_variation_mpn_filled', $isVariationMpnFilled);
        if (!$isVariationMpnFilled) {
            $this->listingProduct->setSetting('additional_data', 'without_mpn_variation_issue', true);
        }

        $this->listingProduct->save();

        if (!empty($variationMpnValues)) {
            $this->fillVariationMpnValues($variationMpnValues);
        }

        $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            Mage::helper('M2ePro')->__(
                'It has been detected that this Item failed to be updated on eBay because of the errors.
                M2E Pro will automatically try to apply another solution to Revise this Item.'),
            Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
        );

        $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

        $this->processAdditionalAction($this->getActionType(), $this->getConfigurator());
    }

    protected function canPerformGetItemCall()
    {
        if ($this->getStatusChanger() == Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_USER) {
            return true;
        }

        $getItemCallsCount   = 0;
        $getItemLastCallDate = NULL;

        $maxAllowedGetItemCallsCount = 2;

        $additionalData = $this->listingProduct->getAdditionalData();
        if (!empty($additionalData['get_item_calls_statistic'])) {
            $getItemCallsCount   = $additionalData['get_item_calls_statistic']['count'];
            $getItemLastCallDate = $additionalData['get_item_calls_statistic']['last_call_date'];
        }

        if ($getItemCallsCount >= $maxAllowedGetItemCallsCount) {
            $minAllowedDate = new DateTime('now', new DateTimeZone('UTC'));
            $minAllowedDate->modify('- 1 day');

            if (strtotime($getItemLastCallDate) > $minAllowedDate->format('U')) {
                return false;
            }

            $getItemCallsCount = 0;
        }

        $getItemCallsCount++;
        $getItemLastCallDate = Mage::helper('M2ePro')->getCurrentGmtDate();

        $additionalData['get_item_calls_statistic']['count']           = $getItemCallsCount;
        $additionalData['get_item_calls_statistic']['last_call_date']  = $getItemLastCallDate;

        $this->listingProduct->setSettings('additional_data', $additionalData);
        $this->listingProduct->save();

        return true;
    }

    protected function getVariationMpnDataFromEbay()
    {
        /** @var Ess_M2ePro_Model_Ebay_Listing_Product $ebayListingProduct */
        $ebayListingProduct = $this->listingProduct->getChildObject();

        /** @var Ess_M2ePro_Model_Connector_Command_RealTime_Virtual $connector */
        $connector = Mage::getModel('M2ePro/Ebay_Connector_Dispatcher')->getVirtualConnector(
            'item', 'get', 'info',
            array(
                'item_id' => $ebayListingProduct->getEbayItemIdReal(),
                'parser_type' => 'standard',
                'full_variations_mode' => true
            ), 'result', $this->getMarketplace(), $this->getAccount()
        );

        try {
            $connector->process();
        } catch (Exception $exception) {
            Mage::helper('M2ePro/Module_Exception')->process($exception);
            return false;
        }

        $itemData = $connector->getResponseData();
        if (empty($itemData['variations'])) {
            return array();
        }

        $variationMpnValues = array();

        foreach ($itemData['variations'] as $variation) {
            if (empty($variation['specifics']['MPN'])) {
                continue;
            }

            $mpnValue = $variation['specifics']['MPN'];
            unset($variation['specifics']['MPN']);

            $variationMpnValues[] = array(
                'mpn'       => $mpnValue,
                'sku'       => $variation['sku'],
                'specifics' => $variation['specifics'],
            );
        }

        return $variationMpnValues;
    }

    /**
     * @param $variationMpnValues
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    protected function fillVariationMpnValues($variationMpnValues)
    {
        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Variation_Collection $variationCollection */
        $variationCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Listing_Product_Variation');
        $variationCollection->addFieldToFilter('listing_product_id', $this->listingProduct->getId());

        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Variation_Option_Collection $variationOptionCollection */
        $variationOptionCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection(
            'Listing_Product_Variation_Option'
        );
        $variationOptionCollection->addFieldToFilter(
            'listing_product_variation_id', $variationCollection->getColumnValues('id')
        );

        /** @var Ess_M2ePro_Model_Listing_Product_Variation[] $variations */
        $variations = $variationCollection->getItems();

        /** @var Ess_M2ePro_Model_Listing_Product_Variation_Option[] $variationOptions */
        $variationOptions = $variationOptionCollection->getItems();

        foreach ($variations as $variation) {
            $specifics = array();

            foreach ($variationOptions as $id => $variationOption) {
                if ($variationOption->getListingProductVariationId() != $variation->getId()) {
                    continue;
                }

                $specifics[$variationOption->getAttribute()] = $variationOption->getOption();
                unset($variationOptions[$id]);
            }

            /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            foreach ($variationMpnValues as $id => $variationMpnValue) {
                if ($ebayVariation->getOnlineSku() != $variationMpnValue['sku'] &&
                    $specifics != $variationMpnValue['specifics']
                ) {
                    continue;
                }

                $additionalData = $variation->getAdditionalData();

                if (!isset($additionalData['ebay_mpn_value']) ||
                    $additionalData['ebay_mpn_value'] != $variationMpnValue['mpn']
                ) {
                    $additionalData['ebay_mpn_value'] = $variationMpnValue['mpn'];

                    $variation->setSettings('additional_data', $additionalData);
                    $variation->save();
                }

                unset($variationMpnValues[$id]);

                break;
            }
        }
    }

    protected function processDuplicateByUUID(Ess_M2ePro_Model_Connector_Connection_Response_Message $message)
    {
        $duplicateItemId = null;
        preg_match('/item ID=(\d+)\.$/', $message->getText(), $matches);
        if (!empty($matches[1])) {
            $duplicateItemId = $matches[1];
        }

        $this->listingProduct->setData('is_duplicate', 1);
        $this->listingProduct->setSetting('additional_data', 'item_duplicate_action_required', array(
            'item_id' => $duplicateItemId,
            'source'  => 'uuid',
            'message' => $message->getText()
        ));
        $this->listingProduct->save();
    }

    protected function processDuplicateByEbayEngine(Ess_M2ePro_Model_Connector_Connection_Response_Message $message)
    {
        $duplicateItemId = null;
        preg_match('/.*\((\d+)\)/', $message->getText(), $matches);
        if (!empty($matches[1])) {
            $duplicateItemId = $matches[1];
        }

        $this->listingProduct->setData('is_duplicate', 1);
        $this->listingProduct->setSetting('additional_data', 'item_duplicate_action_required', array(
            'item_id' => $duplicateItemId,
            'source'  => 'ebay_engine',
            'message' => $message->getText()
        ));
        $this->listingProduct->save();
    }

    //########################################

    protected function getConfigurator()
    {
        if (empty($this->configurator)) {

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
            $configurator->setData($this->params['product']['configurator']);

            $this->configurator = $configurator;
        }

        return $this->configurator;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Response
     */
    protected function getResponseObject()
    {
        if (empty($this->responseObject)) {

            /* @var $response Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Response */
            $response = Mage::getModel(
                'M2ePro/Ebay_Listing_Product_Action_Type_'.$this->getOrmActionType().'_Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($this->listingProduct);
            $response->setConfigurator($this->getConfigurator());
            $response->setRequestData($this->getRequestDataObject());

            $requestMetaData = !empty($this->params['product']['request_metadata'])
                ? $this->params['product']['request_metadata'] : array();

            $response->setRequestMetaData($requestMetaData);

            $this->responseObject = $response;
        }

        return $this->responseObject;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function getRequestDataObject()
    {
        if (empty($this->requestDataObject)) {

            /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData $requestData */
            $requestData = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_RequestData');

            $requestData->setData($this->params['product']['request']);
            $requestData->setListingProduct($this->listingProduct);

            $this->requestDataObject = $requestData;
        }

        return $this->requestDataObject;
    }

    //########################################

    protected function markAsPotentialDuplicate()
    {
        $additionalData = $this->listingProduct->getAdditionalData();

        $additionalData['last_failed_action_data'] = array(
            'native_request_data' => $this->getRequestDataObject()->getData(),
            'previous_status' => $this->listingProduct->getStatus(),
            'action' => $this->getActionType(),
            'request_time' => $this->getResponse()->getRequestTime(),
        );

        $this->listingProduct->addData(array(
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_BLOCKED,
            'additional_data' => Mage::helper('M2ePro')->jsonEncode($additionalData),
        ))->save();

        $this->listingProduct->getChildObject()->updateVariationsStatus();
    }

    //########################################

    protected function processAdditionalAction($actionType,
                                               Ess_M2ePro_Model_Ebay_Listing_Product_Action_Configurator $configurator,
                                               array $params = array())
    {
        $listingProduct = clone $this->listingProduct;
        $listingProduct->setActionConfigurator($configurator);

        $params = array_merge(
            $params,
            array(
                'status_changer' => $this->getStatusChanger(),
                'is_realtime'    => true,
            )
        );

        $dispatcher = Mage::getModel('M2ePro/Ebay_Connector_Item_Dispatcher');
        $dispatcher->process($actionType, array($listingProduct), $params);

        $logsActionId = $this->params['logs_action_id'];
        if (!is_array($logsActionId)) {
            $logsActionId = array($logsActionId);
        }

        $logsActionId[] = $dispatcher->getLogsActionId();

        $this->params['logs_action_id'] = $logsActionId;
    }

    //########################################
}