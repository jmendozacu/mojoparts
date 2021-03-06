<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method Ess_M2ePro_Model_Template_Description getParentObject()
 * @method Ess_M2ePro_Model_Mysql4_Ebay_Template_Description getResource()
 */
class Ess_M2ePro_Model_Ebay_Template_Description extends Ess_M2ePro_Model_Component_Child_Ebay_Abstract
{
    const TITLE_MODE_PRODUCT = 0;
    const TITLE_MODE_CUSTOM  = 1;

    const SUBTITLE_MODE_NONE     = 0;
    const SUBTITLE_MODE_CUSTOM   = 1;

    const DESCRIPTION_MODE_PRODUCT = 0;
    const DESCRIPTION_MODE_SHORT   = 1;
    const DESCRIPTION_MODE_CUSTOM  = 2;

    const CONDITION_MODE_EBAY       = 0;
    const CONDITION_MODE_ATTRIBUTE  = 1;
    const CONDITION_MODE_NONE       = 2;

    const CONDITION_EBAY_NEW                        = 1000;
    const CONDITION_EBAY_NEW_OTHER                  = 1500;
    const CONDITION_EBAY_NEW_WITH_DEFECT            = 1750;
    const CONDITION_EBAY_MANUFACTURER_REFURBISHED   = 2000;
    const CONDITION_EBAY_SELLER_REFURBISHED         = 2500;
    const CONDITION_EBAY_USED                       = 3000;
    const CONDITION_EBAY_VERY_GOOD                  = 4000;
    const CONDITION_EBAY_GOOD                       = 5000;
    const CONDITION_EBAY_ACCEPTABLE                 = 6000;
    const CONDITION_EBAY_NOT_WORKING                = 7000;

    const CONDITION_NOTE_MODE_NONE    = 0;
    const CONDITION_NOTE_MODE_CUSTOM  = 1;

    const EDITOR_TYPE_SIMPLE    = 0;
    const EDITOR_TYPE_TINYMCE   = 1;

    const CUT_LONG_TITLE_DISABLED = 0;
    const CUT_LONG_TITLE_ENABLED  = 1;

    const PRODUCT_DETAILS_MODE_NONE           = 0;
    const PRODUCT_DETAILS_MODE_DOES_NOT_APPLY = 1;
    const PRODUCT_DETAILS_MODE_ATTRIBUTE      = 2;

    const HIT_COUNTER_NONE          = 'NoHitCounter';
    const HIT_COUNTER_BASIC_STYLE   = 'BasicStyle';
    const HIT_COUNTER_GREEN_LED     = 'GreenLED';
    const HIT_COUNTER_HIDDEN_STYLE  = 'HiddenStyle';
    const HIT_COUNTER_HONESTY_STYLE = 'HonestyStyle';
    const HIT_COUNTER_RETRO_STYLE   = 'RetroStyle';

    const GALLERY_TYPE_EMPTY    = 4;
    const GALLERY_TYPE_NO       = 0;
    const GALLERY_TYPE_PICTURE  = 1;
    const GALLERY_TYPE_PLUS     = 2;
    const GALLERY_TYPE_FEATURED = 3;

    const IMAGE_MAIN_MODE_NONE       = 0;
    const IMAGE_MAIN_MODE_PRODUCT    = 1;
    const IMAGE_MAIN_MODE_ATTRIBUTE  = 2;

    const GALLERY_IMAGES_MODE_NONE      = 0;
    const GALLERY_IMAGES_MODE_PRODUCT   = 1;
    const GALLERY_IMAGES_MODE_ATTRIBUTE = 2;

    const VARIATION_IMAGES_MODE_NONE      = 0;
    const VARIATION_IMAGES_MODE_PRODUCT   = 1;
    const VARIATION_IMAGES_MODE_ATTRIBUTE = 2;

    const USE_SUPERSIZE_IMAGES_NO  = 0;
    const USE_SUPERSIZE_IMAGES_YES = 1;

    const INSTRUCTION_TYPE_MAGENTO_STATIC_BLOCK_IN_DESCRIPTION_CHANGED = 'magento_static_block_in_description_changed';

