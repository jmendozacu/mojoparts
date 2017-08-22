<?php
error_reporting(E_ALL);
libxml_use_internal_errors(true);
include('config.php'); 

/***********************************************************/
/* 1st PASS: assign an item_number to any skus missing one */
/***********************************************************/
$p1Count = 0;
$p2Count = 0;
$p3Count = 0;
$p4Count = 0;

echo "************** STARTING PASS 1 ***************".PHP_EOL;
$result = mysqli_query($con, "select vendor_item_number, side, description_scrubbed, ebay_category from mojo_vendor_item_master im where im.item_number is null and im.side is not null and im.side != 'S' and im.side != 'K';");
$numRows = mysqli_num_rows($result);
$rowNum = 0;
while ($row = mysqli_fetch_array($result)) { 
	$item = NULL;
	$eBayCat = $row['ebay_category'];
	$side = $row['side'];
	$vendorItem = $row['vendor_item_number'];
	$description = $row['description_scrubbed'];
	$rowNum++;
	echo "1: row ".$rowNum." of ".$numRows." - ".$vendorItem.PHP_EOL;
	
	// if there is an other side item, and it already has information, use it
	if ($side == "L" || $side == "R") {
		$search = "lh";
		$oppSearch = "rh";
		if ($side == "R") { 
			$search="rh";
			$oppSearch = "lh";
		}
		$pos = strpos($description, $search);
		if ($pos !== FALSE) {
			$oppSearchDescription = str_replace($search, $oppSearch, $description);
			$oppResult = mysqli_query($con, "SELECT item_number, ebay_category, vendor_item_number FROM mojo_vendor_item_master where description_scrubbed = '{$oppSearchDescription}' limit 1");
			if (mysqli_num_rows($oppResult) == 1) {
				$oppRow = mysqli_fetch_array($oppResult);
				$oppItem = $oppRow['item_number'];
				$oppEBayCat = $oppRow['ebay_category'];
				if ($eBayCat == null) { $eBayCat = $oppEBayCat; } // this could get used further down to create the assignment from scratch

				// the other side has a item# assignment, let's use it to assign the item# to this record
				if ($oppItem != NULL) {
					$item = substr($oppItem,0,-1).$side;
					echo "1: ".$item." assigned to [".$vendorItem."]: found opposite side item [".$oppItem."] for vendor item [".$oppRow['vendor_item_number']."]".PHP_EOL;
				}
			}
		}
	}	
	// if we still don't have an assignment, see if there is a pair with an assignment
	if ($item == NULL && ($side == 'B' || $side == 'L' || $side == 'R')) {
		$pairResult = mysqli_query($con, "select item_number, ebay_category from mojo_vendor_item_master im where (im.component_1='{$vendorItem}' or im.component_2='{$vendorItem}') and item_number is not null and side='S' limit 1;");
		if (mysqli_num_rows($pairResult) == 1) {
			$pairRow = mysqli_fetch_array($pairResult);
			$pairItem = $pairRow['item_number'];
			$item = substr($pairItem,0,-2).$side;
			if ($eBayCat == null) { $eBayCat = $pairRow['ebay_category']; } // this could get used further down to create the assignment from scratch
			echo "1: ".$item." assigned to [".$vendorItem."]: found pair: [".$pairItem."]".PHP_EOL;
		}
	}
		
	// if we didn't get an item# from any related items, or the other side has no information, the assign the item# if we have the ebay_category
	if ($item == NULL && $eBayCat != NULL) {
		$catResult = mysqli_query($con, "SELECT * FROM mojo_category_lookup where ebay_category='{$eBayCat}'");
		if (mysqli_num_rows($catResult) == 1) {
			$catRow = mysqli_fetch_array($catResult);
			$prefix = $catRow['prefix'];
			$numResult = mysqli_query($con, "SELECT * FROM mojo_sku_numbering WHERE prefix='{$prefix}' limit 1");
			if (mysqli_num_rows($numResult) == 1) {
				$numRow = mysqli_fetch_array($numResult);
				$suffix = NULL;
				switch ($side) {
					case "L": $suffix = "L"; break;
					case "R": $suffix = "R"; break;
					case "B": $suffix = "B"; break;
					case "N": $suffix = ""; break;
					default: echo "   1: ERROR: invalid side: [".$side."]".PHP_EOL;
				}
				$item = $prefix.str_pad($numRow['next_sequence'], 5, "0", STR_PAD_LEFT).$suffix;
				mysqli_query($con, "UPDATE mojo_sku_numbering SET next_sequence=next_sequence+1 WHERE prefix = '{$prefix}'");
				echo "1: ".$item." assigned to [".$vendorItem."] from scratch".PHP_EOL;
			}
		}
	}

	if($item != NULL) {
		mysqli_query($con, "UPDATE mojo_vendor_item_master SET item_number = '{$item}', ebay_category='{$eBayCat}' where vendor_item_number = '{$vendorItem}'");
		$p1Count++;
	}
} 

