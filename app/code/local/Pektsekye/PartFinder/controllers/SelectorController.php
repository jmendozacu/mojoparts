<?php

class Pektsekye_PartFinder_SelectorController extends Mage_Core_Controller_Front_Action
{

  public function fetchAction()
  {
    $selectedValues   = (array) $this->getRequest()->getParam('values');
    $nextColumnValues = Mage::getModel('partfinder/selector')->fetchColumnValues($selectedValues);
    
    $this->getResponse()->setBody(Zend_Json::encode($nextColumnValues));
  }

}
