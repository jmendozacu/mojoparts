<?php
/**
 * Customerreviewimport.php
 * CommerceExtensions @ InterSEC Solutions LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.commerceextensions.com/LICENSE-M1.txt
 *
 * @category   Review
 * @package    Customerreviewimport
 * @copyright  Copyright (c) 2003-2010 CommerceExtensions @ InterSEC Solutions LLC. (http://www.commerceextensions.com)
 * @license    http://www.commerceextensions.com/LICENSE-M1.txt
 */ 
 
class CommerceExtensions_Customerreviewsimportexport_Model_Convert_Adapter_Customerreviewimport extends Mage_Eav_Model_Convert_Adapter_Entity
{
    protected $_stores;

    public function parse()
    {
        $batchModel = Mage::getSingleton('dataflow/batch');
        /* @var $batchModel Mage_Dataflow_Model_Batch */

        $batchImportModel = $batchModel->getBatchImportModel();
        $importIds = $batchImportModel->getIdCollection();

        foreach ($importIds as $importId) {
            //print '<pre>'.memory_get_usage().'</pre>';
            $batchImportModel->load($importId);
            $importData = $batchImportModel->getBatchData();

            $this->saveRow($importData);
        }
    }

    /**
     * Save Customer Review (import)
     *
     * @param array $importData
     * @throws Mage_Core_Exception
     * @return bool
     */
    public function saveRow(array $importData)
    {
		//SITE 2 SITE TRANSFER ADDED ONLY
		if($this->getBatchParams('customerreviews_map_store_ids') != "") {
			$mapped_store_values = explode(",",$this->getBatchParams('customerreviews_map_store_ids'));
			foreach ($mapped_store_values as $mapped_values) {
				$mapped_value = explode("=",$mapped_values);
				$delimiteddata = explode(',',$importData['store_ids']);
				foreach ($delimiteddata as $individualstoreID) {
					if($individualstoreID == $mapped_value[0]) {
						$importData['store_ids'] .= $mapped_value[1] . ",";
					}
				}
				
			}
		}
		//SITE 2 SITE TRANSFER ADDED ONLY END
				
		 $resource = Mage::getSingleton('core/resource');
		 $prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix'); 
		 $write = $resource->getConnection('core_write');
		 $read = $resource->getConnection('core_read');
		 $entity_id = "";
		 $status_id = "";
		 //by sku vs product_id
		 if(isset($importData['sku'])) {
			 $product = Mage::getModel('catalog/product');
			 $productId = $product->getIdBySku($importData['sku']);
		 } else {
		 	 $productId = $importData['product_id'];
		 }
		 // GETS THE STATUS ID FROM STATUS CODE
		 $get_status_id_qry = "SELECT status_id FROM ".$prefix."review_status WHERE status_code = '".trim($importData['status_code'])."'";
		 $get_status_id_rows = $read->fetchAll($get_status_id_qry);
			foreach($get_status_id_rows as $datastatusid)
			{ 
			 $status_id = $datastatusid['status_id'];
			}

/*
Array ( [form_key] => j5DO6y9RcJ5wjN2y [ratings] => Array ( [1] => 5 [3] => 15 [2] => 10 ) [validate_rating] => [status_id] => 1 [stores] => Array ( [0] => 1 ) [nickname] => Scott [title] => what a great test review [detail] => test review detail text ) 
*/		 if(isset($importData['customer_email'])) {
			 if($importData['customer_email'] !="") {
				 $valueid = Mage::getModel('core/store')->load($importData['store_id_review_is_from'])->getWebsiteId();
				//DUPLICATE CUSTOMERS are appearing after import this value above is likely not found.. so we have a little check here
				if ($valueid < 1) {
					$valueid = 1;
				}
        		$customer = Mage::getModel('customer/customer')
						->setWebsiteId($valueid)
						->loadByEmail($importData['customer_email']);
				$customerID = $customer->getId();
			 } else {
				$customerID = null;//null is for administrator only
			 }
		 } else {
			 if($importData['customer_id'] !="") {
				$customerID = $importData['customer_id'];
			 } else {
				$customerID = null;//null is for administrator only
			 }
		 }
		 
	    $storearrayofids = array();
	    $ratingarrayofvalues = array();
		
		if($importData['rating_options'] !="") {
			$delimiteddataoptions = explode(',',$importData['rating_options']);
			foreach ($delimiteddataoptions as $ratingoptionvalue) {
				if($ratingoptionvalue !="") {
					$ratingoptionarr = explode(':',$ratingoptionvalue);
					$ratingarrayofvalues[$ratingoptionarr[0]] = $ratingoptionarr[1];
				}
			}
		}
		$delimiteddata = explode(',',$importData['store_ids']);
		foreach ($delimiteddata as $individualstoreID) {
			$storearrayofids[] = $individualstoreID;
		}
		$data['created_at'] = $importData['created_at'];
		$data['ratings'] = $ratingarrayofvalues;
		$data['validate_rating'] = "";//just have this magento by default was not needed really thou
		$data['status_id'] = $status_id;
		$data['stores'] = $storearrayofids;
		$data['nickname'] = $importData['nickname'];
		$data['title'] = $importData['review_title'];
		$data['detail'] = $importData['review_detail'];
		
		#print_r($data);
        $review = Mage::getModel('review/review')->setData($data);
		$product = Mage::getModel('catalog/product')->load($productId);
		#$review->addData($data)->save();
		if($data['created_at'] != "") {
		    $review->setEntityId(1) // product
			->setEntityPkValue($productId)
			->setStoreId($product->getStoreId())
			->setStatusId($status_id)//this is handled at the top
			->setCustomerId($customerID)//null is for administrator only
			->save();
			//FOR ISSUES WITH DATE NOT SAVING 
			/*
			$date1 = $data['created_at'];
			 $date1 = str_replace('/', '-', $date1);
			
			// UPDATES CREATED AT DATE FROM CSV
			 $write->query("UPDATE `".$prefix."review` SET created_at = '".date("Y-m-d H:i:s", strtotime($date1))."' WHERE review_id = ". $review->getId() ."");
			 */
			 
			 $dateTime = strtotime($data['created_at']);	
			// UPDATES CREATED AT DATE FROM CSV
			 $write->query("UPDATE `".$prefix."review` SET created_at = '".date("Y-m-d H:i:s", $dateTime)."' WHERE review_id = ". $review->getId() ."");
		} else {
		    $review->setEntityId(1) // product
			->setEntityPkValue($productId)
			->setStoreId($product->getStoreId())
			->setStatusId($status_id)//this is handled at the top
			->setCustomerId($customerID)//null is for administrator only
			->save();
		}
		$votes = Mage::getModel('rating/rating_option_vote')
			->getResourceCollection()
			->setReviewFilter($review->getId())
			->addOptionInfo()
			->load()
			->addRatingOptions();
			
	   //this is for updating the values.. we aren't here yet
	   $select_qry2 = "SELECT ".$prefix."rating.rating_id, ".$prefix."rating_option_vote.option_id FROM ".$prefix."rating INNER JOIN ".$prefix."rating_option_vote ON ".$prefix."rating_option_vote.rating_id = ".$prefix."rating.rating_id WHERE ".$prefix."rating_option_vote.review_id = '".  $review->getId(). "'";
	   $rows2 = $read->fetchAll($select_qry2);
	   foreach($rows2 as $data2)
	    { 
			if($vote = $votes->getItemByColumnValue('rating_id', $data2['rating_id'])) {
				Mage::getModel('rating/rating')
					->setVoteId($vote->getId())
					->setReviewId($review->getId())
					->updateOptionVote($data2['option_id']);
			} 
		}

		//lets import our values
		if($importData['rating_options'] !="") {
			$delimiteddataoptions = explode(',',$importData['rating_options']);
			foreach ($delimiteddataoptions as $ratingoptionvalue) {
				if($ratingoptionvalue !="") {
				   $ratingoptionarr = explode(':',$ratingoptionvalue);
				   $select_qry3 = "SELECT option_id FROM ".$prefix."rating_option WHERE rating_id = '". $ratingoptionarr[0]. "' and value = '" . $ratingoptionarr[1] . "'";
				   $rows3 = $read->fetchAll($select_qry3);
				   foreach($rows3 as $data3)
					{ 
						Mage::getModel('rating/rating')
						->setRatingId($ratingoptionarr[0])
						->setReviewId($review->getId())
						->addOptionVote($data3['option_id'], $review->getEntityPkValue());
					}
				}
			}
		}

        $review->aggregate();
					
        return true;
    }

}

?>