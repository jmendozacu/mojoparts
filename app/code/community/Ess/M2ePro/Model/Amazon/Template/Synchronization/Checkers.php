<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Model_Amazon_Template_Synchronization_Checkers
    implements Ess_M2ePro_Model_Template_Synchronization_Checkers_Interface
{
    const CHECKER_LIST                  = 'list';
    const CHECKER_RELIST                = 'relist';
    const CHECKER_STOP                  = 'stop';
    const CHECKER_REVISE_QTY            = 'revise_qty';
    const CHECKER_REVISE_PRICE_REGULAR  = 'revise_price_regular';
    const CHECKER_REVISE_PRICE_BUSINESS = 'revise_price_business';
    const CHECKER_REVISE_DETAILS        = 'revise_details';
    const CHECKER_REVISE_IMAGES         = 'revise_images';

    //########################################

    public function getAllCheckers()
    {
        return array(
            self::CHECKER_LIST,
            self::CHECKER_RELIST,
            self::CHECKER_STOP,
            self::CHECKER_REVISE_QTY,
            self::CHECKER_REVISE_PRICE_REGULAR,
            self::CHECKER_REVISE_PRICE_BUSINESS,
            self::CHECKER_REVISE_DETAILS,
            self::CHECKER_REVISE_IMAGES,
        );
    }

    public function getReviseCheckers()
    {
        return array(
            self::CHECKER_REVISE_QTY,
            self::CHECKER_REVISE_PRICE_REGULAR,
            self::CHECKER_REVISE_PRICE_BUSINESS,
            self::CHECKER_REVISE_DETAILS,
            self::CHECKER_REVISE_IMAGES,
        );
    }

    public function getDefaultCheckers()
    {
        return array(
            self::CHECKER_LIST,
            self::CHECKER_RELIST,
            self::CHECKER_STOP,
            self::CHECKER_REVISE_QTY,
            self::CHECKER_REVISE_PRICE_REGULAR,
            self::CHECKER_REVISE_PRICE_BUSINESS,
        );
    }

    //########################################

    public function getListingAffectedCheckers()
    {
        return array(
            self::CHECKER_REVISE_DETAILS,
            self::CHECKER_REVISE_IMAGES,
        );
    }

    public function getDescriptionTemplateAffectedCheckers()
    {
        return array(
            self::CHECKER_REVISE_DETAILS,
            self::CHECKER_REVISE_IMAGES,
        );
    }

    public function getProductTaxCodeTemplateAffectedCheckers()
    {
        return array(
            self::CHECKER_REVISE_DETAILS,
        );
    }

    public function getSellingFormatTemplateAffectedCheckers()
    {
        return array(
            self::CHECKER_REVISE_QTY,
            self::CHECKER_REVISE_PRICE_REGULAR,
            self::CHECKER_REVISE_PRICE_BUSINESS,
        );
    }

    public function getShippingTemplateAffectedCheckers()
    {
        return array(
            self::CHECKER_RELIST,
            self::CHECKER_REVISE_DETAILS,
        );
    }

    //########################################

    public function getSettingKeyByChecker($checker)
    {
        $settingsKeys = $this->getCheckersSettingsKeys();

        if (!isset($settingsKeys[$checker])) {
            throw new Ess_M2ePro_Model_Exception_Logic(sprintf('Checker "%s" does not exist.', $checker));
        }

        return $settingsKeys[$checker];
    }

    public function getCheckersBySettingKey($settingKey)
    {
        $checkers = array();

        foreach ($this->getCheckersSettingsKeys() as $checkerNick => $checkerSettingKey) {
            if ($settingKey == $checkerSettingKey) {
                $checkers[] = $checkerNick;
            }
        }

        return $checkers;
    }

    // ---------------------------------------

    public function getCheckersSettingsKeys()
    {
        return array(
            self::CHECKER_LIST                  => 'list_mode',
            self::CHECKER_RELIST                => 'relist_mode',
            self::CHECKER_STOP                  => 'stop_mode',
            self::CHECKER_REVISE_QTY            => 'revise_update_qty',
            self::CHECKER_REVISE_PRICE_REGULAR  => 'revise_update_price',
            self::CHECKER_REVISE_PRICE_BUSINESS => 'revise_update_price',
            self::CHECKER_REVISE_DETAILS        => 'revise_update_details',
            self::CHECKER_REVISE_IMAGES         => 'revise_update_images',
        );
    }

    //########################################
}