    /**
     * @var Ess_M2ePro_Model_Ebay_Template_Description_Source[]
     */
    private $descriptionSourceModels = array();

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Ebay_Template_Description');
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return Ess_M2ePro_Model_Ebay_Template_Manager::TEMPLATE_DESCRIPTION;
    }

    //########################################

    /**
     * @return bool
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)Mage::getModel('M2ePro/Ebay_Listing')
                            ->getCollection()
                            ->addFieldToFilter('template_description_mode',
                                                Ess_M2ePro_Model_Ebay_Template_Manager::MODE_TEMPLATE)
                            ->addFieldToFilter('template_description_id', $this->getId())
                            ->getSize() ||
               (bool)Mage::getModel('M2ePro/Ebay_Listing_Product')
                            ->getCollection()
                            ->addFieldToFilter('template_description_mode',
                                                Ess_M2ePro_Model_Ebay_Template_Manager::MODE_TEMPLATE)
                            ->addFieldToFilter('template_description_id', $this->getId())
                            ->getSize();
    }

    public function deleteInstance()
    {
        $temp = parent::deleteInstance();
        $temp && $this->descriptionSourceModels = array();
        return $temp;
    }

    //########################################

    /**
     * @param Ess_M2ePro_Model_Magento_Product $magentoProduct
     * @return Ess_M2ePro_Model_Ebay_Template_Description_Source
     */
    public function getSource(Ess_M2ePro_Model_Magento_Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionSourceModels[$productId])) {
            return $this->descriptionSourceModels[$productId];
        }

        $this->descriptionSourceModels[$productId] = Mage::getModel('M2ePro/Ebay_Template_Description_Source');
        $this->descriptionSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->descriptionSourceModels[$productId]->setDescriptionTemplate($this->getParentObject());

        return $this->descriptionSourceModels[$productId];
    }

    //########################################

    /**
     * @return bool
     */
    public function isCustomTemplate()
    {
        return (bool)$this->getData('is_custom_template');
    }

    //########################################

    /**
     * @return int
     */
    public function getTitleMode()
    {
        return (int)$this->getData('title_mode');
    }

    /**
     * @return bool
     */
    public function isTitleModeProduct()
    {
        return $this->getTitleMode() == self::TITLE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isTitleModeCustom()
    {
        return $this->getTitleMode() == self::TITLE_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getTitleSource()
    {
        return array(
            'mode'     => $this->getTitleMode(),
            'template' => $this->getData('title_template')
        );
    }

    /**
     * @return array
     */
    public function getTitleAttributes()
    {
        $attributes = array();
        $src = $this->getTitleSource();

        if ($src['mode'] == self::TITLE_MODE_PRODUCT) {
            $attributes[] = 'name';
        } else {
            $match = array();
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSubTitleMode()
    {
        return (int)$this->getData('subtitle_mode');
    }

    /**
     * @return bool
     */
    public function isSubTitleModeProduct()
    {
        return $this->getSubTitleMode() == self::SUBTITLE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isSubTitleModeCustom()
    {
        return $this->getSubTitleMode() == self::SUBTITLE_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getSubTitleSource()
    {
        return array(
            'mode'     => $this->getSubTitleMode(),
            'template' => $this->getData('subtitle_template')
        );
    }

    /**
     * @return array
     */
    public function getSubTitleAttributes()
    {
        $attributes = array();
        $src = $this->getSubTitleSource();

        if ($src['mode'] == self::SUBTITLE_MODE_CUSTOM) {
            $match = array();
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getDescriptionMode()
    {
        return (int)$this->getData('description_mode');
    }

    /**
     * @return bool
     */
    public function isDescriptionModeProduct()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isDescriptionModeShort()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_SHORT;
    }

    /**
     * @return bool
     */
    public function isDescriptionModeCustom()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getDescriptionSource()
    {
        return array(
            'mode'     => $this->getDescriptionMode(),
            'template' => $this->getData('description_template')
        );
    }

    /**
     * @return array
     */
    public function getDescriptionAttributes()
    {
        $attributes = array();
        $src = $this->getDescriptionSource();

        if ($src['mode'] == self::DESCRIPTION_MODE_PRODUCT) {
            $attributes[] = 'description';
        } elseif ($src['mode'] == self::DESCRIPTION_MODE_SHORT) {
            $attributes[] = 'short_description';
        } else {
            preg_match_all('/#([a-zA-Z_0-9]+?)#|#(image|media_gallery)\[.*\]#+?/', $src['template'], $match);
            !empty($match[0]) && $attributes = array_filter(array_merge($match[1], $match[2]));
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getConditionSource()
    {
        return array(
            'mode'      => (int)$this->getData('condition_mode'),
            'value'     => (int)$this->getData('condition_value'),
            'attribute' => $this->getData('condition_attribute')
        );
    }

    /**
     * @return array
     */
    public function getConditionAttributes()
    {
        $attributes = array();
        $src = $this->getConditionSource();

        if ($src['mode'] == self::CONDITION_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getConditionNoteSource()
    {
        return array(
            'mode'      => (int)$this->getData('condition_note_mode'),
            'template'  => $this->getData('condition_note_template')
        );
    }

    /**
     * @return array
     */
    public function getConditionNoteAttributes()
    {
        $attributes = array();
        $src = $this->getConditionNoteSource();

        if ($src['mode'] == self::CONDITION_NOTE_MODE_CUSTOM) {
            $match = array();
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     * @throws Ess_M2ePro_Model_Exception_Logic
     */
    public function getProductDetails()
    {
        return $this->getSettings('product_details');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isProductDetailsIncludeDescription()
    {
        $productDetails = $this->getProductDetails();
        return isset($productDetails['include_description']) ? (bool)$productDetails['include_description'] : true;
    }

    /**
     * @return bool
     */
    public function isProductDetailsIncludeImage()
    {
        $productDetails = $this->getProductDetails();
        return isset($productDetails['include_image']) ? (bool)$productDetails['include_image'] : true;
    }

    // ---------------------------------------

    /**
     * @param int $type
     * @return bool
     */
    public function isProductDetailsModeNone($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_NONE;
    }

    /**
     * @param int $type
     * @return bool
     */
    public function isProductDetailsModeDoesNotApply($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY;
    }

    /**
     * @param int $type
     * @return bool
     */
    public function isProductDetailsModeAttribute($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_ATTRIBUTE;
    }

    public function getProductDetailsMode($type)
    {
        if (!in_array($type, array('isbn', 'epid', 'upc', 'ean', 'brand', 'mpn'))) {
            throw new InvalidArgumentException('Unknown Product details name');
        }

        $productDetails = $this->getProductDetails();

        if (!is_array($productDetails) || !isset($productDetails[$type]) ||
            !isset($productDetails[$type]['mode'])) {
            return NULL;
        }

        return $productDetails[$type]['mode'];
    }

    public function getProductDetailAttribute($type)
    {
        if (!in_array($type, array('isbn', 'epid', 'upc', 'ean', 'brand', 'mpn'))) {
            throw new InvalidArgumentException('Unknown Product details name');
        }

        $productDetails = $this->getProductDetails();

        if (!is_array($productDetails) || !isset($productDetails[$type]) ||
            $this->isProductDetailsModeNone($type) || !isset($productDetails[$type]['attribute'])) {
            return NULL;
        }

        return $productDetails[$type]['attribute'];
    }

    /**
     * @return array
     */
    public function getProductDetailAttributes()
    {
        $attributes = array();

        $temp = $this->getProductDetailAttribute('isbn');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('epid');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('upc');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('ean');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('brand');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('mpn');
        $temp && $attributes[] = $temp;

        return $attributes;
    }

    //########################################

    /**
     * @return bool
     */
    public function isCutLongTitles()
    {
        return (bool)$this->getData('cut_long_titles');
    }

    public function getHitCounterType()
    {
        return $this->getData('hit_counter');
    }

    /**
     * @return array
     */
    public function getEnhancements()
    {
        return $this->getData('enhancement') ? explode(',', $this->getData('enhancement')) : array();
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getEditorType()
    {
        return (int)$this->getData('editor_type');
    }

    /**
     * @return bool
     */
    public function isEditorTypeSimple()
    {
        return $this->getEditorType() == self::EDITOR_TYPE_SIMPLE;
    }

    /**
     * @return bool
     */
    public function isEditorTypeTinyMce()
    {
        return $this->getEditorType() == self::EDITOR_TYPE_TINYMCE;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGalleryType()
    {
        return (int)$this->getData('gallery_type');
    }

    /**
     * @return bool
     */
    public function isGalleryTypeEmpty()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_EMPTY;
    }

    /**
     * @return bool
     */
    public function isGalleryTypeNo()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_NO;
    }

    /**
     * @return bool
     */
    public function isGalleryTypePicture()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_PICTURE;
    }

    /**
     * @return bool
     */
    public function isGalleryTypeFeatured()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_FEATURED;
    }

    /**
     * @return bool
     */
    public function isGalleryTypePlus()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_PLUS;
    }

    //########################################

    /**
     * @return int
     */
    public function getImageMainMode()
    {
        return (int)$this->getData('image_main_mode');
    }

    /**
     * @return bool
     */
    public function isImageMainModeNone()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isImageMainModeProduct()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isImageMainModeAttribute()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getImageMainSource()
    {
        return array(
            'mode'     => $this->getImageMainMode(),
            'attribute' => $this->getData('image_main_attribute')
        );
    }

    /**
     * @return array
     */
    public function getImageMainAttributes()
    {
        $attributes = array();
        $src = $this->getImageMainSource();

        if ($src['mode'] == self::IMAGE_MAIN_MODE_PRODUCT) {
            $attributes[] = 'image';
        } else if ($src['mode'] == self::IMAGE_MAIN_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGalleryImagesMode()
    {
        return (int)$this->getData('gallery_images_mode');
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeNone()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeProduct()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeAttribute()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGalleryImagesSource()
    {
        return array(
            'mode'     => $this->getGalleryImagesMode(),
            'attribute' => $this->getData('gallery_images_attribute'),
            'limit' => $this->getData('gallery_images_limit')
        );
    }

    /**
     * @return array
     */
    public function getGalleryImagesAttributes()
    {
        $attributes = array();
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } else if ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getVariationImagesMode()
    {
        return (int)$this->getData('variation_images_mode');
    }

    /**
     * @return bool
     */
    public function isVariationImagesModeNone()
    {
        return $this->getVariationImagesMode() == self::VARIATION_IMAGES_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isVariationImagesModeProduct()
    {
        return $this->getVariationImagesMode() == self::VARIATION_IMAGES_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isVariationImagesModeAttribute()
    {
        return $this->getVariationImagesMode() == self::VARIATION_IMAGES_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getVariationImagesSource()
    {
        return array(
            'mode'     => $this->getVariationImagesMode(),
            'attribute' => $this->getData('variation_images_attribute'),
            'limit' => $this->getData('variation_images_limit')
        );
    }

    /**
     * @return array
     */
    public function getVariationImagesAttributes()
    {
        $attributes = array();
        $src = $this->getVariationImagesSource();

        if ($src['mode'] == self::VARIATION_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } else if ($src['mode'] == self::VARIATION_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getDefaultImageUrl()
    {
        return $this->getData('default_image_url');
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getDecodedVariationConfigurableImages()
    {
        return Mage::helper('M2ePro')->jsonDecode($this->getData('variation_configurable_images'));
    }

    /**
     * @return bool
     */
    public function isVariationConfigurableImages()
    {
        $images = $this->getDecodedVariationConfigurableImages();
        return !empty($images);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isUseSupersizeImagesEnabled()
    {
        return (bool)$this->getData('use_supersize_images');
    }

    //########################################

    /**
     * @return array
     */
    public function getDefaultSettingsSimpleMode()
    {
        return array(

            'title_mode' => self::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'subtitle_mode' => self::SUBTITLE_MODE_NONE,
            'subtitle_template' => '',

            'description_mode' => self::DESCRIPTION_MODE_PRODUCT,
            'description_template' => '',

            'condition_mode' => self::CONDITION_MODE_EBAY,
            'condition_value' => self::CONDITION_EBAY_NEW,
            'condition_attribute' => '',

            'condition_note_mode' => self::CONDITION_NOTE_MODE_NONE,
            'condition_note_template' => '',

            'product_details' => Mage::helper('M2ePro')->jsonEncode(array(
                'isbn'  => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'epid'  => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'upc'   => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'ean'   => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'brand' => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'mpn'   => array('mode' => self::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY, 'attribute' => ''),
                'include_description' => 1,
                'include_image'       => 1,
            )),

            'editor_type' => self::EDITOR_TYPE_SIMPLE,
            'cut_long_titles' => self::CUT_LONG_TITLE_ENABLED,
            'hit_counter' => self::HIT_COUNTER_NONE,

            'enhancement' => '',
            'gallery_type' => self::GALLERY_TYPE_EMPTY,

            'image_main_mode' => self::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',
            'gallery_images_mode' => self::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit' => 0,
            'gallery_images_attribute' => '',
            'variation_images_mode' => self::VARIATION_IMAGES_MODE_PRODUCT,
            'variation_images_limit' => 1,
            'variation_images_attribute' => '',
            'default_image_url' => '',

            'variation_configurable_images' => Mage::helper('M2ePro')->jsonEncode(array()),
            'use_supersize_images' => self::USE_SUPERSIZE_IMAGES_NO
        );
    }

    /**
     * @return array
     */
    public function getDefaultSettingsAdvancedMode()
    {
        return $this->getDefaultSettingsSimpleMode();
    }

    //########################################

    public function save()
    {
        Mage::helper('M2ePro/Data_Cache_Permanent')->removeTagValues('template_description');
        return parent::save();
    }

    public function delete()
    {
        Mage::helper('M2ePro/Data_Cache_Permanent')->removeTagValues('template_description');
        return parent::delete();
    }

    //########################################
}