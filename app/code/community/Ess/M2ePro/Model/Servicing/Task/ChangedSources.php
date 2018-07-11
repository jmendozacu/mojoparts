<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Servicing_Task_ChangedSources extends Ess_M2ePro_Model_Servicing_Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'changed_sources';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $responseData = array();

        try {

            $dispatcherObject = Mage::getModel('M2ePro/M2ePro_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('files','get','info');
            $dispatcherObject->process($connectorObj);

            $responseData = $connectorObj->getResponseData();

        } catch (Exception $e) {
            Mage::helper('M2ePro/Module_Exception')->process($e);
        }

        if (count($responseData) <= 0) {
            return array();
        }

        $requestData = array();

        foreach ($responseData['files_info'] as $info) {

            if (!in_array($info['path'], $this->getImportantFiles())) {
                continue;
            }

            $absolutePath = Mage::getBaseDir() .'/'. $info['path'];
            if (!is_file($absolutePath)) {

                $requestData[] = array(
                    'path'    => $info['path'],
                    'hash'    => NULL,
                    'content' => NULL,
                );
                continue;
            }

            $content = trim(file_get_contents($absolutePath));
            $content = str_replace(array("\r\n","\n\r",PHP_EOL), chr(10), $content);
            $contentHash = md5($content);

            if ($contentHash != $info['hash']) {

                $requestData[] = array(
                    'path'    => $info['path'],
                    'hash'    => $contentHash,
                    'content' => $content,
                );
            }
        }

        return $requestData;
    }

    //########################################

    public function processResponseData(array $data) {}

    //########################################

    //todo Ruslan is going to change this list
    private function getImportantFiles()
    {
        return array(
            'app/code/community/Ess/M2ePro/Model/Ebay/Actions/Processor.php',
            'app/code/community/Ess/M2ePro/Model/Amazon/Actions/Processor.php'
        );
    }

    //########################################
}