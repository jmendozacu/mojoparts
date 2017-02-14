<?php

require_once './app/Mage.php';
Mage::app();

$orderIds = array_reverse(Mage::getResourceModel('sales/order_collection')->getAllIds());
foreach ($orderIds as $orderId) {
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) continue;

	$order->setData('updated_at', $order->getUpdatedAt());

	if (!$order->getPayment()) {
		echo 'skip: ' . $order->getId() . ' - ' . $order->getIncrementId() . PHP_EOL;
		continue;
	}

	// echo 'save: ' . $order->getId() . ' - ' . $order->getIncrementId() . PHP_EOL;
	// $order->save();
}
