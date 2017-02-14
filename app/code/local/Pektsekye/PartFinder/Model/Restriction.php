<?php

class Pektsekye_PartFinder_Model_Restriction extends Mage_Core_Model_Abstract
{        

		const ATTRIBUTE_CODE = 'partfinder_restriction';
		
		
    protected $_attribute;


    public function _construct()
    {
        parent::_construct();
        $this->_init('partfinder/restriction');
    }


    public function getProductIds($rowId)
    {
      return $this->getResource()->getProductIds($rowId);
    }




    public function rebuildIndex()
    {   
			  $this->getResource()->emptyTable();
			  
        if (!$this->attributeExists())
          return;
          
      	$productIdsByPhrase = $this->getProductIdsByPhrase();          
        if (count($productIdsByPhrase) == 0)
          return;						
      
        $phrases = array_keys($productIdsByPhrase);        
        
        $rowIdsByPhrase = $this->getRowIdsByPhrase($phrases);            
        if (count($rowIdsByPhrase) == 0)
          return;                        

        $productIdsByRowId = $this->getProductIdsByRowId($productIdsByPhrase, $rowIdsByPhrase);        
						         
        $this->getResource()->insertIds($productIdsByRowId);    
  
    }

    
    
    
    public function getProductIdsByPhrase()
    {
      	  
        $productIdsByPhrase = array();    
    
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()           
            ->addAttributeToFilter(self::ATTRIBUTE_CODE, array(array('neq'=>'')));
 
        if ($productCollection->getSize() == 0)	
          return array();
        
        foreach($productCollection as $product){        
          $phrases = $this->getProductPhrases($product->getData(self::ATTRIBUTE_CODE));          
          foreach ($phrases as $phrase)              
            $productIdsByPhrase[$phrase][] = $product->getId();        						                             
        }
        
        return $productIdsByPhrase;
    } 
    
    
    
    

    public function getRowIdsByPhrase($phrases)
    {
        $levelCount = Mage::getModel('partfinder/selector_level')->getCollection()->getSize();
        
        if ($levelCount == 0)
          return array();            
        
        $rowValues  = Mage::getResourceModel('partfinder/selector')->getRowValues($levelCount);	 

        $rowIdsByValue     = array();
        $valuesByFirstWord = array();
        
        foreach ($rowValues as $key => $row){
          $value = '';
          for ($i=0;$i<$levelCount;$i++){
            $value .= ($value != '' ? ' ' : '') . $row['level_'.$i];
            $rowIdsByValue[$value][] = $row['id'];  
          }
          
          $rowIdsByValue['all'][] = $row['id'];
          
          $words = explode(' ', $row['level_0']);
          $valuesByFirstWord[$words[0]][$row['id']] = $value;
          
          unset($rowValues[$key]);
        }

        $rowIdsByPhrase = array();
        foreach($phrases as $phrase){
          $rowIds = $this->matchRows($rowIdsByValue, $valuesByFirstWord, $phrase);
          if (count($rowIds) != 0)
            $rowIdsByPhrase[$phrase] = $rowIds;  
        }       
       
       return $rowIdsByPhrase;   
    }     
    
    
    
    
    
    
    public function getProductIdsByRowId($productIdsByPhrase, $rowIdsByPhrase)
    {  
        $productIdsByRowId = array();        
        foreach($rowIdsByPhrase as $phrase => $rowIds){
          foreach ($rowIds as $rowId)
            $productIdsByRowId[$rowId] = isset($productIdsByRowId[$rowId]) ? array_merge($productIdsByPhrase[$phrase], $productIdsByRowId[$rowId]) : $productIdsByPhrase[$phrase];                             
        }
	                 
        foreach ($productIdsByRowId as $rowId => $productIds)
          $productIdsByRowId[$rowId] = array_unique($productIds);
          
        return $productIdsByRowId; 
    }


   
   
   
    public function getProductPhrases($restriction)
    {
        $restriction = preg_replace('/\s{2,}/', ' ', $restriction);
        $restriction = strtolower($restriction);
        
        $phrases = explode(',', $restriction);
        foreach ($phrases as $k => $phrase){              
          $phrases[$k] = trim($phrase);
          if ($phrases[$k] == '')
            unset($phrases[$k]);        						    
        }  
     
        return array_unique($phrases);        
    }
    
    
    
    
    
    public function matchRows($rowIdsByValue, $valuesByFirstWord, $phrase)
    {
        $rowIds = array();
        
        if (isset($rowIdsByValue[$phrase])){
        
          $rowIds = $rowIdsByValue[$phrase];	
          
        } else {
        
          $words = explode(' ', $phrase);
          $firstWord = $words[0];
          
          if (isset($valuesByFirstWord[$firstWord]))
            $rowIds = $this->pregMatchRows($valuesByFirstWord[$firstWord], $phrase);
            
        }
        
       return $rowIds; 
    }
  
  
  
  
  
  
    public function pregMatchRows($values, $phrase)
    {
  
      $rowIds = array();
      
      if (Mage::getStoreConfig('partfinder/backendsettings/matchyearrange') == 1){        
        $result = $this->matchYearRange($phrase);        
        if (!is_null($result)){                     
          $yearPattern = '(' . implode('|', range($result[1], $result[2])) .'|'. $result[0] . ')';
          $phrase = str_replace($result[0], 'YEAR', $phrase);
        }
      }
      
      $pattern = preg_replace("/\s+/", '.+', preg_replace("/[^\w\s]/", '\W', $phrase));  
      
      if (isset($yearPattern))
        $pattern = str_replace('YEAR', $yearPattern, $pattern);
      
      foreach ($values as $rowId => $value){      
        if (preg_match("/{$pattern}/", $value))  
          $rowIds[] = $rowId;                             
      }       
    
      return $rowIds;
      
    }





    public function matchYearRange($phrase)
    { 
          	
      $count = preg_match("/(^|\s)((\d\d\d\d)\s*-\s*(\d\d\d\d)|(\d\d)\s*-\s*(\d\d))($|\s)/", $phrase, $matches);

      if ($count != 1)
        return null;
        
      if ($matches[3] != ''){
        $yb = (int) $matches[3];
        $ye = (int) $matches[4];
      } else {
        $yb = (int) ((int) $matches[5] < 20 ? '20' . $matches[5] : '19' . $matches[5]);
        $ye = (int) ((int) $matches[6] < 20 ? '20' . $matches[6] : '19' . $matches[6]);
      }        
      
      if ($yb > $ye || $yb < 1950 || $ye > 2020)
        return null;        

      return array($matches[2], $yb, $ye);
      
    }
    
    
    
    
    public function getAllMagentoProductIds()
    { 	
      return $this->getResource()->getAllMagentoProductIds();
    }
    
        
    
    public function updateTextRestrictions($data)
    {     
      $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();      
      $this->getResource()->updateTextRestrictions($entityTypeId, $this->getAttributeId(), $data);      
    }  
 
 
     public function getRestrictionData()
    {
      return $this->getResource()->getRestrictionData($this->getAttributeId());
    } 
  
 
 
     public function getAttribute()
    {   
      if (!isset($this->_attribute)){
        $entityTypeId      = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();     
        $this->_attribute  = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($entityTypeId, self::ATTRIBUTE_CODE);
      }
      
      return $this->_attribute;     	        
    } 
    
    
    
     public function getAttributeId()
    {    
      return $this->attributeExists() ? $this->getAttribute()->getId() : null;     	        
    }  
    
    
    
     public function attributeExists()
    {    
      return !is_null($this->getAttribute()->getId()) && $this->getAttribute()->getFrontendInput() == 'textarea';     	        
    } 
    
    
}
