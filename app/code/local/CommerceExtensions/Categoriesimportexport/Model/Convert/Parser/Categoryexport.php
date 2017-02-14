<?php
/**
 * Categoryexport.php
 * CommerceExtensions @ InterSEC Solutions LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.commerceextensions.com/LICENSE-M1.txt
 *
 * @category   Categoryexport
 * @copyright  Copyright (c) 2003-2010 CommerceExtensions @ InterSEC Solutions LLC. (http://www.commerceextensions.com)
 * @license    http://www.commerceextensions.com/LICENSE-M1.txt
 */ 
 
class CommerceExtensions_Categoriesimportexport_Model_Convert_Parser_Categoryexport extends Mage_Eav_Model_Convert_Parser_Abstract
{
/**
     * @deprecated not used anymore
     */
    public function parse()
    {
			return $this;
	}
 /**
     * Unparse (prepare data) loaded categories
     *
     * @return Mage_Catalog_Model_Convert_Adapter_Categoryexport
     */
	
    public function setCategoryRowData($rootId, $_categoriesPath, $_categorytop, $store)
	{
		$row['rootid'] = $rootId;
		$row['store'] = strtolower($store->getCode());
		#$row['store'] = strtolower($_categorytop->getStore()->getCode());
		if($this->getVar('export_categories_for_transfer') == "true") {
			$row['category_id'] = $_categorytop->getId();
			$row['name'] = $_categorytop->getName();
		}
		$row['categories'] = $_categoriesPath;
		$row['description'] = $_categorytop->getDescription();
		$row['url_key'] = $_categorytop->getUrlKey();
		$row['is_active'] = $_categorytop->getIsActive();
		$row['meta_title'] = $_categorytop->getMetaTitle();
		$row['url_path'] = $_categorytop->getUrlPath();
		$row['is_anchor'] = $_categorytop->getIsAnchor();
		$row['meta_keywords'] = $_categorytop->getMetaKeywords();
		$row['meta_description'] = $_categorytop->getMetaDescription();
		$row['display_mode'] = $_categorytop->getDisplayMode();
		$row['page_layout'] = $_categorytop->getPageLayout();
		$row['cms_block'] = $_categorytop->getLandingPage();
		$row['custom_layout_update'] = $_categorytop->getCustomLayoutUpdate();
		$row['custom_design'] = $_categorytop->getCustomDesign();
		$row['category_image'] = $_categorytop->getImage();
		$row['category_thumb_image'] = $_categorytop->getThumbnail();
		$row['include_in_menu'] = $_categorytop->getIncludeInMenu();
		$verChecksplit = explode(".",Mage::getVersion());
		// 1.7.x ONLY
		if ($verChecksplit[1] >= 7) {
			$row['custom_apply_to_products'] = $_categorytop->getCustomApplyToProducts();
			$row['custom_use_parent_settings'] = $_categorytop->getCustomUseParentSettings();
		}
		$row['position'] = $_categorytop->getPosition();
		
		//START CUSTOM CODE CATEGORY PRODUCT EXPORT
		if($this->getVar('export_products_for_categories') == "true") {
			$category_products_export = "";
			$resource = Mage::getSingleton('core/resource');
			$prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix'); 
			$read = $resource->getConnection('core_read');
			
			$select_qry = "SELECT product_id,position FROM `".$prefix."catalog_category_product` WHERE category_id ='".$_categorytop->getId()."'";
			$catrows = $read->fetchAll($select_qry);
			foreach($catrows as $catproductdata)
			{ 
				$product = Mage::getModel('catalog/product')->load($catproductdata['product_id']);
				if($this->getVar('export_product_position') == "true") {
					$category_products_export .= $product->getSku() . ":" . $catproductdata['position'] . ",";
				} else {
					$category_products_export .= $product->getSku() . ",";
				}
			}
			$row['category_products'] = substr_replace($category_products_export,"",-1);
		}
		//END CUSTOM CODE CATEGORY PRODUCT EXPORT
		
		return $row;
	}
	
