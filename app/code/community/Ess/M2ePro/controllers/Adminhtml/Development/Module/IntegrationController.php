<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Adminhtml_Development_Module_IntegrationController
    extends Ess_M2ePro_Controller_Adminhtml_Development_CommandController
{
    //########################################

    /**
     * @title "Revise Total"
     * @description "Full Force Revise"
     */
    public function reviseTotalAction()
    {
        $html = '';
        foreach (Mage::helper('M2ePro/Component')->getActiveComponents() as $component) {

            $reviseAllStartDate = Mage::helper('M2ePro/Module')->getSynchronizationConfig()->getGroupValue(
                "/{$component}/templates/synchronization/revise/total/", 'start_date'
            );

            $reviseAllEndDate = Mage::helper('M2ePro/Module')->getSynchronizationConfig()->getGroupValue(
                "/{$component}/templates/synchronization/revise/total/", 'end_date'
            );

            $reviseAllInProcessingState = !is_null(
                Mage::helper('M2ePro/Module')->getSynchronizationConfig()->getGroupValue(
                    "/{$component}/templates/synchronization/revise/total/", 'last_listing_product_id'
                )
            );

            $urlHelper = Mage::helper('adminhtml');

            $runNowUrl = $urlHelper->getUrl('*/*/processReviseTotal', array('component' => $component));
            $resetUrl = $urlHelper->getUrl('*/*/resetReviseTotal', array('component' => $component));

            $html .= <<<HTML
<div>
    <span style="display:inline-block; width: 100px;">{$component}</span>
    <span style="display:inline-block; width: 150px;">
        <button onclick="window.location='{$runNowUrl}'">turn on</button>
        <button onclick="window.location='{$resetUrl}'">stop</button>
    </span>
    <span id="{$component}_start_date" style="color: indianred; display: none;">
        Started at - {$reviseAllStartDate}
    </span>
    <span id="{$component}_end_date" style="color: green; display: none;">
        Finished at - {$reviseAllEndDate}
    </span>
</div>

HTML;
            $html.= "<script type=\"text/javascript\">";
            if ($reviseAllInProcessingState) {
                $html .= "document.getElementById('{$component}_start_date').style.display = 'inline-block';";
            } else {

                if ($reviseAllEndDate) {
                    $html .= "document.getElementById('{$component}_end_date').style.display = 'inline-block';";
                }
            }
            $html.= "</script>";
        }

        return $this->getResponse()->setBody($html);
    }

    /**
     * @title "Process Revise Total for Component"
     * @hidden
    */
    public function processReviseTotalAction()
    {
        $component = $this->getRequest()->getParam('component', false);

        if (!$component) {
            $this->_getSession()->addError('Component is not presented.');
            $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageModuleTabUrl());
        }

        Mage::helper('M2ePro/Module')->getSynchronizationConfig()->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/", 'start_date',
            Mage::helper('M2ePro')->getCurrentGmtDate()
        );

        Mage::helper('M2ePro/Module')->getSynchronizationConfig()->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/", 'end_date', null
        );

        Mage::helper('M2ePro/Module')->getSynchronizationConfig()->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/", 'last_listing_product_id', 0
        );

        $this->_redirect('*/*/reviseTotal');
    }

    /**
     * @title "Reset Revise Total for Component"
     * @hidden
     */
    public function resetReviseTotalAction()
    {
        $component = $this->getRequest()->getParam('component', false);

        if (!$component) {
            $this->_getSession()->addError('Component is not presented.');
            $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageModuleTabUrl());
        }

        Mage::helper('M2ePro/Module')->getSynchronizationConfig()->setGroupValue(
            "/{$component}/templates/revise/synchronization/total/", 'last_listing_product_id', null
        );

        $this->_redirect('*/*/reviseTotal');
    }

    /**
     * @title "Print Request Data"
     * @description "Print [List/Relist/Revise] Request Data"
     */
    public function getRequestDataAction()
    {
        if ($this->getRequest()->getParam('print')) {

            /** @var Ess_M2ePro_Model_Listing_Product $lp */
            $listingProductId = $this->getRequest()->getParam('listing_product_id');
            $lp = Mage::helper('M2ePro/Component')->getUnknownObject('Listing_Product', $listingProductId);

            $componentMode    = $lp->getComponentMode();
            $requestType      = $this->getRequest()->getParam('request_type');

            if ($componentMode == 'ebay') {

                /** @var Ess_M2ePro_Model_Ebay_Listing_Product $elp */
                $elp = $lp->getChildObject();

                /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Configurator $configurator */
                $configurator = Mage::getModel('M2ePro/Ebay_Listing_Product_Action_Configurator');

                /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Action_Type_Request $request */
                $request = Mage::getModel("M2ePro/Ebay_Listing_Product_Action_Type_{$requestType}_Request");
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'Revise') {

                    $outOfStockControlCurrentState  = $elp->getOutOfStockControl();
                    $outOfStockControlTemplateState = $elp->getEbaySellingFormatTemplate()->getOutOfStockControl();

                    if (!$outOfStockControlCurrentState && $outOfStockControlTemplateState) {
                        $outOfStockControlCurrentState = true;
                    }

                    $outOfStockControlResult = $outOfStockControlCurrentState ||
                                               $elp->getEbayAccount()->getOutOfStockControl();

                    $request->setParams(array(
                        'out_of_stock_control_current_state' => $outOfStockControlCurrentState,
                        'out_of_stock_control_result'        => $outOfStockControlResult,
                    ));
                }

                return $this->getResponse()->setBody('<pre>'.print_r($request->getData(), true).'</pre>');
            }

            if ($componentMode == 'amazon') {

                /** @var Ess_M2ePro_Model_Amazon_Listing_Product $alp */
                $alp = $lp->getChildObject();

                /** @var Ess_M2ePro_Model_Amazon_Listing_Product_Action_Configurator $configurator */
                $configurator = Mage::getModel('M2ePro/Amazon_Listing_Product_Action_Configurator');

                /** @var Ess_M2ePro_Model_Amazon_Listing_Product_Action_Type_Request $request */
                $request = Mage::getModel("M2ePro/Amazon_Listing_Product_Action_Type_{$requestType}_Request");
                $request->setParams(array());
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'List') {
                    $request->setCachedData(array(
                                                    'sku'        => 'placeholder',
                                                    'general_id' => 'placeholder',
                                                    'list_type'  => 'placeholder'
                                                ));
                }

                return $this->getResponse()->setBody('<pre>'.print_r($request->getData(), true).'</pre>');
            }

            return;
        }

        $formKey = Mage::getSingleton('core/session')->getFormKey();
        $actionUrl = Mage::helper('adminhtml')->getUrl('*/*/*');

        return $this->getResponse()->setBody(<<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Listing Product ID: </label>
        <input name="listing_product_id" style="width: 200px;" required>
    </div>

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Request Type: </label>
        <select name="request_type" style="width: 200px;" required>
            <option style="display: none;"></option>
            <option value="List">List</option>
            <option value="Relist">Relist</option>
            <option value="Revise">Revise</option>
        </select>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Show</button>
    </div>

</form>
HTML
        );
    }

    /**
     * @title "Print Inspector Data"
     * @description "Print Inspector Data"
     * @new_line
     */
    public function getInspectorDataAction()
    {
        if ($this->getRequest()->getParam('print')) {

            /** @var Ess_M2ePro_Model_Listing_Product $lp */
            $listingProductId = $this->getRequest()->getParam('listing_product_id');

            if ($this->getRequest()->getParam('component_mode') == 'ebay') {

                /** @var Ess_M2ePro_Model_Ebay_Listing_Product $elp */
                $lp = Mage::helper('M2ePro/Component_Ebay')->getObject('Listing_Product', $listingProductId);
                $elp = $lp->getChildObject();

                $insp = Mage::getModel('M2ePro/Ebay_Synchronization_Templates_Synchronization_Inspector');

                $resultHtml = '';

                $resultHtml .= '<pre>isMeetListRequirements: ' .$insp->isMeetListRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetRelistRequirements: ' .$insp->isMeetRelistRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetStopRequirements: ' .$insp->isMeetStopRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetReviseGeneralRequirements: '.$insp->isMeetReviseGeneralRequirements($lp)
                               .'<br>';
                $resultHtml .= '<pre>isMeetRevisePriceRequirements: ' .$insp->isMeetRevisePriceRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetReviseQtyRequirements: ' .$insp->isMeetReviseQtyRequirements($lp).'<br>';

                $resultHtml .= '<br>';
                $resultHtml .= '<pre>isSetCategoryTemplate: ' .$elp->isSetCategoryTemplate().'<br>';
                $resultHtml .= '<pre>isInAction: ' .$lp->isSetProcessingLock('in_action'). '<br>';

                $resultHtml .= '<pre>isStatusEnabled: ' .($lp->getMagentoProduct()->isStatusEnabled()).'<br>';
                $resultHtml .= '<pre>isStockAvailability: ' .($lp->getMagentoProduct()->isStockAvailability()).'<br>';

                $resultHtml .= '<pre>onlineQty: '.($elp->getOnlineQty() - $elp->getOnlineQtySold()).'<br>';

                $totalQty = 0;

                if (!$elp->isVariationsReady()) {
                    $totalQty = $elp->getQty();
                } else {
                    foreach ($lp->getVariations(true) as $variation) {
                        /** @var Ess_M2ePro_Model_Ebay_Listing_Product_Variation $ebayVariation */
                        $ebayVariation = $variation->getChildObject();
                        $totalQty += $ebayVariation->getQty();
                    }
                }

                $resultHtml .= '<pre>productQty: ' .$totalQty. '<br>';

                $resultHtml .= '<br>';
                $resultHtml .= '<pre>onlineCurrentPrice: '.($elp->getOnlineCurrentPrice()).'<br>';
                $resultHtml .= '<pre>currentPrice: '.($elp->getFixedPrice()).'<br>';

                $resultHtml .= '<br>';
                $resultHtml .= '<pre>onlineStartPrice: '.($elp->getOnlineStartPrice()).'<br>';
                $resultHtml .= '<pre>startPrice: '.($elp->getStartPrice()).'<br>';

                $resultHtml .= '<br>';
                $resultHtml .= '<pre>onlineReservePrice: '.($elp->getOnlineReservePrice()).'<br>';
                $resultHtml .= '<pre>reservePrice: '.($elp->getReservePrice()).'<br>';

                $resultHtml .= '<br>';
                $resultHtml .= '<pre>onlineBuyItNowPrice: '.($elp->getOnlineBuyItNowPrice()).'<br>';
                $resultHtml .= '<pre>buyItNowPrice: '.($elp->getBuyItNowPrice()).'<br>';

                return $this->getResponse()->setBody($resultHtml);
            }

            if ($this->getRequest()->getParam('component_mode') == 'amazon') {

                /** @var Ess_M2ePro_Model_Amazon_Listing_Product $alp */
                $lp = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product', $listingProductId);

                $insp = Mage::getModel('M2ePro/Amazon_Synchronization_Templates_Synchronization_Inspector');

                $resultHtml = '';

                $resultHtml .= '<pre>isMeetListRequirements: '.$insp->isMeetListRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetRelistRequirements: '.$insp->isMeetRelistRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetStopRequirements: '.$insp->isMeetStopRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetReviseGeneralRequirements: '.$insp->isMeetReviseGeneralRequirements($lp)
                               .'<br>';
                $resultHtml .= '<pre>isMeetReviseRegularPriceRequirements: '
                    .$insp->isMeetReviseRegularPriceRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetReviseBusinessPriceRequirements: '
                    .$insp->isMeetReviseBusinessPriceRequirements($lp).'<br>';
                $resultHtml .= '<pre>isMeetReviseQtyRequirements: '.$insp->isMeetReviseQtyRequirements($lp).'<br>';

                $resultHtml .= '<pre>isStatusEnabled: '.($lp->getMagentoProduct()->isStatusEnabled()).'<br>';
                $resultHtml .= '<pre>isStockAvailability: '.($lp->getMagentoProduct()->isStockAvailability()).'<br>';

                return $this->getResponse()->setBody($resultHtml);
            }

            return;
        }

        $formKey = Mage::getSingleton('core/session')->getFormKey();
        $actionUrl = Mage::helper('adminhtml')->getUrl('*/*/*');

        return $this->getResponse()->setBody(<<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Listing Product ID: </label>
        <input name="listing_product_id" style="width: 200px;" required>
    </div>

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Component: </label>
        <select name="component_mode" style="width: 200px;" required>
            <option style="display: none;"></option>
            <option value="ebay">eBay</option>
            <option value="amazon">Amazon</option>
        </select>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Show</button>
    </div>

</form>
HTML
        );
    }

    //########################################

    /**
     * @title "Build Order Quote"
     * @description "Print Order Quote Data"
     * @new_line
     */
    public function getPrintOrderQuoteDataAction()
    {
        if ($this->getRequest()->getParam('print')) {

            /** @var Ess_M2ePro_Model_Order $order */
            $orderId = $this->getRequest()->getParam('order_id');
            $order =  Mage::helper('M2ePro/Component')->getUnknownObject('Order', $orderId);

            if (!$order->getId()) {

                $this->_getSession()->addError('Unable to load order instance.');
                $this->_redirectUrl(Mage::helper('M2ePro/View_Development')->getPageModuleTabUrl());
                return;
            }

            // Store must be initialized before products
            // ---------------------------------------
            $order->associateWithStore();
            $order->associateItemsWithProducts();
            // ---------------------------------------

            $proxy = $order->getProxy()->setStore($order->getStore());

            $magentoQuote = Mage::getModel('M2ePro/Magento_Quote', $proxy);
            $magentoQuote->buildQuote();
            $magentoQuote->getQuote()->setIsActive(false)->save();

            $shippingAddressData = $magentoQuote->getQuote()->getShippingAddress()->getData();
            unset(
                $shippingAddressData['cached_items_all'],
                $shippingAddressData['cached_items_nominal'],
                $shippingAddressData['cached_items_nonnominal']
            );
            $billingAddressData  = $magentoQuote->getQuote()->getBillingAddress()->getData();
            unset(
                $billingAddressData['cached_items_all'],
                $billingAddressData['cached_items_nominal'],
                $billingAddressData['cached_items_nonnominal']
            );

            $quote = $magentoQuote->getQuote();

            $resultHtml = '';

            $resultHtml .= '<pre><b>Grand Total:</b> ' .$quote->getGrandTotal(). '<br>';
            $resultHtml .= '<pre><b>Shipping Amount:</b> ' .$quote->getShippingAddress()->getShippingAmount(). '<br>';

            $resultHtml .= '<pre><b>Quote Data:</b> ' .print_r($quote->getData(), true). '<br>';
            $resultHtml .= '<pre><b>Shipping Address Data:</b> ' .print_r($shippingAddressData, true). '<br>';
            $resultHtml .= '<pre><b>Billing Address Data:</b> ' .print_r($billingAddressData, true). '<br>';

            return $this->getResponse()->setBody($resultHtml);
        }

        $formKey = Mage::getSingleton('core/session')->getFormKey();
        $actionUrl = Mage::helper('adminhtml')->getUrl('*/*/*');

        return $this->getResponse()->setBody(<<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Order ID: </label>
        <input name="order_id" style="width: 200px;" required>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Build</button>
    </div>

</form>
HTML
        );
    }

    //########################################

    /**
     * @title "Search Troubles With Parallel Execution"
     * @description "By operation history table"
     * @new_line
     */
    public function searchTroublesWithParallelExecutionAction()
    {
        if (!$this->getRequest()->getParam('print')) {

            $formKey = Mage::getSingleton('core/session')->getFormKey();
            $actionUrl = Mage::helper('adminhtml')->getUrl('*/*/*');

            $collection = Mage::getModel('M2ePro/OperationHistory')->getCollection();
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns(array('nick'));
            $collection->getSelect()->order('nick ASC');
            $collection->getSelect()->distinct();

            $optionsHtml = '';
            foreach ($collection->getItems() as $item) {
                $optionsHtml .= <<<HTML
<option value="{$item->getData('nick')}">{$item->getData('nick')}</option>
HTML;
            }

            $html = <<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Search by nick: </label>
        <select name="nick" style="width: 200px;" required>
            <option value="" style="display: none;"></option>
            {$optionsHtml}
        </select>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Search</button>
    </div>

</form>
HTML;
            return $this->getResponse()->setBody($html);
        }

        $searchByNick = (string)$this->getRequest()->getParam('nick');

        $collection = Mage::getModel('M2ePro/OperationHistory')->getCollection();
        $collection->addFieldToFilter('nick', $searchByNick);
        $collection->getSelect()->order('id ASC');

        $results = array();
        $prevItem = NULL;

        foreach ($collection->getItems() as $item) {
            /** @var Ess_M2ePro_Model_OperationHistory $item */
            /** @var Ess_M2ePro_Model_OperationHistory $prevItem */

            if (is_null($item->getData('end_date'))) {
                continue;
            }

            if (is_null($prevItem)) {

                $prevItem = $item;
                continue;
            }

            $prevEnd   = new DateTime($prevItem->getData('end_date'), new \DateTimeZone('UTC'));
            $currStart = new DateTime($item->getData('start_date'), new \DateTimeZone('UTC'));

            if ($currStart->getTimeStamp() < $prevEnd->getTimeStamp()) {

                $results[$item->getId().'##'.$prevItem->getId()] = array(
                    'curr' => array(
                        'id'    => $item->getId(),
                        'start' => $item->getData('start_date'),
                        'end'   => $item->getData('end_date')
                    ),
                    'prev' => array(
                        'id'    => $prevItem->getId(),
                        'start' => $prevItem->getData('start_date'),
                        'end'   => $prevItem->getData('end_date')
                    ),
                );
            }

            $prevItem = $item;
        }

        if (count($results) <= 0) {
            return $this->getResponse()->setBody($this->getEmptyResultsHtml(
                'There are no troubles with a parallel work of crons.'
            ));
        }

        $tableContent = <<<HTML
<tr>
    <th>Num</th>
    <th>Type</th>
    <th>ID</th>
    <th>Started</th>
    <th>Finished</th>
    <th>Total</th>
    <th>Delay</th>
</tr>
HTML;
        $index = 1;
        $results = array_reverse($results, true);

        foreach ($results as $key => $row) {

            $currStart = new \DateTime($row['curr']['start'], new \DateTimeZone('UTC'));
            $currEnd   = new \DateTime($row['curr']['end'], new \DateTimeZone('UTC'));
            $currTime = $currEnd->diff($currStart);
            $currTime = $currTime->format('%H:%I:%S');

            $currUrlUp = $this->getUrl(
                '*/adminhtml_development_database/showOperationHistoryExecutionTreeUp',
                array('operation_history_id' => $row['curr']['id'])
            );
            $currUrlDown = $this->getUrl(
                '*/adminhtml_development_database/showOperationHistoryExecutionTreeDown',
                array('operation_history_id' => $row['curr']['id'])
            );

            $prevStart = new \DateTime($row['prev']['start'], new \DateTimeZone('UTC'));
            $prevEnd   = new \DateTime($row['prev']['end'], new \DateTimeZone('UTC'));
            $prevTime = $prevEnd->diff($prevStart);
            $prevTime = $prevTime->format('%H:%I:%S');

            $prevUrlUp = $this->getUrl(
                '*/adminhtml_development_database/showOperationHistoryExecutionTreeUp',
                array('operation_history_id' => $row['prev']['id'])
            );
            $prevUrlDown = $this->getUrl(
                '*/adminhtml_development_database/showOperationHistoryExecutionTreeDown',
                array('operation_history_id' => $row['prev']['id'])
            );

            $delayTime = $currStart->diff($prevStart);
            $delayTime = $delayTime->format('%H:%I:%S');

            $tableContent .= <<<HTML
<tr>
    <td rowspan="2">{$index}</td>
    <td>Previous</td>
    <td>
        {$row['prev']['id']}&nbsp;
        <a style="color: green;" href="{$prevUrlUp}" target="_blank"><span>&uarr;</span></a>&nbsp;
        <a style="color: green;" href="{$prevUrlDown}" target="_blank"><span>&darr;</span></a>
    </td>
    <td><span>{$row['prev']['start']}</span></td>
    <td><span>{$row['prev']['end']}</span></td>
    <td><span>{$prevTime}</span></td>
    <td rowspan="2"><span>{$delayTime}</span>
</tr>
<tr>
    <td>Current</td>
    <td>
        {$row['curr']['id']}&nbsp;
        <a style="color: green;" href="{$currUrlUp}" target="_blank"><span>&uarr;</span></a>&nbsp;&nbsp;
        <a style="color: green;" href="{$currUrlDown}" target="_blank"><span>&darr;</span></a>
        </td>
    <td><span>{$row['curr']['start']}</span></td>
    <td><span>{$row['curr']['end']}</span></td>
    <td><span>{$currTime}</span></td>
</tr>
HTML;
            $index++;
        }

        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Parallel work of [{$searchByNick}]
            <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
        </h2>
        <br/>
        <table class="grid" cellpadding="0" cellspacing="0">
            {$tableContent}
        </table>
    </body>
</html>
HTML;
        return $this->getResponse()->setBody(str_replace('#count#', count($results), $html));
    }

    //########################################

    protected function getEmptyResultsHtml($messageText)
    {
        $backUrl = Mage::helper('M2ePro/View_Development')->getPageModuleTabUrl();

        return <<<HTML
    <h2 style="margin: 20px 0 0 10px">
        {$messageText} <span style="color: grey; font-size: 10px;">
        <a href="{$backUrl}">[back]</a>
    </h2>
HTML;
    }

    //########################################
}