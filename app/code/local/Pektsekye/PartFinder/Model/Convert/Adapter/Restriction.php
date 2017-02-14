<?php



class Pektsekye_PartFinder_Model_Convert_Adapter_Restriction
    extends Mage_Dataflow_Model_Convert_Parser_Csv
{


    public function parse()
    {
    
        $resModel = Mage::getModel('partfinder/restriction');

        if (!$resModel->attributeExists()){ 
          $this->addException(Mage::helper('partfinder')->__('The attribute with code "%s" does not exist or its type is not Text Area.', Pektsekye_PartFinder_Model_Restriction::ATTRIBUTE_CODE), Mage_Dataflow_Model_Convert_Exception::FATAL);
          return;
        }    
        
        setlocale(LC_ALL, Mage::app()->getLocale()->getLocaleCode().'.UTF-8');

        $fDel = $this->getVar('delimiter', ',');
        $fEnc = $this->getVar('enclose', '"');
        if ($fDel == '\t') {
            $fDel = "\t";
        }

        $batchModel = $this->getBatchModel();
        $batchIoAdapter = $this->getBatchModel()->getIoAdapter();        

        $batchIoAdapter->open(false);
        $fieldNames = array();
        foreach ($batchIoAdapter->read(true, $fDel, $fEnc) as $v) {
          $v = strtolower( preg_replace('/\s+/', '_', preg_replace('/[^\w\s]/', '', $v)));
          if ($v != '' && !in_array($v, $fieldNames)){
            $fieldNames[] = $v;
          }  
        }
               		        
        $productIds = $resModel->getAllMagentoProductIds();
        
        $data = array();
        $countRows = 0;
        $skippedRows = 0;        
        while (($csvData = $batchIoAdapter->read(true, $fDel, $fEnc)) !== false) {
          if (count($csvData) == 1 && $csvData[0] === null) {
              continue;
          }
          
          if ($skippedRows > 100){
           $this->addException(Mage::helper('partfinder')->__('Too many rows to skip. Stop import process.'), Mage_Dataflow_Model_Convert_Exception::FATAL);
           break;
          }
          
          $d = array();
          $countRows ++; $i = 0;
          foreach ($fieldNames as $field) {
              $d[$field] = isset($csvData[$i]) ? $csvData[$i] : '';
              $i ++;
          }

          if (empty($d['product_sku'])){
            $this->addException(Mage::helper('partfinder')->__('Skip import row, required field "%s" is not defined', 'product_sku'), Mage_Dataflow_Model_Convert_Exception::FATAL);
            $skippedRows++;
            continue;        
          }    

          if (!isset($productIds[$d['product_sku']])){      
            $this->addException(Mage::helper('partfinder')->__('Skip import row, the product with SKU "%s" does not exist', $d['product_sku']), Mage_Dataflow_Model_Convert_Exception::FATAL);
            $skippedRows++;
            continue;        
          } 

          $data[$productIds[$d['product_sku']]] = $d['restriction'];               
        }


        $importedRows = $countRows - $skippedRows;

        if ($importedRows > 0)             
          $resModel->updateTextRestrictions($data);          
        
          
        if ($skippedRows == 0)     
          $this->addException(Mage::helper('partfinder')->__('Imported %d rows.',$countRows));
        else 
          $this->addException(Mage::helper('partfinder')->__('Imported %d rows of %d',$importedRows,$countRows)); 
          
          
        if ($importedRows > 0){ 
          $this->addException(Mage::helper('partfinder')->__('Rebuilding index...'));          
          Mage::getModel('partfinder/restriction')->rebuildIndex();                      
        }
        
        return $this;

    }
	

	 
}
