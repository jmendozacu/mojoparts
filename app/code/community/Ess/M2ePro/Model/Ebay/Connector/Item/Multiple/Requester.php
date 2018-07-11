<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Model_Ebay_Connector_Item_Multiple_Requester
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Requester
{
    /** @var Ess_M2ePro_Model_Listing_Product[] $listingsProducts */
    protected $listingsProducts = array();

    /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Validator[] $validatorsObjects */
    protected $validatorsObjects = array();

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request[]
     */
    protected $requestsObjects = array();

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData[]
     */
    protected $requestsDataObjects = array();

    //########################################

    public function setListingsProducts(array $listingsProducts)
    {
        if (count($listingsProducts) > $this->getMaxProductsCount()) {
            throw new Ess_M2ePro_Model_Exception_Logic('Maximum products count is exceeded');
        }

        /** @var Ess_M2ePro_Model_Account $account */
        $account = reset($listingsProducts)->getAccount();
        /** @var Ess_M2ePro_Model_Marketplace $marketplace */
        $marketplace = reset($listingsProducts)->getMarketplace();

        $listingProductIds   = array();
        $actionConfigurators = array();

        foreach($listingsProducts as $listingProduct) {

            if (!($listingProduct instanceof Ess_M2ePro_Model_Listing_Product)) {
                throw new Ess_M2ePro_Model_Exception('Multiple Item Connector has received invalid Product data type');
            }

            if ($account->getId() != $listingProduct->getListing()->getAccountId()) {
                throw new Ess_M2ePro_Model_Exception(
                    'Multiple Item Connector has received Products from different Accounts'
                );
            }

            if ($marketplace->getId() != $listingProduct->getListing()->getMarketplaceId()) {
                throw new Ess_M2ePro_Model_Exception(
                    'Multiple Item Connector has received Products from different Marketplaces'
                );
            }

            $listingProductIds[] = $listingProduct->getId();

            if (!is_null($listingProduct->getActionConfigurator())) {
                $actionConfigurators[$listingProduct->getId()] = $listingProduct->getActionConfigurator();
            } else {
                $actionConfigurators[$listingProduct->getId()] = Mage::getModel(
                    'M2ePro/Ebay_Listing_Product_Action_Configurator'
                );
            }
        }

        /** @var Ess_M2ePro_Model_Mysql4_Listing_Product_Collection $listingProductCollection */
        $listingProductCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Listing_Product');
        $listingProductCollection->addFieldToFilter('id', array('in' => array_unique($listingProductIds)));

        /** @var Ess_M2ePro_Model_Listing_Product[] $actualListingsProducts */
        $actualListingsProducts = $listingProductCollection->getItems();

        if (empty($actualListingsProducts)) {
            throw new Ess_M2ePro_Model_Exception('All products were removed before connector processing');
        }

        foreach ($actualListingsProducts as $actualListingProduct) {
            $actualListingProduct->setActionConfigurator($actionConfigurators[$actualListingProduct->getId()]);
            $this->listingsProducts[$actualListingProduct->getId()] = $actualListingProduct;
        }

        $this->marketplace = $marketplace;
        $this->account     = $account;

        return $this;
    }

    abstract public function getMaxProductsCount();

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay_Connector_Item_Multiple_ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data'        => $this->getRequestData(),
                'listing_product_ids' => array_keys($this->listingsProducts),
                'lock_identifier'     => $this->getLockIdentifier(),
                'action_type'         => $this->getActionType(),
                'request_timeout'     => $this->getRequestTimeOut(),
            )
        );
    }

    //########################################

    public function process()
    {
        try {
            $this->getLogger()->setStatus(Ess_M2ePro_Helper_Data::STATUS_SUCCESS);

            $this->filterLockedListingsProducts();
            $this->lockListingsProducts();
            $this->validateAndFilterListingsProducts();

            if (empty($this->listingsProducts)) {
                return;
            }

            parent::process();
        } catch (Exception $exception) {
            $this->unlockListingsProducts();
            throw $exception;
        }

        $this->unlockListingsProducts();
    }

    protected function processResponser()
    {
        $this->unlockListingsProducts();
        parent::processResponser();
    }

    //########################################

    protected function getRequestData()
    {
        $data = array(
            'products' => array()
        );

        foreach ($this->listingsProducts as $listingProduct) {

            $tempData = $this->getRequestObject($listingProduct)->getData();

            foreach ($this->getRequestObject($listingProduct)->getWarningMessages() as $messageText) {

                $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    $messageText, Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
                );

                $this->getLogger()->logListingProductMessage(
                    $listingProduct, $message, Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
            }

            $data['products'][$listingProduct->getId()] = $this->buildRequestDataObject(
                $listingProduct,$tempData
            )->getData();
        }

        return $data;
    }

    //########################################

    protected function getResponserParams()
    {
        $products = array();

        foreach ($this->listingsProducts as $listingProduct) {
            $products[$listingProduct->getId()] = array(
                'request'          => $this->getRequestDataObject($listingProduct)->getData(),
                'request_metadata' => $this->getRequestObject($listingProduct)->getMetaData(),
                'configurator'     => $listingProduct->getActionConfigurator()->getData(),
            );
        }

        return array(
            'is_realtime'     => $this->isRealTime(),
            'account_id'      => $this->account->getId(),
            'action_type'     => $this->getActionType(),
            'lock_identifier' => $this->getLockIdentifier(),
            'logs_action'     => $this->getLogsAction(),
            'logs_action_id'  => $this->getLogger()->getActionId(),
            'status_changer'  => $this->params['status_changer'],
            'params'          => $this->params,
            'products'        => $products,
        );
    }

    //########################################

    protected function validateAndFilterListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $validator = $this->getValidatorObject($listingProduct);

            $validationResult = $validator->validate();

            foreach ($validator->getMessages() as $messageData) {

                $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
                $message->initFromPreparedData($messageData['text'], $messageData['type']);

                $this->getLogger()->logListingProductMessage(
                    $listingProduct,
                    $message,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
            }

            if ($validationResult) {
                continue;
            }

            $this->removeAndUnlockListingProduct($listingProduct->getId());
        }
    }

    //########################################

    protected function filterLockedListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            $lockItem = Mage::getModel('M2ePro/LockItem');
            $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$listingProduct->getId());

            if ($listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {

                // M2ePro_TRANSLATIONS
                // Another Action is being processed. Try again when the Action is completed.
                $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
                $message->initFromPreparedData(
                    'Another Action is being processed. Try again when the Action is completed.',
                    Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_ERROR
                );

                $this->getLogger()->logListingProductMessage(
                    $listingProduct,
                    $message,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                unset($this->listingsProducts[$listingProduct->getId()]);
            }
        }
    }

    protected function removeAndUnlockListingProduct($listingProductId)
    {
        $lockItem = Mage::getModel('M2ePro/LockItem');
        $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$listingProductId);
        $lockItem->remove();

        unset($this->listingsProducts[$listingProductId]);
    }

    // ########################################

    protected function lockListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {
            $lockItem = Mage::getModel('M2ePro/LockItem');
            $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$listingProduct->getId());

            $lockItem->create();
            $lockItem->makeShutdownFunction();
        }
    }

    protected function unlockListingsProducts()
    {
        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            $lockItem = Mage::getModel('M2ePro/LockItem');
            $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$listingProduct->getId());

            $lockItem->remove();
        }
    }

    //########################################

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Validator
     */
    protected function getValidatorObject(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (!isset($this->validatorsObjects[$listingProduct->getId()])) {

            /** @var $validator Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Validator */
            $validator = Mage::getModel(
                'M2ePro/Ebay_Listing_Product_Action_Type_'.$this->getOrmActionType().'_Validator'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($listingProduct);
            $validator->setConfigurator($listingProduct->getActionConfigurator());

            $this->validatorsObjects[$listingProduct->getId()] = $validator;
        }

        return $this->validatorsObjects[$listingProduct->getId()];
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request
     */
    protected function getRequestObject(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (!isset($this->requestsObjects[$listingProduct->getId()])) {
            $this->requestsObjects[$listingProduct->getId()] = $this->makeRequestObject($listingProduct);
        }
        return $this->requestsObjects[$listingProduct->getId()];
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request
     */
    protected function makeRequestObject(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request $request */

        $request = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Type_'.$this->getOrmActionType().'_Request');

        $request->setParams($this->params);
        $request->setListingProduct($listingProduct);
        $request->setConfigurator($listingProduct->getActionConfigurator());

        return $request;
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @param array $data
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function buildRequestDataObject(Ess_M2ePro_Model_Listing_Product $listingProduct, array $data)
    {
        if (!isset($this->requestsDataObjects[$listingProduct->getId()])) {
            $this->requestsDataObjects[$listingProduct->getId()] = $this->makeRequestDataObject($listingProduct,$data);
        }
        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @param array $data
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function makeRequestDataObject(Ess_M2ePro_Model_Listing_Product $listingProduct, array $data)
    {
        /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData $requestData */

        $requestData = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_RequestData');

        $requestData->setData($data);
        $requestData->setListingProduct($listingProduct);

        return $requestData;
    }

    /**
     * @param Ess_M2ePro_Model_Listing_Product $listingProduct
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function getRequestDataObject(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        return $this->requestsDataObjects[$listingProduct->getId()];
    }

    //########################################
}