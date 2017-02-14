<?php


class Pektsekye_PartFinder_Block_Product_List extends Mage_Catalog_Block_Product_Abstract
{
    protected $_productCollection;

    protected function _prepareLayout()
    {
        $title = $this->getHeaderText();
        $this->getLayout()->getBlock('head')->setTitle($title);
        $this->getLayout()->getBlock('root')->setHeaderTitle($title);
        return parent::_prepareLayout();
    }

    public function setListOrders() {
        $this->getChild('search_result_list')
            ->setAvailableOrders(array(
                'name' => Mage::helper('tag')->__('Name'),
                'price' => Mage::helper('tag')->__('Price'))
            );
    }

    public function setListModes() {
        $this->getChild('search_result_list')
            ->setModes(array(
                'grid' => Mage::helper('catalogsearch')->__('Grid'),
                'list' => Mage::helper('catalogsearch')->__('List'))
            );
    }

    public function setListCollection() {
        $this->getChild('search_result_list')
           ->setCollection($this->_getProductCollection());
    }

    public function getProductListHtml()
    {
        return $this->getChildHtml('search_result_list');
    }
    
    public function getLayer()
    {
        return Mage::registry('partfinder_layer');
    }


    
    protected function _getProductCollection()
    {
      $collection = $this->getLayer()->getProductCollection();

      return $collection; 
    }

    

    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
          $collection = $this->_getProductCollection();
          $this->setResultCount($collection->getSize());
        }
        return $this->getData('result_count');
    }

    public function getHeaderText()
    {
      $text = implode(' ', Mage::registry('partfinder_selected_values'));
      
	    $searchQuery = $this->getRequest()->getParam(Mage::helper('catalogsearch')->getQueryParamName());
		  $category = Mage::registry('partfinder_applied_category');
		  
		  if (!is_null($searchQuery)){
		    $text .= " '{$searchQuery}'"; 
		  } elseif (!is_null($category)){
		    $text .= ", {$category->getName()}"; 
		  }      
  		return $this->htmlEscape($text);      
    }

    public function getSubheaderText()
    {
        return false;
    }

    public function getNoResultText()
    {
        return Mage::helper('tag')->__('No matches found.');
    }
}
