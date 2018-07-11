<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Ebay_Template_Description_Source
{
    const GALLERY_IMAGES_COUNT_MAX = 11;
    const VARIATION_IMAGES_COUNT_MAX = 12;

    /**
     * @var $magentoProduct Ess_M2ePro_Model_Magento_Product
     */
    private $magentoProduct = null;

    /**
     * @var $descriptionTemplateModel Ess_M2ePro_Model_Template_Description
     */
    private $descriptionTemplateModel = null;

    //########################################

    /**
     * @param Ess_M2ePro_Model_Magento_Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(Ess_M2ePro_Model_Magento_Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return Ess_M2ePro_Model_Magento_Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param Ess_M2ePro_Model_Template_Description $instance
     * @return $this
     */
    public function setDescriptionTemplate(Ess_M2ePro_Model_Template_Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
        return $this;
    }

    /**
     * @return Ess_M2ePro_Model_Template_Description
     */
    public function getDescriptionTemplate()
    {
        return $this->descriptionTemplateModel;
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Template_Description
     */
    public function getEbayDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return string
     */
    public function getTitle()
    {
        $title = '';
        $src = $this->getEbayDescriptionTemplate()->getTitleSource();

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Ebay_Template_Description::TITLE_MODE_PRODUCT:
                $title = $this->getMagentoProduct()->getName();
                break;

            case Ess_M2ePro_Model_Ebay_Template_Description::TITLE_MODE_CUSTOM:
                $title = Mage::helper('M2ePro/Module_Renderer_Description')
                    ->parseTemplate($src['template'], $this->getMagentoProduct());
                break;

            default:
                $title = $this->getMagentoProduct()->getName();
                break;
        }

        if ($this->getEbayDescriptionTemplate()->isCutLongTitles()) {
            $title = $this->cutLongTitles($title);
        }

        return $title;
    }

    /**
     * @return string
     */
    public function getSubTitle()
    {
        $subTitle = '';
        $src = $this->getEbayDescriptionTemplate()->getSubTitleSource();

        if ($src['mode'] == Ess_M2ePro_Model_Ebay_Template_Description::SUBTITLE_MODE_CUSTOM) {

            $subTitle = Mage::helper('M2ePro/Module_Renderer_Description')
                ->parseTemplate($src['template'], $this->getMagentoProduct());

            if ($this->getEbayDescriptionTemplate()->isCutLongTitles()) {
                $subTitle = $this->cutLongTitles($subTitle, 55);
            }
        }

        return $subTitle;
    }

    /**
     * @return string
     * @throws Ess_M2ePro_Model_Exception
     */
    public function getDescription()
    {
        $description = '';
        $src = $this->getEbayDescriptionTemplate()->getDescriptionSource();
        $templateProcessor = Mage::getModel('Core/Email_Template_Filter');

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_PRODUCT:
                $description = $this->getMagentoProduct()->getProduct()->getDescription();
                $description = $templateProcessor->filter($description);
                break;

            case Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_SHORT:
                $description = $this->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $templateProcessor->filter($description);
                break;

            case Ess_M2ePro_Model_Ebay_Template_Description::DESCRIPTION_MODE_CUSTOM:
                $description = Mage::helper('M2ePro/Module_Renderer_Description')->parseTemplate(
                    $src['template'], $this->getMagentoProduct()
                );
                break;

            default:
                $description = $this->getMagentoProduct()->getProduct()->getDescription();
                $description = $templateProcessor->filter($description);
                break;
        }

        return str_replace(array('<![CDATA[', ']]>'), '', $description);
    }

    //########################################

    /**
     * @return int|string
     */
    public function getCondition()
    {
        $src = $this->getEbayDescriptionTemplate()->getConditionSource();

        if ($src['mode'] == Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_MODE_NONE) {
            return 0;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_MODE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    /**
     * @return string
     */
    public function getConditionNote()
    {
        $note = '';
        $src = $this->getEbayDescriptionTemplate()->getConditionNoteSource();

        if ($src['mode'] == Ess_M2ePro_Model_Ebay_Template_Description::CONDITION_NOTE_MODE_CUSTOM) {
            $note = Mage::helper('M2ePro/Module_Renderer_Description')->parseTemplate(
                $src['template'], $this->getMagentoProduct()
            );
        }

        return $note;
    }

    // ---------------------------------------

    public function getProductDetail($type)
    {
        if (!$this->getEbayDescriptionTemplate()->isProductDetailsModeAttribute($type)) {
            return NULL;
        }

        $attribute = $this->getEbayDescriptionTemplate()->getProductDetailAttribute($type);

        if (!$attribute) {
            return NULL;
        }

        return $this->getMagentoProduct()->getAttributeValue($attribute);
    }

    //########################################

    /**
     * @return Ess_M2ePro_Model_Magento_Product_Image|null
     */
    public function getMainImage()
    {
        $image = null;

        if ($this->getEbayDescriptionTemplate()->isImageMainModeProduct()) {
            $image = $this->getMagentoProduct()->getImage('image');
        }

        if ($this->getEbayDescriptionTemplate()->isImageMainModeAttribute()) {
            $src = $this->getEbayDescriptionTemplate()->getImageMainSource();
            $image = $this->getMagentoProduct()->getImage($src['attribute']);
        }

        return $image;
    }

    /**
     * @return Ess_M2ePro_Model_Magento_Product_Image[]
     */
    public function getGalleryImages()
    {
        if ($this->getEbayDescriptionTemplate()->isImageMainModeNone()) {
            return array();
        }

        if (!$mainImage = $this->getMainImage()) {

            $defaultImageUrl = $this->getEbayDescriptionTemplate()->getDefaultImageUrl();
            if (empty($defaultImageUrl)) {
                return array();
            }

            $image = new Ess_M2ePro_Model_Magento_Product_Image($defaultImageUrl);
            $image->setStoreId($this->getMagentoProduct()->getStoreId());

            return array($image);
        }

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeNone()) {
            return array($mainImage);
        }

        $galleryImages = array();
        $gallerySource = $this->getEbayDescriptionTemplate()->getGalleryImagesSource();
        $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeProduct()) {

            $limitGalleryImages = (int)$gallerySource['limit'];
            $galleryImagesTemp = $this->getMagentoProduct()->getGalleryImages((int)$gallerySource['limit']+1);

            foreach ($galleryImagesTemp as $image) {

                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeAttribute()) {

            $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

            $galleryImagesTemp = $this->getMagentoProduct()->getAttributeValue($gallerySource['attribute']);
            $galleryImagesTemp = (array)explode(',', $galleryImagesTemp);

            foreach ($galleryImagesTemp as $tempImageLink) {

                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                $image = new Ess_M2ePro_Model_Magento_Product_Image($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if (count($galleryImages) <= 0) {
            return array($mainImage);
        }

        foreach ($galleryImages as $key => $image) {
            /** @var Ess_M2ePro_Model_Magento_Product_Image $image */

            if ($image->getHash() == $mainImage->getHash()) {
                unset($galleryImages[$key]);
            }
        }

        $galleryImages = array_slice($galleryImages, 0, $limitGalleryImages);
        array_unshift($galleryImages, $mainImage);

        return $galleryImages;
    }

    /**
     * @return Ess_M2ePro_Model_Magento_Product_Image[]
     */
    public function getVariationImages()
    {
        if ($this->getEbayDescriptionTemplate()->isImageMainModeNone() ||
            $this->getEbayDescriptionTemplate()->isVariationImagesModeNone()) {

            return array();
        }

        $variationImages = array();
        $variationSource = $this->getEbayDescriptionTemplate()->getVariationImagesSource();
        $limitVariationImages = self::VARIATION_IMAGES_COUNT_MAX;

        if ($this->getEbayDescriptionTemplate()->isVariationImagesModeProduct()) {

            $limitVariationImages = (int)$variationSource['limit'];
            $variationImagesTemp = $this->getMagentoProduct()->getGalleryImages((int)$variationSource['limit']);

            foreach ($variationImagesTemp as $image) {

                if (array_key_exists($image->getHash(), $variationImages)) {
                    continue;
                }

                $variationImages[$image->getHash()] = $image;
            }
        }

        if ($this->getEbayDescriptionTemplate()->isVariationImagesModeAttribute()) {

            $limitVariationImages = self::VARIATION_IMAGES_COUNT_MAX;

            $variationImagesTemp = $this->getMagentoProduct()->getAttributeValue($variationSource['attribute']);
            $variationImagesTemp = (array)explode(',', $variationImagesTemp);

            foreach ($variationImagesTemp as $tempImageLink) {

                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                $image = new Ess_M2ePro_Model_Magento_Product_Image($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $variationImages)) {
                    continue;
                }

                $variationImages[$image->getHash()] = $image;
            }
        }

        if (count($variationImages) <= 0) {
            return array();
        }

        return array_slice($variationImages, 0, $limitVariationImages);
    }

    //########################################

    /**
     * @param string $str
     * @param int $length
     * @return string
     */
    private function cutLongTitles($str, $length = 80)
    {
        $str = trim($str);

        if ($str === '' || strlen($str) <= $length) {
            return $str;
        }

        return Mage::helper('core/string')->truncate($str, $length, '');
    }

    //########################################
}