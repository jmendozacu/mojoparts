<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

abstract class Ess_M2ePro_Model_Ebay_Connector_Item_Single_Requester
    extends Ess_M2ePro_Model_Ebay_Connector_Item_Requester
{
    /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */
    protected $listingProduct = NULL;

    /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Validator $validatorObject */
    protected $validatorObject = NULL;

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request
     */
    protected $requestObject = NULL;

    /**
     * @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected $requestDataObject = NULL;

    //########################################

    public function setListingProduct(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (!is_null($listingProduct->getActionConfigurator())) {
            $actionConfigurator = $listingProduct->getActionConfigurator();
        } else {
            $actionConfigurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');
        }

        $this->listingProduct = $listingProduct->loadInstance($listingProduct->getId());
        $this->listingProduct->setActionConfigurator($actionConfigurator);

        $this->marketplace = $this->listingProduct->getMarketplace();
        $this->account     = $this->listingProduct->getAccount();
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Ebay_Connector_Item_Single_ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            array(
                'request_data'        => $this->getRequestData(),
                'listing_product_id'  => $this->listingProduct->getId(),
                'lock_identifier'     => $this->getLockIdentifier(),
                'action_type'         => $this->getActionType(),
                'request_timeout'     => $this->getRequestTimeout(),
            )
        );
    }

    //########################################

    public function getRequestTimeout()
    {
        $requestDataObject = $this->getRequestDataObject();
        $requestData = $requestDataObject->getData();

        if ($requestData['is_eps_ebay_images_mode'] === false ||
            (is_null($requestData['is_eps_ebay_images_mode']) &&
                $requestData['upload_images_mode'] ==
                Ess_M2ePro_Model_Ebay_Listing_Product_Action_Request_Description::UPLOAD_IMAGES_MODE_SELF)) {
            return parent::getRequestTimeout();
        }

        $imagesTimeout = self::TIMEOUT_INCREMENT_FOR_ONE_IMAGE * $requestDataObject->getTotalImagesCount();
        return parent::getRequestTimeout() + $imagesTimeout;
    }

    //########################################

    public function process()
    {
        try {
            $this->getLogger()->setStatus(Ess_M2ePro_Helper_Data::STATUS_SUCCESS);

            if ($this->isListingProductLocked()) {
                return;
            }

            $this->lockListingProduct();
            if (!$this->validateListingProduct()) {
                return;
            }

            parent::process();
        } catch (Exception $exception) {
            $this->unlockListingProduct();
            throw $exception;
        }

        $this->unlockListingProduct();
    }

    protected function processResponser()
    {
        $this->unlockListingProduct();
        parent::processResponser();
    }

    //########################################

    protected function getRequestData()
    {
        $tempData = $this->getRequestObject()->getData();

        foreach ($this->getRequestObject()->getWarningMessages() as $messageText) {

            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $messageText, Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct, $message, Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );
        }

        return $this->buildRequestDataObject($tempData)->getData();
    }

    //########################################

    protected function getResponserParams()
    {
        $product = array(
            'request'          => $this->getRequestDataObject()->getData(),
            'request_metadata' => $this->getRequestObject()->getMetaData(),
            'configurator'     => $this->listingProduct->getActionConfigurator()->getData(),
            'id'               => $this->listingProduct->getId(),
        );

        return array(
            'is_realtime'     => $this->isRealTime(),
            'account_id'      => $this->account->getId(),
            'action_type'     => $this->getActionType(),
            'lock_identifier' => $this->getLockIdentifier(),
            'logs_action'     => $this->getLogsAction(),
            'logs_action_id'  => $this->getLogger()->getActionId(),
            'status_changer'  => $this->params['status_changer'],
            'params'          => $this->params,
            'product'         => $product,
        );
    }

    //########################################

    protected function validateListingProduct()
    {
        $validator = $this->getValidatorObject();

        $validationResult = $validator->validate();

        foreach ($validator->getMessages() as $messageData) {

            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );
        }

        if ($validationResult) {
            return true;
        }

        $this->unlockListingProduct();

        return false;
    }

    //########################################

    protected function isListingProductLocked()
    {
        $lockItem = Mage::getModel('M2ePro/LockItem');
        $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        if ($this->listingProduct->isSetProcessingLock('in_action') || $lockItem->isExist()) {

            // M2ePro_TRANSLATIONS
            // Another Action is being processed. Try again when the Action is completed.
            $message = Mage::getModel('M2ePro/Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Another Action is being processed. Try again when the Action is completed.',
                Ess_M2ePro_Model_Connector_Connection_Response_Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            return true;
        }

        return false;
    }

    // ########################################

    protected function lockListingProduct()
    {
        $lockItem = Mage::getModel('M2ePro/LockItem');
        $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        $lockItem->create();
        $lockItem->makeShutdownFunction();
    }

    protected function unlockListingProduct()
    {
        $lockItem = Mage::getModel('M2ePro/LockItem');
        $lockItem->setNick(Ess_M2ePro_Helper_Component_Ebay::NICK.'_listing_product_'.$this->listingProduct->getId());

        $lockItem->remove();
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Validator
     */
    protected function getValidatorObject()
    {
        if (empty($this->validatorObject)) {

            /** @var $validator Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Validator */
            $validator = Mage::getModel(
                'M2ePro/Ebay_Listing_Product_Action_Type_'.$this->getOrmActionType().'_Validator'
            );

            $validator->setParams($this->params);
            $validator->setListingProduct($this->listingProduct);
            $validator->setConfigurator($this->listingProduct->getActionConfigurator());

            $this->validatorObject = $validator;
        }

        return $this->validatorObject;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request
     */
    protected function getRequestObject()
    {
        if (empty($this->requestObject)) {
            $this->requestObject = $this->makeRequestObject();
        }

        return $this->requestObject;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request
     */
    protected function makeRequestObject()
    {
        /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request $request */

        $request = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Type_'.$this->getOrmActionType().'_Request');

        $request->setParams($this->params);
        $request->setListingProduct($this->listingProduct);
        $request->setConfigurator($this->listingProduct->getActionConfigurator());
        $request->setValidatorsData($this->getValidatorObject($this->listingProduct)->getData());

        return $request;
    }

    /**
     * @param array $data
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function buildRequestDataObject(array $data)
    {
        if (empty($this->requestDataObject)) {
            $this->requestDataObject = $this->makeRequestDataObject($data);
        }

        return $this->requestDataObject;
    }

    /**
     * @param array $data
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function makeRequestDataObject(array $data)
    {
        $requestData = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_RequestData');

        $requestData->setData($data);
        $requestData->setListingProduct($this->listingProduct);

        return $requestData;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Listing_Product_Action_RequestData
     */
    protected function getRequestDataObject()
    {
        return $this->requestDataObject;
    }

    //########################################
}