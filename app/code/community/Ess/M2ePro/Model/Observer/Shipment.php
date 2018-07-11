<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Observer_Shipment extends Ess_M2ePro_Model_Observer_Abstract
{
    //########################################

    public function process()
    {
        if (Mage::helper('M2ePro/Data_Global')->getValue('skip_shipment_observer')) {
            Mage::helper('M2ePro/Data_Global')->unsetValue('skip_shipment_observer');
            return;
        }

        /** @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $this->getEvent()->getShipment();
        $magentoOrderId = $shipment->getOrderId();

        try {
            /** @var $order Ess_M2ePro_Model_Order */
            $order = Mage::helper('M2ePro/Component')
                            ->getUnknownObject('Order', $magentoOrderId, 'magento_order_id');
        } catch (Exception $e) {
            return;
        }

        if (is_null($order)) {
            return;
        }

        if (!in_array($order->getComponentMode(), Mage::helper('M2ePro/Component')->getActiveComponents())) {
            return;
        }

        $order->getLog()->setInitiator(Ess_M2ePro_Helper_Data::INITIATOR_EXTENSION);

        /** @var $shipmentHandler Ess_M2ePro_Model_Order_Shipment_Handler */
        $shipmentHandler = Mage::getModel('M2ePro/Order_Shipment_Handler')->factory($order->getComponentMode());
        $shipmentHandler->handle($order, $shipment);
    }

    //########################################
}