<?php

class Pektsekye_PartFinder_Block_Layer extends Mage_CatalogSearch_Block_Layer
{
    public function getLayer()
    {
        return Mage::registry('partfinder_layer');
    }
}
