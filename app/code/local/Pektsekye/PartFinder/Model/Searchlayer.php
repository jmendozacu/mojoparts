<?php


class Pektsekye_PartFinder_Model_Searchlayer extends Mage_CatalogSearch_Model_Layer
{

    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = Mage::getResourceModel('catalogsearch/fulltext_collection');
            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }

    
    
    public function prepareProductCollection($collection)
    {
        parent::prepareProductCollection($collection);
        
    		$collection->addIdFilter(Mage::registry('partfinder_product_ids'));
    		
        return $this;
    }

}
