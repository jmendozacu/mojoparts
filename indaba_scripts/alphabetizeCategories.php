<?php

require_once('../app/Mage.php');
Mage::app();

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

const TABLE = 'catalog_category_entity';

function alphabetizeCategories($parentCategoryId, $conn) {
	$categories = Mage::getModel('catalog/category')->getCollection()
		->addAttributeToSelect('name')
		->addFieldToFilter('parent_id', $parentCategoryId)
		->load();

	$_categories = array();
	foreach ($categories as $category) {
		$_categories[$category->getName() . '_' . $category->getId()] = $category->getId();
	}

	// alphabetical sort
	ksort($_categories);

	$position = 1;
	foreach ($_categories as $categoryName => $categoryId) {
		$query = "UPDATE `" . TABLE . "` SET `position` = {$position} where `entity_id` = {$categoryId}";
		$conn->query($query);
		$position++;

		alphabetizeCategories($categoryId, $conn);
	}
}

$conn = Mage::getModel('core/resource')->getConnection('core_write');

$root = 1;
alphabetizeCategories($root, $conn);