<?php



class Pektsekye_PartFinder_Model_Convert_Adapter_Db
    extends Mage_Dataflow_Model_Convert_Parser_Csv
{

    const ENTITY = 'partfinder_db_import';




    public function parse()
    {

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
          if ($v == '' || in_array($v, $fieldNames)){
            $this->addException(Mage::helper('partfinder')->__('Import failed. The first row in the import.csv file must contain unique column names.'), Mage_Dataflow_Model_Convert_Exception::FATAL);
            return;          
          } 
          $fieldNames[] = $v;
        }               				          
        
        $dbModel = Mage::getModel('partfinder/db');          
        $PfDatabaseExists = $dbModel->getPfDatabaseExists();        
        
        if ($PfDatabaseExists){
          $notFoundColumns = array_diff($dbModel->getUsedColumns(), $fieldNames);          
          if (count($notFoundColumns) > 0){
            $this->addException(Mage::helper('partfinder')->__('Import failed. There are columns (%s) used for selector or mapped that are not found in the import.csv file. Try to rename columns in your import.csv file or remove the selector and the mapped columns.', implode(', ', $notFoundColumns)), Mage_Dataflow_Model_Convert_Exception::FATAL);
            return;          
          }                      
        }
        
        
        $resource = $dbModel->getResource();        
        $resource->createTable($fieldNames);        
        
        
        $data = array();
        $countRows = 0;    
        while (($csvData = $batchIoAdapter->read(true, $fDel, $fEnc)) !== false) {
          if (count($csvData) == 1 && $csvData[0] === null) {
              continue;
          }         
          
          foreach ($fieldNames as $k => $v)
            $data[$countRows][] = isset($csvData[$k]) ? $csvData[$k] : '';
          
          if ($countRows % 1000 == 0){
            $resource->addValues($data);
            $data = array();            
          }
          
          $countRows++;        
        }


        if (count($data) > 0)
          $resource->addValues($data);

        if ($PfDatabaseExists){
        
          if (Mage::getModel('partfinder/selector_level')->getCollection()->getSize() > 0)
            Mage::getModel('partfinder/selector')->recreateSelector();

          $entity = new Varien_Object();
          Mage::getSingleton('index/indexer')->processEntityAction(
              $entity, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
          );
        }      
        
        $this->addException(Mage::helper('partfinder')->__('Imported %d rows.', $countRows));


        return $this;

    }
    
        
}