/********************************************************************************************************************/
/* 2nd PASS: assign item_number and other data based on other side.  This catches any assignments made from pass 1. */
/********************************************************************************************************************/
echo "************** STARTING PASS 2 ***************".PHP_EOL;
$result = mysqli_query($con, "select vendor_item_number, side, description_scrubbed, ebay_category from mojo_vendor_item_master im where im.item_number is null and (im.side = 'L' or im.side = 'R' or im.side = 'B')");
$numRows = mysqli_num_rows($result);
$rowNum = 0;
while ($row = mysqli_fetch_array($result)) { 
	$item = NULL;
	$eBayCat = $row['ebay_category'];
	$side = $row['side'];
	$vendorItem = $row['vendor_item_number'];
	$description = $row['description_scrubbed'];
	$rowNum++;
	echo "2: row ".$rowNum." of ".$numRows." - ".$vendorItem.PHP_EOL;

	// if we have the side, then check the other side to see if it has the category
	if ($side == "L" || $side == "R") {
		$search = "lh";
		$oppSearch = "rh";
		if ($side == "R") { 
			$search="rh";
			$oppSearch = "lh";
		}
		$pos = strpos($description, $search);

		if ($pos !== FALSE) {
			$oppSearchDescription = str_replace($search, $oppSearch, $description);
			$oppResult = mysqli_query($con, "SELECT item_number, ebay_category, vendor_item_number, side FROM mojo_vendor_item_master where description_scrubbed = '{$oppSearchDescription}' and item_number is not NULL limit 1");
			if (mysqli_num_rows($oppResult) == 1) {
				$oppRow = mysqli_fetch_array($oppResult);
				$oppItem = $oppRow['item_number'];
				$oppEBayCat = $oppRow['ebay_category'];
				
				// we know the other side has a item# assignment, so let's use it to assign the item# to this record
				$item = substr($oppItem,0,-1).$side;
				echo "2: ".$item." assigned to [".$vendorItem."]: found opposite side item [".$oppItem."] for vendor item [".$oppRow['vendor_item_number']."]".PHP_EOL;
				if ($eBayCat == NULL && $oppEBayCat != NULL && $oppEBayCat != "") {
					$eBayCat = $oppEBayCat;
				}
			}
		}
	}
	if($item != NULL) {
		mysqli_query($con, "UPDATE mojo_vendor_item_master SET item_number = '{$item}', ebay_category='{$eBayCat}' where vendor_item_number = '{$vendorItem}'");
		$p2Count++;
	}
}

/****************************************************/
/* 3rd PASS: make assignments to any existing pairs */
/****************************************************/
echo "************** STARTING PASS 3 ***************".PHP_EOL;
$result = mysqli_query($con, "select vendor_item_number, component_1, component_2, ebay_category from mojo_vendor_item_master im where im.item_number is null and im.side = 'S' and component_1 is not null and component_2 is not null;");
$numRows = mysqli_num_rows($result);
$rowNum = 0;
while ($row = mysqli_fetch_array($result)) { 
	$item = NULL;
	$vendorItem = $row['vendor_item_number'];
	$component1 = $row['component_1'];
	$component2 = $row['component_2'];
	$eBayCat = $row['ebay_category'];
	$rowNum++;
	echo "3: row ".$rowNum." of ".$numRows." - ".$vendorItem.PHP_EOL;

	// if corresponding components have an assignment, use it
	$singleResult = mysqli_query($con, "select item_number, ebay_category from mojo_vendor_item_master im where (im.vendor_item_number='{$component1}' or im.vendor_item_number='{$component2}') and item_number is not null limit 1;");
	if (mysqli_num_rows($singleResult) == 1) {
		$singleRow = mysqli_fetch_array($singleResult);
		$singleItem = $singleRow['item_number'];
		$singleEbayCat = $singleRow['ebay_category'];
		if (substr($singleItem,-1) == "L" || substr($singleItem,-1) == "R" || substr($singleItem,-1) == "B") {
			$item = substr($singleItem,0,-1)."LR";
			if ($eBayCat == NULL) { $eBayCat = $singleEbayCat; }
			echo "3: assigned [".$item."] since corresponding single [".$singleItem."] was found.".PHP_EOL;
		}

	// if corresponding components don't exist or don't have assignments, then assign from scratch based on ebay_category
	} else {
		if($eBayCat != NULL) {
			$catResult = mysqli_query($con, "SELECT * FROM mojo_category_lookup where ebay_category='{$eBayCat}'");
			if (mysqli_num_rows($catResult) == 1) {
				$catRow = mysqli_fetch_array($catResult);
				$prefix = $catRow['prefix'];
				$numResult = mysqli_query($con, "SELECT * FROM mojo_sku_numbering WHERE prefix='{$prefix}' limit 1");
				if (mysqli_num_rows($numResult) == 1) {
					$numRow = mysqli_fetch_array($numResult);
					$item = $prefix.str_pad($numRow['next_sequence'], 5, "0", STR_PAD_LEFT)."LR";
					mysqli_query($con, "UPDATE mojo_sku_numbering SET next_sequence=next_sequence+1 WHERE prefix = '{$prefix}'");
					echo "3: ".$item." assigned to [".$vendorItem."] from scratch".PHP_EOL;
				}
			}
		}
	}
	if($item != NULL) {
		mysqli_query($con, "UPDATE mojo_vendor_item_master SET item_number = '{$item}', ebay_category = '{$eBayCat}' where vendor_item_number = '{$vendorItem}'");
		$p3Count++;
	}
}

