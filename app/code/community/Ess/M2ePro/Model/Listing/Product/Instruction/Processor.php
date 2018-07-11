<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Listing_Product_Instruction_Processor
{
    private $component = NULL;

    private $maxListingsProductsCount = NULL;

    /** @var Ess_M2ePro_Model_Listing_Product_Instruction_Handler_Interface[] */
    private $handlers = array();

    //########################################

    public function setComponent($component)
    {
        $this->component = $component;
        return $this;
    }

    public function setMaxListingsProductsCount($count)
    {
        $this->maxListingsProductsCount = $count;
        return $this;
    }

    //########################################

    public function registerHandler(Ess_M2ePro_Model_Listing_Product_Instruction_Handler_Interface $handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }

    //########################################

    public function process()
    {
        $listingsProducts = $this->getNeededListingsProducts();

        $instructions = $this->loadInstructions($listingsProducts);
        if (empty($instructions)) {
            return;
        }

        foreach ($instructions as $listingProductId => $listingProductInstructions) {
            $handlerInput = Mage::getModel('M2ePro/Listing_Product_Instruction_Handler_Input');
            $handlerInput->setListingProduct($listingsProducts[$listingProductId]);
            $handlerInput->setInstructions($listingProductInstructions);

            foreach ($this->handlers as $handler) {
                $handler->process($handlerInput);

                if ($handlerInput->getListingProduct()->isDeleted()) {
                    break;
                }
            }

            Mage::getResourceModel('M2ePro/Listing_Product_Instruction')->remove(
                array_keys($listingProductInstructions)
            );
        }
    }

    //########################################

    /**
     * @param Ess_M2ePro_Model_Listing_Product[] $listingsProducts
     * @return Ess_M2ePro_Model_Listing_Product_Instruction[][]
     */
    private function loadInstructions(array $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return array();
        }

        $instructionCollection = Mage::getResourceModel('M2ePro/Listing_Product_Instruction_Collection');
        $instructionCollection->addFieldToFilter('listing_product_id', array_keys($listingsProducts));

        /** @var Ess_M2ePro_Model_Listing_Product_Instruction[] $instructions */
        $instructions = $instructionCollection->getItems();

        $instructionsByListingsProducts = array();

        foreach ($instructions as $instruction) {
            /** @var Ess_M2ePro_Model_Listing_Product $listingProduct */
            $listingProduct = $listingsProducts[$instruction->getListingProductId()];
            $instruction->setListingProduct($listingProduct);

            $instructionsByListingsProducts[$instruction->getListingProductId()][$instruction->getId()] = $instruction;
        }

        return $instructionsByListingsProducts;
    }

    /**
     * @return array
     */
    private function getNeededListingsProducts()
    {
        $resource       = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_write');

        $select = $readConnection->select()
            ->from(array('lpsi' => $resource->getTableName('m2epro_listing_product_instruction')))
            ->joinLeft(
                array('pl' => $resource->getTableName('m2epro_processing_lock')), '
                pl.object_id = lpsi.listing_product_id AND model_name = \'M2ePro/Listing_Product\''
            )
            ->where('lpsi.component = ?', $this->component)
            ->where('pl.id IS NULL')
            ->order('MAX(lpsi.priority) DESC')
            ->order('MIN(lpsi.create_date) ASC')
            ->group('lpsi.listing_product_id')
            ->limit($this->maxListingsProductsCount);

        $select->reset(Zend_Db_Select::COLUMNS);
        $select->columns('lpsi.listing_product_id');

        $ids = $readConnection->fetchCol($select);
        if (empty($ids)) {
            return array();
        }

        $listingsProductsCollection = Mage::helper('M2ePro/Component_'.$this->component)
            ->getCollection('Listing_Product');
        $listingsProductsCollection->addFieldToFilter('id', $ids);

        return $listingsProductsCollection->getItems();
    }

    //########################################
}