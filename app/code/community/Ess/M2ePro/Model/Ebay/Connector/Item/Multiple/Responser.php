<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Model_Ebay_Connector_Item_Multiple_Responser
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Responser
{
    /**
     * @var Ess_M2ePro_Model_Listing_Product[]
     */
    protected $listingsProducts = array();

    /**
     * @var Ess_M2ePro_Model_Listing_Product[]
     */
    protected $successfulListingProducts = array();

    /**
     * @var Ess_M2ePro_Model_Listing_Product[]
     */
    protected $skippedListingsProducts = array();

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Configurator[]
     */
    protected $configurators = array();

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Response[]
     */
    protected $responsesObjects = array();

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData[]
     */
    protected $requestsDataObjects = array();

    protected $isResponseFailed = false;

    //########################################

    public function __construct(array $params = array(), Ess_M2ePro_Model_Connector_Connection_Response $response)
    {
        parent::__construct($params, $response);

        $listingsProductsIds = array_keys($this->params['products']);

        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Collection $listingProductCollection */
        $listingProductCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Listing_Product');
        $listingProductCollection->addFieldToFilter('id', array('in' => $listingsProductsIds));

        $this->listingsProducts = $listingProductCollection->getItems();
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

        foreach ($this->listingsProducts as $listingProduct) {
            $this->getLogger()->logListingProductMessage(
                $listingProduct,
                $message,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH
            );
        }
    }

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (empty($this->params['is_realtime'])) {
            $this->inspectProducts();
        }
    }

    protected function inspectProducts()
    {
        $listingsProductsByStatus = array(
            Ess_M2ePro_Model_Listing_Product::STATUS_LISTED  => array(),
            Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED => array(),
            Ess_M2ePro_Model_Listing_Product::STATUS_HIDDEN  => array(),
        );

        foreach ($this->successfulListingProducts as $listingProduct) {
            $listingsProductsByStatus[$listingProduct->getStatus()][$listingProduct->getId()] = $listingProduct;
        }

        foreach ($this->skippedListingsProducts as $listingProduct) {
            $listingsProductsByStatus[$listingProduct->getStatus()][$listingProduct->getId()] = $listingProduct;
        }

        $runner = Mage::getModel('M2ePro/Synchronization_Templates_Synchronization_Runner');
        $runner->setConnectorModel('Ebay_Connector_Item_Dispatcher');
        $runner->setMaxProductsPerStep(100);

        $inspector = Mage::getModel('M2ePro/Ebay_Synchronization_Templates_Synchronization_Inspector');

        foreach ($listingsProductsByStatus[Ess_M2ePro_Model_Listing_Product::STATUS_LISTED] as $listingProduct) {

            /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');

            /** @var Ess_M2ePro_Model_Ebay_Listing_Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            if ($inspector->isMeetStopRequirements($listingProduct)) {

                $action = Ess_M2ePro_Model_Listing_Product::ACTION_STOP;

                if ($ebayListingProduct->isOutOfStockControlEnabled()) {

                    $action = Ess_M2ePro_Model_Listing_Product::ACTION_REVISE;

                    $configurator->setParams(array('replaced_action' => Ess_M2ePro_Model_Listing_Product::ACTION_STOP));
                    $configurator->setPartialMode();
                    $configurator->allowQty()->allowVariations();
                }

                $runner->addProduct(
                    $listingProduct, $action, $configurator
                );

                continue;
            }

            $configurator->setPartialMode();

            $needRevise = false;

            if ($inspector->isMeetReviseQtyRequirements($listingProduct)) {
                $configurator->allowQty();
                $needRevise = true;
            }

            if ($inspector->isMeetRevisePriceRequirements($listingProduct)) {
                $configurator->allowPrice();
                $needRevise = true;
            }

            if (!$needRevise) {
                continue;
            }

            if ($ebayListingProduct->isVariationsReady()) {
                $configurator->allowVariations();
            }

            $runner->addProduct(
                $listingProduct, Ess_M2ePro_Model_Listing_Product::ACTION_REVISE, $configurator
            );
        }

        $products = array_merge($listingsProductsByStatus[Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED],
                                $listingsProductsByStatus[Ess_M2ePro_Model_Listing_Product::STATUS_HIDDEN]);

        foreach ($products as $listingProduct) {

            /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */

            if (!$inspector->isMeetRelistRequirements($listingProduct)) {
                continue;
            }

            /** @var Ess_M2ePro_Model_Ebay_Listing_Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
            $action = Ess_M2ePro_Model_Listing_Product::ACTION_RELIST;

            if ($listingProduct->isHidden()) {

                $configurator->setParams(array('replaced_action' => Ess_M2ePro_Model_Listing_Product::ACTION_RELIST));
                $action = Ess_M2ePro_Model_Listing_Product::ACTION_REVISE;
            }

            if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isRelistSendData()) {
                $configurator->setPartialMode();
                $configurator->allowQty()->allowPrice()->allowVariations();
            }

            $runner->addProduct(
                $listingProduct, $action, $configurator
            );
        }

        $runner->execute();
    }

    //########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->listingsProducts as $listingProduct) {
            $this->processMessages($listingProduct, $this->getResponse()->getMessages()->getEntities());
        }
    }

    protected function processResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getPreparedResponseData();

        foreach ($this->listingsProducts as $listingProduct) {

            $messagesData = array();
            if (!empty($responseData['result'][$listingProduct->getId()]['messages'])) {
                $messagesData = $responseData['result'][$listingProduct->getId()]['messages'];
            }

            $messages = array();

            foreach ($messagesData as $messageData) {
                $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
                $message->initFromResponseData($messageData);

                $messages[] = $message;
            }

            if (!$this->processMessages($listingProduct, $messages)) {
                if (!empty($responseData['result'][$listingProduct->getId()]['is_skipped'])) {
                    $this->skippedListingsProducts[$listingProduct->getId()] = $listingProduct;
                }

                continue;
            }

            $successData = $this->getSuccessfulData($listingProduct);
            $this->processCompleted($listingProduct, $successData);
        }
    }

    protected function processMessages(Ess_M2ePro_Model_Listing_Product $listingProduct, array $messages)
    {
        $hasError = false;

        foreach ($messages as $message) {

            /** @var Ess_M2ePro_Model_Connector_Connection_Response_Message $message */

            !$hasError && $hasError = $message->isError();

            $this->getLogger()->logListingProductMessage(
                $listingProduct, $message
            );
        }

        return !$hasError;
    }

    protected function processCompleted(Ess_M2ePro_Model_Listing_Product $listingProduct,
                                        array $data = array(), array $params = array())
    {
        $this->getResponseObject($listingProduct)->processSuccess($data, $params);

        $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            $this->getSuccessfulMessage($listingProduct),
            Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_SUCCESS
        );

        $this->getLogger()->logListingProductMessage(
            $listingProduct, $message
        );

        $this->successfulListingProducts[$listingProduct->getId()] = $listingProduct;
    }

    //----------------------------------------

    protected function getSuccessfulData(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $responseData = $this->getPreparedResponseData();
        if (empty($responseData['result'][$listingProduct->getId()])) {
            return array();
        }

        $listingProductResponseData = $responseData['result'][$listingProduct->getId()];
        unset($listingProductResponseData['messages']);

        return $listingProductResponseData;
    }

    //----------------------------------------

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return string
     */
    abstract protected function getSuccessfulMessage(Ess_M2ePro_Model_Listing_Product $listingProduct);

    //########################################

    protected function getConfigurator(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (empty($this->configurators[$listingProduct->getId()])) {

            $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
            $configurator->setData($this->params['products'][$listingProduct->getId()]['configurator']);

            $this->configurators[$listingProduct->getId()] = $configurator;
        }

        return $this->configurators[$listingProduct->getId()];
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Response
     */
    protected function getResponseObject(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (!isset($this->responsesObjects[$listingProduct->getId()])) {

            /* @var $response Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Response */
            $response = Mage::getModel(
                'M2ePro/Ebay_Listing_Product_Action_Type_'.$this->getOrmActionType().'_Response'
            );

            $response->setParams($this->params['params']);
            $response->setListingProduct($listingProduct);
            $response->setConfigurator($this->getConfigurator($listingProduct));
            $response->setRequestData($this->getRequestDataObject($listingProduct));

            $requestMetaData = !empty($this->params['products'][$listingProduct->getId()]['request_metadata'])
                ? $this->params['products'][$listingProduct->getId()]['request_metadata'] : array();

            $response->setRequestMetaData($requestMetaData);

            $this->responsesObjects[$listingProduct->getId()] = $response;
        }

        return $this->responsesObjects[$listingProduct->getId()];
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function getRequestDataObject(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (!isset($this->requestsDataObjects[$listingProduct->getId()])) {

            /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData $requestData */
            $requestData = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_RequestData');

            $requestData->setData($this->params['products'][$listingProduct->getId()]['request']);
            $requestData->setListingProduct($listingProduct);

            $this->requestsDataObjects[$listingProduct->getId()] = $requestData;
        }

        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    //########################################
}