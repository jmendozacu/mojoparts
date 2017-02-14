<?php

class Pektsekye_PartFinder_Model_Indexer_Restriction extends Mage_Index_Model_Indexer_Abstract
{
		
    /**
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION
        ),
        Mage_Catalog_Model_Convert_Adapter_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Pektsekye_PartFinder_Model_Convert_Adapter_Db::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE           
        )        
    );

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        // $this->_init('partfinder/indexer_restriction');
    }

    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('partfinder')->__('PartFinder Product Restriction');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('partfinder')->__('Rebuild PartFinder product restriction');
    }

    /**
     * Register data required by process in event object
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
       
       if (!Mage::getModel('partfinder/restriction')->attributeExists()) 
          return;
          
    	 if ($event->getEntity() == Mage_Catalog_Model_Convert_Adapter_Product::ENTITY) {

          $event->addNewData('partfinder_reindex_all', true);
    	
       } elseif ($event->getEntity() == Mage_Catalog_Model_Product::ENTITY && $event->getType() == Mage_Index_Model_Event::TYPE_SAVE) {

					$product = $event->getDataObject();

					if (!$product->getIsMassupdate() && $product->dataHasChangedFor(Pektsekye_PartFinder_Model_Restriction::ATTRIBUTE_CODE))
            $event->getProcess()->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);				
					
       } elseif ($event->getEntity() == Pektsekye_PartFinder_Model_Convert_Adapter_Db::ENTITY){
      
          $event->getProcess()->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
          
       }

    }

    /**
     * Process event
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        if (!empty($data['partfinder_reindex_all'])) {
          $this->reindexAll();
        }
    }


    public function reindexAll()
    {
        Mage::getModel('partfinder/restriction')->rebuildIndex();
    }
 
}
