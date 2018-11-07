<?php

define('MAGENTO_ROOT', getcwd());

$mageFilename = MAGENTO_ROOT . '/app/Mage.php';

require_once $mageFilename;
Mage::app();

$resultHtml = '';

try {

    $response = Mage::helper('M2ePro/Server_Request')->single(
        array('timeout' => 30), null, null, false, false
    );

} catch (Ess_M2ePro_Model_Exception_Connection $e) {

    $resultHtml .= "<h2>{$e->getMessage()}</h2><pre><br/>";
    $additionalData = $e->getAdditionalData();

    if (!empty($additionalData['curl_info'])) {
        $resultHtml .= '</pre><h2>Report</h2><pre>';
        $resultHtml .= print_r($additionalData['curl_info'], true);
        $resultHtml .= '</pre>';
    }

    if (!empty($additionalData['curl_error_number']) && !empty($additionalData['curl_error_message'])) {
        $resultHtml .= '<h2 style="color:red;">Errors</h2>';
        $resultHtml .= $additionalData['curl_error_number'] .': '
            . $additionalData['curl_error_message'] . '<br/><br/>';
    }

    echo $resultHtml;

} catch (Exception $e) {

    echo "<h2>{$e->getMessage()}</h2><pre><br/>";
}

$resultHtml .= '<h2>Response</h2><pre>';
$resultHtml .= print_r($response['body'], true);
$resultHtml .= '</pre>';

$resultHtml .= '</pre><h2>Report</h2><pre>';
$resultHtml .= print_r($response['curl_info'], true);
$resultHtml .= '</pre>';

echo $resultHtml;
exit;