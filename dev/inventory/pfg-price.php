<?php
error_reporting(E_ALL);

$magento_con = mysqli_init();
mysqli_real_connect($magento_con, 'mojomysql2.c6orzbehh7d1.us-east-1.rds.amazonaws.com','mojo','3^-4Grj,;pF7[3kN','mojomagento');

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
		$newPrice = $currentPrice; // initialize 	for now
		$newTotalCost = $newItemCost + $newShippingCost + $newHandlingCost; // initialize for now
		$tempPrice15 = round(($newTotalCost + 0.3) / (0.8785 - 0.15), 2);  // calc the price @ 15%

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
		
		$minProfitAmt = 5;
		$minProfitPct = 5;

		$calcedPrice = round(($newTotalCost + 0.3) / (0.8785 - $currentProfitPct / 100), 2);
		$calcedMinPrice = round(($newTotalCost + 0.3 + $minProfitAmt) / 0.8785, 2);
		$tempMinPrice =  round(($newTotalCost + 0.3) / (0.8785 - ($minProfitPct / 100)), 2);
		if ($tempMinPrice > $calcedMinPrice) $calcedMinPrice = $tempMinPrice; // take the greater of the 2
		if ($calcedMinPrice > $calcedPrice) {
			$calcedPrice = $calcedMinPrice;
		}
		$calcedProfitAmt = round($calcedPrice - $newTotalCost - $calcedPrice*0.1215 - 0.3, 2);
		$calcedProfitPct = round($calcedProfitAmt / $calcedPrice, 2);
		$reasonCode = 0;
		$reason = NULL;
		
		if ($newTotalCost < $currentTotalCost) { // costs have gone down
			if ($tempPrice15 < $currentPrice) { // best case scenario - lower costs, we can get back to trying full profit
				if ($daysSinceLastSale > 60) { // hasn't had a recent sale, so let's lower it
					if ($calcedMinPrice > $tempPrice15) {
						$newPrice = $calcedMinPrice; // don't let it go below floor
						$reasonCode = 15;
						$reason = "The PFG costs decreased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), there are no recent sales (60d), and the new floor price @ floor (above 15%) (".$newPrice.") is still below the current price (".$currentPrice.") So we can lower the price AND make more profit - great!.";
					}
					else {
						$newPrice = $tempPrice15;
						$reasonCode = 8;
						$reason = "The PFG costs decreased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), there are no recent sales (60d), and the new price @ 15% (".$newPrice.") is below the current price (".$currentPrice.") So we can lower the price AND make more profit - great!.";
					}
				}
				else {
					// it has recent sales, so let's try keeping the price the same and see if it keeps selling (with better profit%)
					$reasonCode = 16;
					$reason = "The PFG costs decreased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), but there are recent sales. So we will leave the price alone to see if we can continue sales but make more profit.";
				}
			}
			else { // the new price @ 15% is more than the current price, so shoot lower
				if ($calcedMinPrice > $currentPrice) { // (sanity check) currently underpriced (mistakenly) despite costs being lower
					$newPrice = $calcedMinPrice;
					$reasonCode = 9;
					$reason = "The PFG costs decreased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), but the current price (".$currentPrice.") is below the new floor (".$calcedMinPrice.") so we have to fix this (this should never happen though).";
				}
				else { // it's priced above the new floor
					// do nothing - this is a good compromise - leave price alone but get more profit from lower costs
					$reasonCode = 10;
					$reason = "The PFG costs decreased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), and the current price (".$currentPrice.") is above the new floor (".$calcedMinPrice.") so we can leave it alone and just make more profit - great again!";
				}
			}
		}
		else if ($newTotalCost > $currentTotalCost) { // costs have gone up
			if ($daysSinceLastSale < 365) { // has had 1Y sales 
				if ($calcedMinPrice > $currentPrice) { // the new floor is higher than the current price... we can't have that, so...
					$newPrice = $calcedMinPrice; // disturb the price as little as possible, but we have to at least get up the floor price
					$reasonCode = 1;
					$reason = "The PFG costs increased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), and the minimum price (".$calcedMinPrice.") is now above the current price (".$currentPrice.") But since it has had 1Y sales (most recent sale: ".$latestOrderDate.", ".$daysSinceLastSale." days ago), we don't want to disturb that.  So we will just set the new price = the minimum price.";
				}
				else  { // lucky, we can leave the price alone and just adjust the profit% value
					// $newPrice was already set to $currentPrice when it was initalized earlier
					$reasonCode = 2;
					$reason = "The PFG costs increased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), but the minimum price (".$calcedMinPrice.") is no greater than the current price (".$currentPrice.") Since it has had 1Y sales (most recent sale: ".$latestOrderDate.", ".$daysSinceLastSale." days ago), we don't want to disturb that.  So we will just leave it alone.";
				}
			}
			else {
				if ($startDateStr == $today) { // this is newly in stock
					$newPrice = $calcedPrice; // this is a brand new listing, so give it a chance to sell at the current profit%
					$reasonCode = 3;
					$reason = "The PFG costs increased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), and it has NOT had 1Y sales and it's a new listing, so let's give it a shot with the new price.";
				}
				else {
					$newPrice = $calcedMinPrice; // this has never sold and has already been listed, so drop it to the floor and see if that gets it going
					$reasonCode = 4;
					$reason = "The PFG costs increased (old:".$currentTotalCost."=".$currentItemCost."+".$currentShippingCost."+".$currentHandlingCost.", vs new:".$newTotalCost."=".$newItemCost."+".$newShippingCost."+".$newHandlingCost."), and it has NOT had 1Y sales and it's not a brand new listing, so let's get it kickstarted by lowering it to the floor price.";
				}
			}
		}
		else { // costs are the same, so let's re-evaluate the situation with this one
			// this was manually stopped (GTC) or finished (FP30)
			// we need to change the price a little if we want to refresh the listing,
			// only if it hasn't already been recently randomized and is just stuck in stopped/finished due to some error
			if ($listingStatus == 3 || $listingStatus == 4) { 
				if ($startDateStr > $priceUpdateDate) { // make sure we're only doing an update 1 time (if the listing is stuck in stopped/finished for many days
					if ($daysSinceLastSale < 365) {
						if ($calcedMinPrice > $currentPrice) { // it's priced below floor, so even though it hasn't sold in 60d, it has sold >60d, let's try to keep the price as low as possible 
							$newPrice = $calcedMinPrice;
							$reasonCode = 5;
							$reason = "The PFG costs are the same, the listing is manually stopped or finished, the most recent sale has been within 1Y although not within 60d (".$daysSinceLastSale." days), but it's currently priced (".$currentPrice.") below floor (".$calcedMinPrice."). So we need to at least set the new price = the minimum price.";
						}
						else { // this has had sales in the last year, but it's dead, so it's a candidate to randomize the price 
							$randomIncrease = rand(25,75);
							$newPrice = $currentPrice + $randomIncrease/100;
							$reasonCode = 6;
							$reason = "The PFG costs are the same, the listing is manually stopped or finished due to no recent sales, so randomly adjust the price to cause it to refresh.";
						}
					}
					else { // has already been listed for 60d but hasn't had sales in the last year, so it's a candidate for reduction
						if ($calcedMinPrice > $currentPrice) {
							$newPrice = $calcedMinPrice;
							$reasonCode = 13;
							$reason = "The PFG costs are the same, the listing is manually stopped, it hasn't sold within 1Y (".$daysSinceLastSale." days), but it's currently priced (".$currentPrice.") below floor (".$calcedMinPrice."). So we need to raise the price at least up to the floor.";
						} else if ($calcedMinPrice == $currentPrice) {
							$randomIncrease = rand(25,75);
							$newPrice = $currentPrice + $randomIncrease/100;
							$reasonCode = 17;
							$reason = "The PFG costs are the same, the listing is manually stopped or finished due to no sales in over a year, so randomly adjust the price to cause it to refresh.";
						}
						else { // we can only lower it if it is not already at the floor price
							$tempPrice = round(($newTotalCost + 0.3) / (0.8785 - ($currentProfitPct-3) / 100),2); // reduce the current profit by 3
							if ($tempPrice < $calcedMinPrice) $newPrice = $calcedMinPrice;
							else $newPrice = $tempPrice;
							$reasonCode = 7;
							$reason = "The PFG costs are the same, the listing is manually stopped or finished, it hasn't sold within 1Y (".$daysSinceLastSale." days), and it's currently priced (".$currentPrice.") above floor (".$calcedMinPrice."). So let's try reducing the profit% (".$currentProfitPct.") by 3%.";
						}
					}
				}
			}
			else { // the listing was not manually stopped or finished, there was no cost change, so this is an existing active listing that should just be double-checked to make sure the pricing is ok
				if ($calcedMinPrice > $currentPrice) { // it is underpriced, need to move up to floor
					$newPrice = $calcedMinPrice;
					$reasonCode = 11;
					$reason = "The PFG costs are the same, the listing NOT manually stopped, but it's currently priced (".$currentPrice.") below floor (".$calcedMinPrice."). So we need to at least set the new price = the minimum price.";
				}
				else { 
					// price is the dummy initial value, before we had the actual costs
					if ($currentPrice >= 99999.99) { 
						$newPrice = $tempPrice15; 
						$reasonCode = 14;
						$reason = "The listing NOT manually stopped, and it's currently priced with the dummy starting price of $99999.99, so set it to the 15% profit price.";
					} 
					else {
						// leave the price alone (newPrice already initialized as currentPrice)
						$reasonCode = 12;
						$reason = "The PFG costs are the same, the listing NOT manually stopped, and it's currently priced (".$currentPrice.") at or above floor (".$calcedMinPrice."). So will leave it alone, except to update the profit%.";
					}
				}
			}
		}

		$finalPrice = round($newPrice, 2);
		$finalProfitPct = round((($finalPrice - $newTotalCost - $finalPrice*0.1215 - 0.3) / $finalPrice)*100, 2);
		$priceDiff = $finalPrice - $currentPrice;

		if ($finalPrice <> $currentPrice || $finalProfitPct <> $currentProfitPct || $newTotalCost <> $currentTotalCost) {
			$array = array($sku, $finalPrice, $calcedMinPrice, $newItemCost, $newShippingCost, $newHandlingCost, $msrp, $finalProfitPct, $today, $priceDiff, $reasonCode, $reason);
			fputcsv($pfgPriceCSV, $array);
		}
	} // while loop
}

$magento_con->close();

echo "DONE.".PHP_EOL;
?>
	