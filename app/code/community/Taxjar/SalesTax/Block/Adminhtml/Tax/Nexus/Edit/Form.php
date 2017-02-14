<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Taxjar_SalesTax_Block_Adminhtml_Tax_Nexus_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('nexus_form');
        $this->setTemplate('taxjar/nexus/form.phtml');
    }

    protected function _prepareForm()
    {
        $model  = Mage::registry('taxjar/tax_nexus');
        $form   = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getData('action'),
            'method'    => 'post'
        ));
        
        $countries = Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
        unset($countries[0]);
        
        if (!$model->hasCountryId()) {
            $model->setCountryId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_COUNTRY));
        }

        if (!$model->hasRegionId()) {
            $model->setRegionId(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_REGION));
        }
        
        $regionCollection = Mage::getModel('directory/region')->getCollection()->addCountryFilter($model->getCountryId());
        $regions = $regionCollection->toOptionArray();

        $this->setTitle('Nexus Address Information');

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => 'Nexus Address Information'
        ));
        
        if ($model->getId() > 0) {
            $fieldset->addField('id', 'hidden', array(
                'name'  => 'id',
                'value' => $model->getId()
            ));

            $fieldset->addField('api_id', 'hidden', array(
                'name'  => 'api_id',
                'value' => $model->getApiId()
            ));
            
            $fieldset->addField('region', 'hidden', array(
                'name'  => 'region',
                'value' => $model->getRegion()
            ));
        }

        $fieldset->addField('street', 'text',
            array(
                'name'     => 'street',
                'label'    => Mage::helper('taxjar')->__('Street Address'),
                'class'    => 'required-entry',
                'value'    => $model->getStreet(),
                'required' => true
            )
        );
        
        $fieldset->addField('city', 'text',
            array(
                'name'     => 'city',
                'label'    => Mage::helper('taxjar')->__('City'),
                'class'    => 'required-entry',
                'value'    => $model->getCity(),
                'required' => true
            )
        );
        
        $fieldset->addField('country_id', 'select',
            array(
                'name'               => 'country_id',
                'label'              => Mage::helper('taxjar')->__('Country'),
                'class'              => 'required-entry',
                'values'             => $countries,
                'required'           => true,
                'after_element_html' => '<p class="note"><span>TaxJar provides sales tax calculations for <a href="http://developers.taxjar.com/api/reference/#countries" target="_blank">more than 30 countries</a> around the world. Sales tax reporting and filing is currently offered in the US.</span></p>'
            )
        );

        $fieldset->addField('region_id', 'select',
            array(
                'name'     => 'region_id',
                'label'    => Mage::helper('taxjar')->__('State/Region'),
                'values'   => $regions
            )
        );
        
        $fieldset->addField('postcode', 'text',
            array(
                'name'     => 'postcode',
                'label'    => Mage::helper('taxjar')->__('Zip/Post Code'),
                'class'    => 'required-entry',
                'value'    => $model->getPostcode(),
                'required' => true
            )
        );

        $form->setAction($this->getUrl('*/tax_nexus/save'));
        $form->setUseContainer(true);
        $form->setMethod('post');
        
        $nexusData = $model->getData();
        $form->setValues($nexusData);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
