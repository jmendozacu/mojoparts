<?php



class Pektsekye_PartFinder_Model_Convert_Parser_Restriction extends Mage_Dataflow_Model_Convert_Parser_Csv
{

   public function unparse()
    {
        $resModel = Mage::getModel('partfinder/restriction');
        
        if (!$resModel->attributeExists()){ 
          $this->addException(Mage::helper('partfinder')->__('The attribute with code "%s" does not exist or its type is not Text Area.', Pektsekye_PartFinder_Model_Restriction::ATTRIBUTE_CODE), Mage_Dataflow_Model_Convert_Exception::FATAL);
          return;
        }   
        
        $io = $this->getBatchModel()->getIoAdapter();
        $io->open();
              
        $fieldList = array(				
		      'product_sku',
		      'restriction'		    					  					  			  			  		
        );
              
        $io->write($this->getCsvString($fieldList));
        
        $data = $resModel->getRestrictionData();  

        while ($row = $data->fetch())
          $io->write($this->getCsvString($row));	

      $io->close();
      
      return $this;
	 }


}
