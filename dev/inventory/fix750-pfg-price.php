<?php
error_reporting(E_ALL);

$magento_con = mysqli_init();
// TODO: use an included config file for db connection.  The hard-coded connection was removed for better security.
// mysqli_real_connect($con, '$server','$user','$passord','$database');

echo "... calculate PFG price changes".PHP_EOL;
$query = "SELECT  
a.entity_id,
a.sku, 
b.value as 'vendor_item',
c.is_in_stock,
p.value as 'price',
cost.value as 'item_cost',
ship.value as 'shipping_cost',
hand.value as 'handling_cost',
fl.value as 'floor_price',
msrp.value as 'msrp',
mkp.value as 'markup_pct',
lp.status as 'listing_status',
elp.start_date 'start_date',
max(oi.created_at) as 'latest_order_date',
mvi.item_cost as 'new_item_cost',
mvi.shipping_cost as 'new_shipping_cost',
mvi.handling_cost as 'new_handling_cost',
pud.value as 'price_update_date'
FROM catalog_product_entity a
inner join catalog_product_entity_varchar b on a.entity_id = b.entity_id and b.attribute_id = 164
inner join catalog_product_entity_int d on a.entity_id = d.entity_id and d.attribute_id = 163 and d.value=36
inner join cataloginventory_stock_item c on a.entity_id = c.product_id and c.is_in_stock=1
inner join catalog_product_entity_decimal p on a.entity_id = p.entity_id and p.attribute_id=75
inner join catalog_product_entity_int sts on a.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
inner join mojo_vendor_inventory mvi on mvi.item_number=a.sku and mvi.warehouse='VA'
left join catalog_product_entity_decimal cost on a.entity_id = cost.entity_id and cost.attribute_id=79
left join catalog_product_entity_decimal ship on a.entity_id = ship.entity_id and ship.attribute_id=161
left join catalog_product_entity_decimal hand on a.entity_id = hand.entity_id and hand.attribute_id=159
left join catalog_product_entity_decimal fl on a.entity_id = fl.entity_id and fl.attribute_id=185
left join catalog_product_entity_decimal msrp on a.entity_id = msrp.entity_id and msrp.attribute_id=120
left join catalog_product_entity_int stg on a.entity_id = stg.entity_id and stg.attribute_id=188
left join catalog_product_entity_varchar mkp on a.entity_id = mkp.entity_id and mkp.attribute_id=189
left join catalog_product_entity_decimal rp on a.entity_id = rp.entity_id and rp.attribute_id=191
left join m2epro_ebay_listing_product elp on elp.online_sku=a.sku
left join m2epro_listing_product lp on lp.id=elp.listing_product_id
left join sales_flat_order_item oi on oi.product_id=a.entity_id
left join catalog_product_entity_datetime pud on a.entity_id = pud.entity_id and pud.attribute_id = 219
group by a.entity_id 
UNION
SELECT  
a.entity_id,
a.sku, 
b.value as 'vendor_item',
c.is_in_stock,
p.value as 'price',
cost.value as 'item_cost',
ship.value as 'shipping_cost',
hand.value as 'handling_cost',
fl.value as 'floor_price',
msrp.value as 'msrp',
mkp.value as 'markup_pct',
lp.status as 'listing_status',
elp.start_date 'start_date',
max(oi.created_at) as 'latest_order_date',
mvi1.item_cost + mvi2.item_cost as 'new_item_cost',
greatest(mvi1.shipping_cost,mvi2.shipping_cost) as 'new_shipping_cost',
mvi1.handling_cost+mvi2.handling_cost as 'new_handling_cost',
pud.value as 'price_update_date'
FROM catalog_product_entity a
inner join catalog_product_entity_varchar b on a.entity_id = b.entity_id and b.attribute_id = 164
inner join catalog_product_entity_int d on a.entity_id = d.entity_id and d.attribute_id = 163 and d.value=36
inner join cataloginventory_stock_item c on a.entity_id = c.product_id and c.is_in_stock=1
inner join catalog_product_entity_decimal p on a.entity_id = p.entity_id and p.attribute_id=75
inner join catalog_product_entity_int sts on a.entity_id = sts.entity_id and sts.attribute_id=96 and sts.value=1
inner join mojo_vendor_item_master im on im.item_number=a.sku
inner join mojo_vendor_inventory mvi1 on mvi1.vendor_item_number=im.component_1 and mvi1.warehouse='VA'
inner join mojo_vendor_inventory mvi2 on mvi2.vendor_item_number=im.component_2 and mvi2.warehouse='VA'
left join catalog_product_entity_decimal cost on a.entity_id = cost.entity_id and cost.attribute_id=79
left join catalog_product_entity_decimal ship on a.entity_id = ship.entity_id and ship.attribute_id=161
left join catalog_product_entity_decimal hand on a.entity_id = hand.entity_id and hand.attribute_id=159
left join catalog_product_entity_decimal fl on a.entity_id = fl.entity_id and fl.attribute_id=185
left join catalog_product_entity_decimal msrp on a.entity_id = msrp.entity_id and msrp.attribute_id=120
left join catalog_product_entity_int stg on a.entity_id = stg.entity_id and stg.attribute_id=188
left join catalog_product_entity_varchar mkp on a.entity_id = mkp.entity_id and mkp.attribute_id=189
left join catalog_product_entity_decimal rp on a.entity_id = rp.entity_id and rp.attribute_id=191
left join m2epro_ebay_listing_product elp on elp.online_sku=a.sku
left join m2epro_listing_product lp on lp.id=elp.listing_product_id
left join sales_flat_order_item oi on oi.product_id=a.entity_id
left join catalog_product_entity_datetime pud on a.entity_id = pud.entity_id and pud.attribute_id = 219
group by a.entity_id;";
$result = mysqli_query($magento_con, $query);
if (!$result) {
	echo "ERROR: ".$query.PHP_EOL;
	exit(1);
}

