<?php

class Pektsekye_PartFinder_Model_Column extends Mage_Core_Model_Abstract
{

    const ENTITY = 'partfinder_column';
    
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/column');
    }

    /**
     * Init indexing process after attribute data commit
     *
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();

        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        return $this;
    }

    /**
     * Register indexing event before delete catalog eav attribute
     *
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function _beforeDelete()
    {
        Mage::getSingleton('index/indexer')->logEvent(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
        );
        return parent::_beforeDelete();
    }

    
}
