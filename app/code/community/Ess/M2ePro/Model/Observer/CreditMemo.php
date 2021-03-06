<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Observer_Creditmemo extends Ess_M2ePro_Model_Observer_Abstract
{
    //########################################

    public function process()
    {
        try {

            /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
            $creditmemo = $this->getEvent()->getCreditmemo();
            $magentoOrderId = $creditmemo->getOrderId();

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

            if ($order->getComponentMode() != Ess_M2ePro_Helper_Component_Amazon::NICK) {
                return;
            }

            /** @var Ess_M2ePro_Model_Amazon_Order $amazonOrder */
            $amazonOrder = $order->getChildObject();

            if (!$amazonOrder->canRefund()) {
                return;
            }

            $order->getLog()->setInitiator(Ess_M2ePro_Helper_Data::INITIATOR_EXTENSION);

            $itemsForCancel = array();

            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                /** @var Mage_Sales_Model_Order_Creditmemo_Item $creditmemoItem */

                $additionalData = $creditmemoItem->getOrderItem()->getAdditionalData();
                if (!is_string($additionalData)) {
                    continue;
                }

                $additionalData = @unserialize($additionalData);
                if (!is_array($additionalData) ||
                    empty($additionalData[Ess_M2ePro_Helper_Data::CUSTOM_IDENTIFIER]['items'])
                ) {
                    continue;
                }

                foreach ($additionalData[Ess_M2ePro_Helper_Data::CUSTOM_IDENTIFIER]['items'] as $item) {
                    $amazonOrderItemId = $item['order_item_id'];

                    if (in_array($amazonOrderItemId, $itemsForCancel)) {
                        continue;
                    }

                    /** @var Ess_M2ePro_Model_Mysql4_Amazon_Order_Item_Collection $amazonOrderItemCollection */
                    $amazonOrderItemCollection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Order_Item');
                    $amazonOrderItemCollection->addFieldToFilter('amazon_order_item_id', $amazonOrderItemId);

                    /** @var Ess_M2ePro_Model_Order_Item $orderItem */
                    $orderItem = $amazonOrderItemCollection->getFirstItem();

                    if (is_null($orderItem) || !$orderItem->getId()) {
                        continue;
                    }

                    /** @var Ess_M2ePro_Model_Amazon_Order_Item $amazonOrderItem */
                    $amazonOrderItem = $orderItem->getChildObject();

                    $price = $creditmemoItem->getPriceInclTax();
                    if ($price > $amazonOrderItem->getPrice()) {
                        $price = $amazonOrderItem->getPrice();
                    }

                    $tax = $creditmemoItem->getTaxAmount();
                    if ($tax > $amazonOrderItem->getTaxAmount()) {
                        $tax = $amazonOrderItem->getTaxAmount();
                    }

                    $itemsForCancel[] = array(
                        'item_id'  => $amazonOrderItemId,
                        'qty'      => $creditmemoItem->getQty(),
                        'prices'   => array(
                            'product' => $price,
                        ),
                        'taxes'    => array(
                            'product' => $tax,
                        ),
                    );
                }
            }

            $amazonOrder->refund($itemsForCancel);

        } catch (Exception $exception) {

            Mage::helper('M2ePro/Module_Exception')->process($exception);

        }
    }

    //########################################
}