    public function unparse()
    {
					if($this->getVar('categorydelimiter') !="") {
						$category_delimiter = $this->getVar('categorydelimiter');
					} else {
						$category_delimiter = "/";
					}
					#$id = 3;
					#$ids = array();
					
					if($this->getVar('rootids')!="") {
						$allrootids = explode(",", $this->getVar('rootids'));
						foreach ($allrootids as $rootId) {
						#echo "ID: " . $rootId;		
						foreach (Mage::app()->getStores(1) as $store) {		
						
						if($this->getVar('filter_by_storeid') !="") {
							if($this->getVar('filter_by_storeid') == $store->getData('store_id')) {
								#print_r($store->getData());
								//start filter by storeID
								/* Load category by id*/
						$cat = Mage::getModel('catalog/category')->load($rootId);
						$categories = Mage::getModel('catalog/category')->getCollection()
            								->setStore($store)//sets store ID
    										->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$rootId)))//first level from the tree
    										->addAttributeToSelect('*')//or any other attributes you need
    										->setOrder('position'); 
						#print_r($categories);						
						if(count($categories)) {
				    	foreach ($categories as $_categorytop) {
							
								$categories_path = $cat->getName() . $category_delimiter . $_categorytop->getName();
								$row = $this->setCategoryRowData($rootId, $categories_path, $_categorytop, $store);
								
								$batchExport = $this->getBatchExportModel()
										->setId(null)
										->setBatchId($this->getBatchModel()->getId())
										->setBatchData($row)
										->setStatus(1)
										->save();
							
				  				$subcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
										->setStore($store)//sets store ID
										->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_categorytop->getId())))//first level 
										->addAttributeToSelect('*')//or any other attributes you need
										->setOrder('position', 'asc'); 
										
								if(count($subcategoriesmodel)) {
									foreach ($subcategoriesmodel as $subcategories) {
								
									if($subcategories->getId() > 0) {
										$_sub_category = Mage::getModel('catalog/category')->load($subcategories->getId());
										
										$categories_path = $cat->getName() . $category_delimiter . $_categorytop->getName() . $category_delimiter . $_sub_category->getName();
										
										$row3 = $this->setCategoryRowData($rootId, $categories_path, $_sub_category, $store);
										
										$batchExport = $this->getBatchExportModel()
												->setId(null)
												->setBatchId($this->getBatchModel()->getId())
												->setBatchData($row3)
												->setStatus(1)
												->save();
												
												/* START OF 3rd LEVEL CATEGORY EXPORT */
											 
												$subsubcategoriesmodel = Mage::getModel('catalog/category')->load($_sub_category->getId());
												$subsubcategories = $subsubcategoriesmodel->getChildren();
												#echo "SUB CAT ID: " . $subcategories;
												foreach(explode(',',$subsubcategories) as $subsubcategoriesid)
												{
													if($subsubcategoriesid > 0) {
														$_sub_sub_category = Mage::getModel('catalog/category')->load($subsubcategoriesid);
														
														$categories_path = $cat->getName() . $category_delimiter . $_categorytop->getName() . $category_delimiter . $_sub_category->getName() . $category_delimiter . $_sub_sub_category->getName();
														
														$row4 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_category, $store);
										
														$batchExport = $this->getBatchExportModel()
																->setId(null)
																->setBatchId($this->getBatchModel()->getId())
																->setBatchData($row4)
																->setStatus(1)
																->save();
												/* START OF 4th LEVEL CATEGORY EXPORT */
												
												$subsubsubcategoriesmodel = Mage::getModel('catalog/category')->load($_sub_sub_category->getId());
												$subsubsubcategories = $subsubsubcategoriesmodel->getChildren();
												
												foreach(explode(',',$subsubsubcategories) as $subsubsubcategoriesid)
												{
													if($subsubsubcategoriesid > 0) {
														$_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubcategoriesid);
														
														$categories_path = $cat->getName() . $category_delimiter . $_categorytop->getName() . $category_delimiter . $_sub_category->getName() . $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName();
														$row5 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_category, $store);
														$batchExport = $this->getBatchExportModel()
																->setId(null)
																->setBatchId($this->getBatchModel()->getId())
																->setBatchData($row5)
																->setStatus(1)
																->save();
																
														/* START OF 5th LEVEL CATEGORY EXPORT */
														$subsubsubsubcategoriesmodel = Mage::getModel('catalog/category')->load($_sub_sub_sub_category->getId());
														$subsubsubsubcategories = $subsubsubsubcategoriesmodel->getChildren();
														foreach(explode(',',$subsubsubsubcategories) as $subsubsubsubcategoriesid)
														{
															if($subsubsubcategoriesid > 0) {
																$_sub_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubsubcategoriesid);
																
																$categories_path = $cat->getName() . $category_delimiter . $_categorytop->getName() . $category_delimiter . $_sub_category->getName() . $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_sub_category->getName();
																$row6 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_sub_category, $store);
																$batchExport = $this->getBatchExportModel()
																		->setId(null)
																		->setBatchId($this->getBatchModel()->getId())
																		->setBatchData($row6)
																		->setStatus(1)
																		->save();
															}
														}
													}
												}
											}
										}
									}
								}
							}
						} // end for each
						} else {
						
								$rootId = 2;
								$categories_path = $cat->getName();
								$row = $this->setCategoryRowData($rootId, $categories_path, $cat, $store);
								
								$batchExport = $this->getBatchExportModel()
										->setId(null)
										->setBatchId($this->getBatchModel()->getId())
										->setBatchData($row)
										->setStatus(1)
										->save();
						}	
						//end filter by storeID
						}
						} else {
						//start root categoryids
						/* Load category by id*/
						$cat = Mage::getModel('catalog/category')->load($rootId);
						$categories = Mage::getModel('catalog/category')->getCollection()
            								->setStore($store)//sets store ID
    										->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$rootId)))//first level tree
    										->addAttributeToSelect('*')//or any other attributes you need
    										->setOrder('position'); 
											
						if(count($categories)) {
				    	foreach ($categories as $_categorytop) {
								
							$categories_path = $_categorytop->getName();
							$row = $this->setCategoryRowData($rootId, $categories_path, $_categorytop, $store);
							
							$batchExport = $this->getBatchExportModel()
									->setId(null)
									->setBatchId($this->getBatchModel()->getId())
									->setBatchData($row)
									->setStatus(1)
									->save();
							
					/*Returns comma separated ids*/
				  $subcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
										->setStore($store)//sets store ID
										->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_categorytop->getId())))//first level
										->addAttributeToSelect('*')//or any other attributes you need
										->setOrder('position', 'asc'); 
										#$subcategories = $subcategoriesmodel->getChildren();
										
							if(count($subcategoriesmodel)) {
								foreach ($subcategoriesmodel as $subcategories) {
							
								if($subcategories->getId() > 0) {
									$_sub_category = Mage::getModel('catalog/category')->load($subcategories->getId());
									
									$categories_path = $_categorytop->getName() . $category_delimiter . $_sub_category->getName();
									$row3 = $this->setCategoryRowData($rootId, $categories_path, $_sub_category, $store);
									
									$batchExport = $this->getBatchExportModel()
											->setId(null)
											->setBatchId($this->getBatchModel()->getId())
											->setBatchData($row3)
											->setStatus(1)
											->save();
											
									/* START OF 3rd LEVEL CATEGORY EXPORT */
											
									$subsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
															->setStore($store)//sets store ID
															->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_category->getId())))//first level from the tree
															->addAttributeToSelect('*')//or any other attributes you need
															->setOrder('position'); 
										#echo "SUB CAT ID: " . $subcategories;
										foreach($subsubcategoriesmodel as $subsubcategoriesid)
										{
											if($subsubcategoriesid['entity_id'] > 0) {
												$_sub_sub_category = Mage::getModel('catalog/category')->load($subsubcategoriesid->getId());
												
												$categories_path = $_categorytop->getName() . $category_delimiter . $_sub_category->getName() . $category_delimiter . $_sub_sub_category->getName();
												
												$row4 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_category, $store);
												$batchExport = $this->getBatchExportModel()
														->setId(null)
														->setBatchId($this->getBatchModel()->getId())
														->setBatchData($row4)
														->setStatus(1)
														->save();
										/* START OF 4th LEVEL CATEGORY EXPORT */
														
				$subsubsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
												->setStore($store)//sets store ID
												->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_sub_category->getId())))
												->addAttributeToSelect('*')//or any other attributes you need
												->setOrder('position'); 
															
											foreach($subsubsubcategoriesmodel as $subsubsubcategoriesid)
											{
												if($subsubsubcategoriesid['entity_id'] > 0) {
													$_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubcategoriesid->getId());
													
													$categories_path = $_categorytop->getName() . $category_delimiter . $_sub_category->getName() . $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName();
													$row5 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_category, $store);
													
													$batchExport = $this->getBatchExportModel()
															->setId(null)
															->setBatchId($this->getBatchModel()->getId())
															->setBatchData($row5)
															->setStatus(1)
															->save();
																
											/* START OF 5th LEVEL CATEGORY EXPORT */
											$subsubsubsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
											->setStore($store)//sets store ID
											->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_sub_sub_category->getId())))
											->addAttributeToSelect('*')//or any other attributes you need
											->setOrder('position'); 
																			
											foreach(subsubsubsubcategoriesmodel as $subsubsubsubcategoriesid)
														{
															if($subsubsubsubcategoriesid['entity_id'] > 0) {
																$_sub_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubsubcategoriesid->getId());
																
																$categories_path = $_categorytop->getName() . $category_delimiter . $_sub_category->getName() . $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_sub_category->getName();
																
																$row6 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_sub_category, $store);
																$batchExport = $this->getBatchExportModel()
																		->setId(null)
																		->setBatchId($this->getBatchModel()->getId())
																		->setBatchData($row6)
																		->setStatus(1)
																		->save();
																}
															}
													}
												}
											}
										}
									}
								}
							}
						} // end for each
						} else {
						
								$rootId = 2;
								$categories_path = $cat->getName();
								$row = $this->setCategoryRowData($rootId, $categories_path, $cat, $store);
								
								$batchExport = $this->getBatchExportModel()
										->setId(null)
										->setBatchId($this->getBatchModel()->getId())
										->setBatchData($row)
										->setStatus(1)
										->save();
						}
						//end root categoryids
						}
						}//ends for each Mage:app()->getStores(1)
						}//ends for each $allrootids
					
					} else {
					
					foreach (Mage::app()->getStores() as $store) {
						$rootId = $store->getRootCategoryId();
						
					/* Load category by id*/
					$categories = Mage::getModel('catalog/category')->getCollection()
            						->setStore($store)//sets store ID
									->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$rootId)))//first level from the tree
									->addAttributeToSelect('*')//or any other attributes you need
									->setOrder('position'); 
												
						if(count($categories)) {
				    	foreach ($categories as $_categorytop) {
							
							$subcats = Mage::getModel('catalog/category')->getCollection()
										->setStore($store)//sets store ID
										->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_categorytop->getId())))//first level
										->addAttributeToSelect('*')//or any other attributes you need
										->setOrder('position'); 
								
							$categories_path = $_categorytop->getName();
							
							$row = $this->setCategoryRowData($rootId, $categories_path, $_categorytop, $store);
							
							$batchExport = $this->getBatchExportModel()
									->setId(null)
									->setBatchId($this->getBatchModel()->getId())
									->setBatchData($row)
									->setStatus(1)
									->save();

					foreach ($subcats as $_category)
					{
						
						$categories_path = $_categorytop->getName() . $category_delimiter . $_category->getName();
						
						$row2 = $this->setCategoryRowData($rootId, $categories_path, $_category, $store);
						
						$batchExport = $this->getBatchExportModel()
								->setId(null)
								->setBatchId($this->getBatchModel()->getId())
								->setBatchData($row2)
								->setStatus(1)
								->save();
						
						$subcategories = Mage::getModel('catalog/category')->getCollection()
								->setStore($store)//sets store ID
								->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_category->getId())))//first level 
								->addAttributeToSelect('*')//or any other attributes you need
								->setOrder('position'); 
						
						foreach ($subcategories as $_sub_category)
						{
							$subcategoriesid=1;
							if($subcategoriesid > 0) {
								
								$categories_path = $_categorytop->getName() . $category_delimiter . $_category->getName() . $category_delimiter . $_sub_category->getName();
								$row3 = $this->setCategoryRowData($rootId, $categories_path, $_sub_category, $store);
								
								$batchExport = $this->getBatchExportModel()
										->setId(null)
										->setBatchId($this->getBatchModel()->getId())
										->setBatchData($row3)
										->setStatus(1)
										->save();
										
								 /* START OF 3rd LEVEL CATEGORY EXPORT */
							  
								$subsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
														->setStore($store)//sets store ID
														->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_category->getId())))//first level from the tree
														->addAttributeToSelect('*')//or any other attributes you need
														->setOrder('position'); 
								
								foreach($subsubcategoriesmodel as $subsubcategoriesid)
								{
									if($subsubcategoriesid['entity_id'] > 0) {
										$_sub_sub_category = Mage::getModel('catalog/category')->load($subsubcategoriesid['entity_id']);
										
										$categories_path = $_categorytop->getName() . $category_delimiter . $_category->getName() . $category_delimiter . $_sub_category->getName(). $category_delimiter . $_sub_sub_category->getName();
										
										$row4 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_category, $store);
										
										$batchExport = $this->getBatchExportModel()
												->setId(null)
												->setBatchId($this->getBatchModel()->getId())
												->setBatchData($row4)
												->setStatus(1)
												->save();
												
												
											/* START OF 4th LEVEL CATEGORY EXPORT */
												 
											$subsubsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
														->setStore($store)//sets store ID
														->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_sub_category->getId())))//first level from the tree
														->addAttributeToSelect('*')//or any other attributes you need
														->setOrder('position'); 
														
											foreach($subsubsubcategoriesmodel as $subsubsubcategoriesid)
											{
												if($subsubsubcategoriesid['entity_id'] > 0) {
													$_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubcategoriesid['entity_id']);
													$categories_path = $_categorytop->getName() . $category_delimiter . $_category->getName() . $category_delimiter . $_sub_category->getName(). $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName();
													
													$row5 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_category, $store);
													$batchExport = $this->getBatchExportModel()
															->setId(null)
															->setBatchId($this->getBatchModel()->getId())
															->setBatchData($row5)
															->setStatus(1)
															->save();
																
											/* START OF 5th LEVEL CATEGORY EXPORT */
																
											$subsubsubsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
															->setStore($store)//sets store ID
															->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_sub_sub_category->getId())))//first level from the tree
															->addAttributeToSelect('*')//or any other attributes you need
															->setOrder('position'); 
															
															foreach(subsubsubsubcategoriesmodel as $subsubsubsubcategoriesid)
															{
																if($subsubsubsubcategoriesid['entity_id'] > 0) {
																	$_sub_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubsubcategoriesid['entity_id']);
																	
																	$categories_path = $_categorytop->getName() . $category_delimiter . $_category->getName() . $category_delimiter . $_sub_category->getName(). $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_sub_category->getName();
																	
																	$row6 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_sub_category, $store);
																	
																	$batchExport = $this->getBatchExportModel()
																			->setId(null)
																			->setBatchId($this->getBatchModel()->getId())
																			->setBatchData($row6)
																			->setStatus(1)
																			->save();
																					
											/* START OF 6th LEVEL CATEGORY EXPORT */
																		
															$subsubsubsubsubcategoriesmodel = Mage::getModel('catalog/category')->getCollection()
															->setStore($store)//sets store ID
															->addAttributeToFilter(array(array('attribute'=>'parent_id', 'eq'=>$_sub_sub_sub_sub_category->getId())))//first level from the tree
															->addAttributeToSelect('*')//or any other attributes you need
															->setOrder('position'); 
																		
																		
															foreach($subsubsubsubsubcategoriesmodel as $subsubsubsubsubcategoriesid)
															{
																if($subsubsubsubsubcategoriesid['entity_id'] > 0) {
																	$_sub_sub_sub_sub_sub_category = Mage::getModel('catalog/category')->load($subsubsubsubsubcategoriesid['entity_id']);
																	
																	$categories_path = $_categorytop->getName() . $category_delimiter . $_category->getName() . $category_delimiter . $_sub_category->getName(). $category_delimiter . $_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_sub_category->getName(). $category_delimiter . $_sub_sub_sub_sub_sub_category->getName();
																	$row7 = $this->setCategoryRowData($rootId, $categories_path, $_sub_sub_sub_sub_sub_category, $store);
																	$batchExport = $this->getBatchExportModel()
																			->setId(null)
																			->setBatchId($this->getBatchModel()->getId())
																			->setBatchData($row7)
																			->setStatus(1)
																			->save();
																}
															}
														}
													}
												}
											}
										}
								    }
							     }
						      }
							}
						 }
					  }
				   }
				}
        return $this;
		}
}

?>