/**************************************************************************/
/* 4th PASS: create new pairs for any singles that don't have one already */
/**************************************************************************/
echo "************** STARTING PASS 4 ***************".PHP_EOL;
$result = mysqli_query($con, "select item_number, vendor_item_number, side, description_scrubbed, ebay_category, ebay_store_category, magento_category from mojo_vendor_item_master im where im.item_number is not null and (im.side = 'R' or im.side = 'B');");
$numRows = mysqli_num_rows($result);
$rowNum = 0;
while ($row = mysqli_fetch_array($result)) { 
	$singleItem = $row['item_number'];
	$item = substr($singleItem,0,-1)."LR";
	$singleVendorItem = $row['vendor_item_number'];
	$vendorItem = NULL;
	$side = $row['side'];
	$description = $row['description_scrubbed'];
	$eBayCat = $row['ebay_category'];
	$eBayStoreCat = $row['ebay_store_category'];
	$magentoCategory = $row['magento_category'];
	$component1 = NULL;
	$component2 = NULL;
	$rowNum++;
	echo "4: row ".$rowNum." of ".$numRows." - ".$singleVendorItem.PHP_EOL;
	
	// check to see if the pair item already exists based on the base item#
	$pairResult = mysqli_query($con, "select item_number from mojo_vendor_item_master im where im.item_number = '{$item}' limit 1;");
	if (mysqli_num_rows($pairResult) != 1) {

		// No existing pair was found.  So handle the easy case, where the single side = "B",
		if ($side == "B") {
			$component1 = $vendorItem;
			$component2 = $vendorItem;
			$vendorItem = "SET-".$singleVendorItem;
			
		// otherwise side = "R", so look for the left side
		} else {
			$search = "rh";
			$oppSearch = "lh";
			$pos = strpos($description, $search);

			if ($pos !== FALSE) {
				$oppSearchDescription = str_replace($search, $oppSearch, $description);
				$oppResult = mysqli_query($con, "SELECT item_number, ebay_category, vendor_item_number FROM mojo_vendor_item_master where description_scrubbed = '{$oppSearchDescription}' limit 1;");
				if (mysqli_num_rows($oppResult) == 1) {
					$oppRow = mysqli_fetch_array($oppResult);
					$oppItem = $oppRow['item_number'];
					if (substr($oppItem,0,-1) == substr($singleItem,0,-1)) {
						$component1 = $singleVendorItem;
						$component2 = $oppRow['vendor_item_number'];
						$vendorItem = "SET-".$singleVendorItem; // it's usually the RH side used in their numbering scheme
					}
				}
			}
		}
		if ($component1 != NULL && $component2 != NULL && $vendorItem != NULL) {
			mysqli_query($con, "INSERT INTO `mojo_vendor_item_master` (`vendor`, `vendor_item_number`, `component_1`, `component_2`,  `item_number`, `ebay_category`, `ebay_store_category`, `magento_category`, `side`) 
			VALUES ('PFG', '{$vendorItem}', '{$component1}', '{$component2}', '{$item}', '{$eBayCat}', '{$eBayStoreCat}', '{$magentoCategory}', 'S');"); 
			echo "4: created new pair [".$item."] using vendor item [".$vendorItem."] composed of [".$component1."] and [".$component2."]".PHP_EOL;
			$p4Count++;
		}
	}
}

echo "--------------------------------------------------".PHP_EOL;
echo "1: single assignments: ".$p1Count.PHP_EOL;
echo "2: more single assignments building on step 1: ".$p2Count.PHP_EOL;
echo "3: existing pair assignments: ".$p3Count.PHP_EOL;
echo "4: create new pairs: ".$p4Count.PHP_EOL.PHP_EOL.PHP_EOL;

?>