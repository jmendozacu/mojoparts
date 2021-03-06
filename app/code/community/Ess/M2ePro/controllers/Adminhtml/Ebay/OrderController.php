<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Adminhtml_Ebay_OrderController extends Ess_M2ePro_Controller_Adminhtml_Ebay_MainController
{
    //########################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_title(Mage::helper('M2ePro')->__('Sales'))
             ->_title(Mage::helper('M2ePro')->__('Orders'));

        $this->getLayout()->getBlock('head')
             ->addJs('M2ePro/Plugin/ProgressBar.js')
             ->addCss('M2ePro/css/Plugin/ProgressBar.css')
             ->addJs('M2ePro/Order/Debug.js')
             ->addJs('M2ePro/Order/Handler.js')
             ->addJs('M2ePro/Order/Edit/ItemHandler.js')
             ->addJs('M2ePro/Order/Edit/ShippingAddressHandler.js')
             ->addJs('M2ePro/Ebay/Order/MigrationToV611Handler.js');

        $this->setPageHelpLink(NULL, NULL, "x/RQAJAQ");

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro_ebay/orders');
    }

    //########################################

    public function indexAction()
    {
        $this->_initAction();
        $this->_initPopUp();
        $this->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_ebay_order'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //########################################

    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        Mage::helper('M2ePro/Data_Global')->setValue('temp_data', $order);

        $this->_initAction();

        $this->setPageHelpLink(NULL, NULL, "x/wYwVAQ");

        $this->_initPopUp();

        $this->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_view'))
             ->renderLayout();
    }

    //########################################

    public function orderItemGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        Mage::helper('M2ePro/Data_Global')->setValue('temp_data', $order);

        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_view_item')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //########################################

    public function editShippingAddressAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        Mage::helper('M2ePro/Data_Global')->setValue('temp_data', $order);

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_edit_shippingAddress'))
             ->renderLayout();
    }

    public function saveShippingAddressAction()
    {
        if (!$post = $this->getRequest()->getPost()) {
            return $this->_redirect('*/adminhtml_ebay_order/index');
        }

        $id = $this->getRequest()->getParam('order_id');

        /** @var Ess_M2ePro_Model_Order $order */
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        $data = array();
        $keys = array(
            'buyer_name',
            'buyer_email'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $order->setData('buyer_name', $data['buyer_name']);
        $order->setData('buyer_email', $data['buyer_email']);

        $data = array();
        $keys = array(
            'street',
            'city',
            'country_code',
            'state',
            'postal_code',
            'phone'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if (isset($data['street']) && is_array($data['street'])) {
            $data['street'] = array_filter($data['street']);
        }

        $shippingDetails = $order->getChildObject()->getShippingDetails();
        $shippingDetails['address'] = $data;

        $order->setData('shipping_details', Mage::helper('M2ePro')->jsonEncode($shippingDetails));
        $order->save();

        $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Order address has been updated.'));

        $this->_redirect('*/adminhtml_ebay_order/view', array('id' => $order->getId()));
    }

    //########################################

    private function processConnector($action, array $params = array())
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Please select Order(s).'));
            return false;
        }

        return Mage::getModel('M2ePro/Ebay_Connector_Order_Dispatcher')->process($action, $ids, $params);
    }

    // ---------------------------------------

    public function updatePaymentStatusAction()
    {
        if ($this->processConnector(Ess_M2ePro_Model_Ebay_Connector_Order_Dispatcher::ACTION_PAY)) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Payment status for selected eBay Order(s) was updated to Paid.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Payment status for selected eBay Order(s) was not updated.')
            );
        }

        return $this->_redirectUrl($this->_getRefererUrl());
    }

    public function updateShippingStatusAction()
    {
        if ($this->processConnector(Ess_M2ePro_Model_Ebay_Connector_Order_Dispatcher::ACTION_SHIP)) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Shipping status for selected eBay Order(s) was updated to Shipped.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Shipping status for selected eBay Order(s) was not updated.')
            );
        }

        return $this->_redirectUrl($this->_getRefererUrl());
    }

    // ---------------------------------------

    public function markAsReadyForPickupAction()
    {
        if ($this->sendInStorePickupNotifications('ready_for_pickup')) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Orders were successfully marked as Ready For Pickup.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Orders were not marked as Ready For Pickup.')
            );
        }

        return $this->_redirectUrl($this->_getRefererUrl());
    }

    public function markAsPickedUpAction()
    {
        if ($this->sendInStorePickupNotifications('picked_up')) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Orders were successfully marked as Picked Up.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Orders were not marked as Picked Up.')
            );
        }

        return $this->_redirectUrl($this->_getRefererUrl());
    }

    public function markAsCancelledAction()
    {
        if ($this->sendInStorePickupNotifications('cancelled')) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Orders were successfully marked as Cancelled.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Orders were not marked as Cancelled.')
            );
        }

        return $this->_redirectUrl($this->_getRefererUrl());
    }

    private function sendInStorePickupNotifications($type)
    {
        $ids = $this->getRequestIds();

        /** @var Ess_M2ePro_Model_Mysql4_Order_Collection $orderCollection */
        $orderCollection = Mage::helper('M2ePro/Component_Ebay')->getCollection('Order');
        $orderCollection->addFieldToFilter('id', $ids);

        /** @var Ess_M2ePro_Model_Order[] $orders */
        $orders = $orderCollection->getItems();

        $successMessage = '';
        switch ($type) {
            case 'ready_for_pickup':
                $successMessage = Mage::helper('M2ePro')->__('Order was successfully marked as Ready For Pickup');
                break;

            case 'picked_up':
                $successMessage = Mage::helper('M2ePro')->__('Order was successfully marked as Picked Up');
                break;

            case 'cancelled':
                $successMessage = Mage::helper('M2ePro')->__('Order was successfully marked as Cancelled');
                break;
        }

        foreach ($orders as $order) {
            /** @var Ess_M2ePro_Model_Ebay_Order $ebayOrder */
            $ebayOrder = $order->getChildObject();

            $dispatcher = Mage::getModel('M2ePro/Ebay_Connector_Dispatcher');
            $connector = $dispatcher->getVirtualConnector(
                'store', 'update', 'order',
                array('order_id' => $ebayOrder->getEbayOrderId(), 'type' => $type),
                NULL, NULL, $order->getAccount()
            );

            try {
                $dispatcher->process($connector);
            } catch (Exception $exception) {
                return false;
            }

            $order->addSuccessLog($successMessage);
        }

        return true;
    }

    //########################################

    public function createMagentoOrderAction()
    {
        $id = $this->getRequest()->getParam('id');
        $force = $this->getRequest()->getParam('force');

        /** @var $order Ess_M2ePro_Model_Order */
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);
        $order->getLog()->setInitiator(Ess_M2ePro_Helper_Data::INITIATOR_USER);

        if (!is_null($order->getMagentoOrderId()) && $force != 'yes') {
            // M2ePro_TRANSLATIONS
            // Magento Order is already created for this eBay Order. Press Create Order Button to create new one.
            $message = 'Magento Order is already created for this eBay Order. ' .
                       'Press Create Order Button to create new one.';

            $this->_getSession()->addWarning(
                Mage::helper('M2ePro')->__($message)
            );
            $this->_redirect('*/*/view', array('id' => $id));
            return;
        }

        // Create magento order
        // ---------------------------------------
        try {
            $order->createMagentoOrder();
            $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Magento Order was created.'));
        } catch (Exception $e) {
            $message = Mage::helper('M2ePro')->__(
                'Magento Order was not created. Reason: %error_message%',
                 Mage::helper('M2ePro/Module_Log')->decodeDescription($e->getMessage())
            );
            $this->_getSession()->addError($message);
        }
        // ---------------------------------------

        if ($order->getChildObject()->canCreatePaymentTransaction()) {
            $order->getChildObject()->createPaymentTransactions();
        }

        if ($order->getChildObject()->canCreateInvoice()) {
            $result = $order->createInvoice();
            $result && $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Invoice was created.'));
        }

        if ($order->getChildObject()->canCreateShipment()) {
            $result = $order->createShipment();
            $result && $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Shipment was created.'));
        }

        if ($order->getChildObject()->canCreateTracks()) {
            $order->getChildObject()->createTracks();
        }

        // ---------------------------------------
        $order->updateMagentoOrderStatus();
        // ---------------------------------------

        return $this->_redirectUrl($this->_getRefererUrl());
    }

    //########################################

    public function goToPaypalAction()
    {
        $transactionId = $this->getRequest()->getParam('transaction_id');

        if (!$transactionId) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Transaction ID should be defined.'));
            return $this->_redirect('*/adminhtml_ebay_order/index');
        }

        /** @var $transaction Ess_M2ePro_Model_Ebay_Order_ExternalTransaction */
        $transaction = Mage::getModel('M2ePro/Ebay_Order_ExternalTransaction')->load($transactionId, 'transaction_id');

        if (is_null($transaction->getId())) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('eBay Order Transaction does not exist.'));
            return $this->_redirect('*/adminhtml_ebay_order/index');
        }

        if (!$transaction->isPaypal()) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('This is not a PayPal Transaction.'));
            return $this->_redirect('*/adminhtml_ebay_order/index');
        }

        return $this->_redirectUrl($transaction->getPaypalUrl());
    }

    //########################################

    public function migrateOrdersPackToV611Action()
    {
        $ordersCount = (int)$this->getRequest()->getParam('orders_count');
        if ($ordersCount <= 0) {
            return;
        }

        /** @var Ess_M2ePro_Model_Upgrade_Migration_ToVersion611_OrdersData $migrationModel */
        $migrationModel = Mage::getModel('M2ePro/Upgrade_Migration_ToVersion611_OrdersData');
        $migrationModel->setMaxOrdersCount($ordersCount);
        $migrationModel->migrate();
    }

    //########################################
}