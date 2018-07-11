<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Connector_Connection
{
    const API_VERSION = 1;

    /** @var Ess_M2ePro_Model_Connector_Connection_Request $request */
    private $request = NULL;

    /** @var Ess_M2ePro_Model_Connector_Connection_Response $response */
    private $response = NULL;

    private $serverBaseUrl = NULL;
    private $serverHostName = NULL;
    private $timeout = 300;

    private $tryToResendOnError = true;
    private $tryToSwitchEndpointOnError = true;

    // ########################################

    public function process()
    {
        try {

            $requestTime = Mage::helper('M2ePro')->getCurrentGmtDate();

            $result = Mage::helper('M2ePro/Server')->sendRequest($this->getBody(),
                                                                 $this->getHeaders(),
                                                                 $this->getServerBaseUrl(),
                                                                 $this->getServerHostName(),
                                                                 $this->getTimeout(),
                                                                 $this->isTryToResendOnError(),
                                                                 $this->isTryToSwitchEndpointOnError());

        } catch (Exception $exception) {
            Mage::helper('M2ePro/Client')->updateMySqlConnection();
            throw $exception;
        }

        Mage::helper('M2ePro/Client')->updateMySqlConnection();

        try {

            $this->response = Mage::getModel('M2ePro/Connector_Connection_Response');
            $this->response->initFromRawResponse($result['response']);
            $this->response->setRequestTime($requestTime);

        } catch (Exception $exception) {

            $this->isTryToSwitchEndpointOnError() && Mage::helper('M2ePro/Server')->switchEndpoint();

            $connectionErrorMessage = 'The Action was not completed because connection with M2E Pro Server was not set.
            There are several possible reasons:  temporary connection problem – please wait and try again later;
            block of outgoing connection by firewall – please, ensure that connection to s1.m2epro.com and
            s2.m2epro.com, port 443 is allowed; CURL library is not installed or it does not support HTTPS Protocol –
            please, install/update CURL library on your server and ensure it supports HTTPS Protocol.
            More information you can find <a target="_blank" href="'.
                Mage::helper('M2ePro/Module_Support')
                    ->getKnowledgebaseUrl('664870-issues-with-m2e-pro-server-connection')
                .'">here</a>';

            throw new Ess_M2ePro_Model_Exception_Connection($connectionErrorMessage, $result);
        }

        if ($this->getResponse()->getMessages()->hasSystemErrorEntity()) {

            throw new Ess_M2ePro_Model_Exception(Mage::helper('M2ePro')->__(
                "Internal Server Error(s) [%error_message%]",
                $this->getResponse()->getMessages()->getCombinedSystemErrorsString()
            ), array(), 0, !$this->getResponse()->isServerInMaintenanceMode());
        }
    }

    // ########################################

    /**
     * @param Ess_M2ePro_Model_Connector_Connection_Request $request
     * @return $this
     */
    public function setRequest(Ess_M2ePro_Model_Connector_Connection_Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Ess_M2ePro_Model_Connector_Connection_Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    // ----------------------------------------

    /**
     * @return Ess_M2ePro_Model_Connector_Connection_Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    // ########################################

    public function setServerBaseUrl($value)
    {
        $this->serverBaseUrl = $value;
        return $this;
    }

    public function getServerBaseUrl()
    {
        return $this->serverBaseUrl;
    }

    // ----------------------------------------

    public function setServerHostName($value)
    {
        $this->serverHostName = $value;
        return $this;
    }

    public function getServerHostName()
    {
        return $this->serverHostName;
    }

    // ----------------------------------------

    public function setTimeout($value)
    {
        $this->timeout = (int)$value;
        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    // ----------------------------------------

    /**
     * @param boolean $tryToResendOnError
     * @return $this
     */
    public function setTryToResendOnError($tryToResendOnError)
    {
        $this->tryToResendOnError = $tryToResendOnError;
        return $this;
    }

     /**
     * @return boolean
     */
    public function isTryToResendOnError()
    {
        return $this->tryToResendOnError;
    }

    // ----------------------------------------

    /**
     * @param boolean $tryToSwitchEndpointOnError
     * @return $this
     */
    public function setTryToSwitchEndpointOnError($tryToSwitchEndpointOnError)
    {
        $this->tryToSwitchEndpointOnError = $tryToSwitchEndpointOnError;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isTryToSwitchEndpointOnError()
    {
        return $this->tryToSwitchEndpointOnError;
    }

    // ----------------------------------------

    public function getHeaders()
    {
        $command = $this->getRequest()->getCommand();

        return array(
            'M2EPRO-API-VERSION: '.self::API_VERSION,
            'M2EPRO-API-COMPONENT: '.$this->getRequest()->getComponent(),
            'M2EPRO-API-COMPONENT-VERSION: '.$this->getRequest()->getComponentVersion(),
            'M2EPRO-API-COMMAND: /'.$command[0] .'/'.$command[1].'/'.$command[2].'/'
        );
    }

    public function getBody()
    {
        $request = Mage::helper('M2ePro')->jsonEncode($this->getRequest()->getInfo());
        $data    = Mage::helper('M2ePro')->jsonEncode($this->getRequest()->getData());

        return array(
            'api_version' => self::API_VERSION,
            'request'     => $request,
            'data'        => $data
        );
    }

    // ########################################
}