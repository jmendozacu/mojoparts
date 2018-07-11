<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

class Ess_M2ePro_Helper_Component extends Mage_Core_Helper_Abstract
{
    //########################################

    public function getComponents()
    {
        return array(
            Ess_M2ePro_Helper_Component_Ebay::NICK,
            Ess_M2ePro_Helper_Component_Amazon::NICK,
        );
    }

    // ---------------------------------------

    public function getComponentsTitles()
    {
        return array(
            Ess_M2ePro_Helper_Component_Ebay::NICK   => Mage::helper('M2ePro/Component_Ebay')->getTitle(),
            Ess_M2ePro_Helper_Component_Amazon::NICK => Mage::helper('M2ePro/Component_Amazon')->getTitle(),
        );
    }

    //########################################

    public function getEnabledComponents()
    {
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isActive()) {
            $components[] = Ess_M2ePro_Helper_Component_Ebay::NICK;
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isActive()) {
            $components[] = Ess_M2ePro_Helper_Component_Amazon::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getEnabledComponentsTitles()
    {
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isEnabled()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Mage::helper('M2ePro/Component_Ebay')->getTitle();
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isEnabled()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Mage::helper('M2ePro/Component_Amazon')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getDisabledComponents()
    {
        $components = array();

        if (!Mage::helper('M2ePro/Component_Ebay')->isEnabled()) {
            $components[] = Ess_M2ePro_Helper_Component_Ebay::NICK;
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isEnabled()) {
            $components[] = Ess_M2ePro_Helper_Component_Amazon::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getDisabledComponentsTitles()
    {
        $components = array();

        if (!Mage::helper('M2ePro/Component_Ebay')->isEnabled()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Mage::helper('M2ePro/Component_Ebay')->getTitle();
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isEnabled()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Mage::helper('M2ePro/Component_Amazon')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getAllowedComponents()
    {
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isAllowed()) {
            $components[] = Ess_M2ePro_Helper_Component_Ebay::NICK;
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isAllowed()) {
            $components[] = Ess_M2ePro_Helper_Component_Amazon::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getAllowedComponentsTitles()
    {
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isAllowed()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Mage::helper('M2ePro/Component_Ebay')->getTitle();
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isAllowed()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Mage::helper('M2ePro/Component_Amazon')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getForbiddenComponents()
    {
        $components = array();

        if (!Mage::helper('M2ePro/Component_Ebay')->isAllowed()) {
            $components[] = Ess_M2ePro_Helper_Component_Ebay::NICK;
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isAllowed()) {
            $components[] = Ess_M2ePro_Helper_Component_Amazon::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getForbiddenComponentsTitles()
    {
        $components = array();

        if (!Mage::helper('M2ePro/Component_Ebay')->isAllowed()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Mage::helper('M2ePro/Component_Ebay')->getTitle();
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isAllowed()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Mage::helper('M2ePro/Component_Amazon')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getActiveComponents()
    {
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isActive()) {
            $components[] = Ess_M2ePro_Helper_Component_Ebay::NICK;
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isActive()) {
            $components[] = Ess_M2ePro_Helper_Component_Amazon::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getActiveComponentsTitles()
    {
        $components = array();

        if (Mage::helper('M2ePro/Component_Ebay')->isActive()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Mage::helper('M2ePro/Component_Ebay')->getTitle();
        }
        if (Mage::helper('M2ePro/Component_Amazon')->isActive()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Mage::helper('M2ePro/Component_Amazon')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getInactiveComponents()
    {
        $components = array();

        if (!Mage::helper('M2ePro/Component_Ebay')->isActive()) {
            $components[] = Ess_M2ePro_Helper_Component_Ebay::NICK;
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isActive()) {
            $components[] = Ess_M2ePro_Helper_Component_Amazon::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getInactiveComponentsTitles()
    {
        $components = array();

        if (!Mage::helper('M2ePro/Component_Ebay')->isActive()) {
            $components[Ess_M2ePro_Helper_Component_Ebay::NICK] = Mage::helper('M2ePro/Component_Ebay')->getTitle();
        }
        if (!Mage::helper('M2ePro/Component_Amazon')->isActive()) {
            $components[Ess_M2ePro_Helper_Component_Amazon::NICK] = Mage::helper('M2ePro/Component_Amazon')->getTitle();
        }

        return $components;
    }

    //########################################

    public function isSingleActiveComponent()
    {
        return count($this->getActiveComponents()) == 1;
    }

    //########################################

    public function getComponentTitle($component)
    {
        $title = NULL;

        switch ($component) {
            case Ess_M2ePro_Helper_Component_Ebay::NICK:
                $title = Mage::helper('M2ePro/Component_Ebay')->getTitle();
                break;
            case Ess_M2ePro_Helper_Component_Amazon::NICK:
                $title = Mage::helper('M2ePro/Component_Amazon')->getTitle();
                break;
        }

        return $title;
    }

    //########################################

    public function getComponentMode($modelName, $value, $field = NULL)
    {
        /** @var $model Ess_M2ePro_Model_Component_Parent_Abstract */
        $model = Mage::helper('M2ePro')->getModel($modelName);

        if (is_null($model) || !($model instanceof Ess_M2ePro_Model_Component_Parent_Abstract)) {
            return NULL;
        }

        $mode = $model->loadInstance($value,$field)->getData('component_mode');

        if (is_null($mode) || !in_array($mode,$this->getComponents())) {
            return NULL;
        }

        return $mode;
    }

    public function getComponentModel($mode, $modelName)
    {
        if (is_null($mode) || !in_array($mode,$this->getComponents())) {
            return NULL;
        }

        /** @var $model Ess_M2ePro_Model_Component_Parent_Abstract */
        $model = Mage::helper('M2ePro')->getModel($modelName);

        if (is_null($model) || !($model instanceof Ess_M2ePro_Model_Component_Parent_Abstract)) {
            return NULL;
        }

        $model->setChildMode($mode);

        return $model;
    }

    public function getComponentCollection($mode, $modelName)
    {
        return $this->getComponentModel($mode, $modelName)->getCollection();
    }

    // ---------------------------------------

    public function getUnknownObject($modelName, $value, $field = NULL)
    {
        $mode = $this->getComponentMode($modelName, $value, $field);

        if (is_null($mode)) {
            return NULL;
        }

        return $this->getComponentObject($mode, $modelName, $value, $field);
    }

    public function getComponentObject($mode, $modelName, $value, $field = NULL)
    {
        /** @var $model Ess_M2ePro_Model_Component_Parent_Abstract */
        $model = $this->getComponentModel($mode, $modelName);

        if (is_null($model)) {
            return NULL;
        }

        return $model->loadInstance($value, $field);
    }

    // ---------------------------------------

    public function getCachedUnknownObject($modelName, $value, $field = NULL, array $tags = array())
    {
        $mode = $this->getComponentMode($modelName, $value, $field);

        if (is_null($mode)) {
            return NULL;
        }

        return $this->getCachedComponentObject($mode, $modelName, $value, $field, $tags);
    }

    public function getCachedComponentObject($mode, $modelName, $value, $field = NULL, array $tags = array())
    {
        if (Mage::helper('M2ePro/Module')->isDevelopmentEnvironment()) {
            return $this->getComponentObject($mode,$modelName,$value,$field);
        }

        $cacheKey = strtoupper('component_'.$mode.'_'.$modelName.'_data_'.$field.'_'.$value);
        $cacheData = Mage::helper('M2ePro/Data_Cache_Permanent')->getValue($cacheKey);

        if ($cacheData !== false) {
            return $cacheData;
        }

        $tags[] = $mode;
        $tags[] = $modelName;
        $tags[] = $mode.'_'.$modelName;
        $tags = array_unique($tags);
        $tags = array_map('strtolower',$tags);

        $cacheData = $this->getComponentObject($mode,$modelName,$value,$field);

        if (!empty($cacheData)) {
            Mage::helper('M2ePro/Data_Cache_Permanent')->setValue($cacheKey,$cacheData,$tags,60*60*24);
        }

        return $cacheData;
    }

    //########################################
}