if (mysqli_num_rows($result)) {
	$pfgPriceCSVName = "/var/www/html/var/import/pfg-price.csv";
	$pfgPriceCSV = fopen($pfgPriceCSVName, "w");
	fputcsv($pfgPriceCSV, array('sku','price','floor_price','cost','shipping_cost','handling_cost','msrp','markup_pct','price_update_date','price_update_diff','price_update_reason_code','price_update_reason'));
	$today = date('Y-m-d');
	
	while ($row = mysqli_fetch_array($result)) { 
		$sku = $row['sku'];
		$currentPrice = round($row['price'],2);
		$currentItemCost = round($row['item_cost'],2);
		$currentShippingCost = round($row['shipping_cost'],2);
		$currentHandlingCost = round($row['handling_cost'],2);
		$currentProfitPct = round(round($row['markup_pct'],2),2);
		$currentTotalCost = $currentItemCost + $currentShippingCost + $currentHandlingCost;

		$newItemCost = round($row['new_item_cost'],2);
		$newShippingCost = round($row['new_shipping_cost'],2);
		$newHandlingCost = round($row['new_handling_cost'],2);
		$newPrice = $currentPrice; // initialize for now
		$newTotalCost = $newItemCost + $newShippingCost + $newHandlingCost; // initialize for now

		$msrp = round($row['msrp'],2);
		$listingStatus = $row['listing_status'];
		$startDate = $row['start_date'];
		$startDateStr = NULL;
		if ($startDate != NULL) $startDateStr = date('Y-m-d',strtotime($startDate));
		$priceUpdateDate = $row['price_update_date'];
		$priceUpdateDateStr = NULL;
		if ($priceUpdateDate != NULL) $priceUpdateDateStr = date('Y-m-d',strtotime($priceUpdateDate));
		$daysSinceLastSale = 366;
		$daysSincePriceUpdate = 999;
		if ($priceUpdateDate != NULL) {
			$daysSincePriceUpdate = floor((time()-strtotime($priceUpdateDate))/(60*60*24));
		}
		$latestOrderDate = $row['latest_order_date'];
		if ($latestOrderDate != NULL && $latestOrderDate <> '') {
			$latestOrderDate = date('Y-m-d',strtotime($latestOrderDate));
			$daysSinceLastSale = floor((strtotime($today)-strtotime($latestOrderDate)) / (60 * 60 * 24));
		}
		
		// when making a profit calc change, these values represent the old scheme
		$prevMinProfitAmt = 5;
		$prevMinProfitPct = 5;
		$prevMinNewPrice = round(($newTotalCost + 0.3 + $prevMinProfitAmt) / 0.8785, 2); // new price based on the previous min profit $
		$tempPrevMinPrice =  round(($newTotalCost + 0.3) / (0.8785 - ($prevMinProfitPct / 100)), 2); // new price based on previous min profit %
		if ($tempPrevMinPrice > $prevMinNewPrice) {
			$prevMinNewPrice = $tempPrevMinPrice; // take the greater of the 2
		}

		// when making a profit calc change, these values represent the new scheme
		$minProfitAmt = 10;
		$minProfitPct = 10;
		$minNewPrice = round(($newTotalCost + 0.3 + $minProfitAmt) / 0.8785, 2); // new price based on min profit $
		$tempMinPrice =  round(($newTotalCost + 0.3) / (0.8785 - ($minProfitPct / 100)), 2); // new price based on min profit %
		if ($tempMinPrice > $minNewPrice) {
			$minNewPrice = $tempMinPrice; // take the greater of the 2
		}

		$calcedNewPrice = round(($newTotalCost + 0.3) / (0.8785 - $currentProfitPct / 100), 2); // new price based on current profit %
		if ($minNewPrice > $calcedNewPrice) {
			$calcedNewPrice = $minNewPrice; // don't let new price fall below the minimum
		}
		$maxNewPrice = round(($newTotalCost + 0.3) / (0.8785 - 0.20), 2);  // new price based on max profit (20%)
		if ($minNewPrice > $maxNewPrice) {
			$maxNewPrice = $minNewPrice; // don't let max price fall below the minimum (could happen if it's low cost)
		}

		$calcedProfitAmt = round($calcedNewPrice - $newTotalCost - $calcedNewPrice*0.1215 - 0.3, 2);
		$calcedProfitPct = round(($calcedProfitAmt / $calcedNewPrice)*100, 2);
		$reasonCode = 0;
		$reason = NULL;
		
		if ($maxNewPrice >= 750) {
			$profitAmt750 = round(750 - $newTotalCost - 750*0.1215 - 0.3, 2);
			$profitPct750 = round(($profitAmt750 / 750)*100, 2);
			if ($profitPct750 > $minProfitPct) { // it's still ok to drop the price below 750, so let's do that rather than not sell this
				$newPrice = 749.99;
				$reasonCode = 20;
				$reason = "The PFG costs increased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), but the new price is more than 750, but it's still minimally profitable at 750, so let's drop it to 749.99.";
			}
			else {
				$newPrice = $maxNewPrice; 
				$reasonCode = 21; 
				$reason = "The PFG costs increased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), and it can't be sold profitably for less than $750, so we need to set the price normally (above $750) and end the listing.";
			}
		}

		$finalPrice = round($newPrice, 2);
		$finalProfitPct = round((($finalPrice - $newTotalCost - $finalPrice*0.1215 - 0.3) / $finalPrice)*100, 2);
		$priceDiff = $finalPrice - $currentPrice;

		if ($finalPrice <> $currentPrice || $finalProfitPct <> $currentProfitPct || $newTotalCost <> $currentTotalCost) {
			$array = array($sku, $finalPrice, $minNewPrice, $newItemCost, $newShippingCost, $newHandlingCost, $msrp, $finalProfitPct, $today, $priceDiff, $reasonCode, $reason);
			fputcsv($pfgPriceCSV, $array);
		}
	} // while loop
}

$magento_con->close();

echo "DONE.".PHP_EOL;
?>
	