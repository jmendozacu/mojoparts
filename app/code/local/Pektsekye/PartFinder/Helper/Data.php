<?php

class Pektsekye_PartFinder_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function saveQuery()
    {
        $query = Mage::helper('catalogsearch')->getQuery();
        /* @var $query Mage_CatalogSearch_Model_Query */

        $query->setStoreId(Mage::app()->getStore()->getId());

        if ($query->getQueryText()) {
            if (Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            } else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity()+1);
                }
                else {
                    $query->setPopularity(1);
                }

                $query->prepare();
                $query->save();
            }
        }
    }
    
    
    public function toOptions($values, $firstOptionTitle = null)
    {
      $options = array();
      
      if (!is_null($firstOptionTitle))
        $options = array(array('value' => '', 'label' => $this->__($firstOptionTitle)));
              
      foreach ($values as $value)
          $options[] = array('value' => $value, 'label' => $value); 
          
      return $options;
    }     
}
