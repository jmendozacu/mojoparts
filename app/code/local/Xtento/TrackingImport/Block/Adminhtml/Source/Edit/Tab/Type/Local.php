<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2013-11-11T15:52:16+01:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Source/Edit/Tab/Type/Local.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Source_Edit_Tab_Type_Local
{
    // Local Directory Configuration
    public function getFields($form)
    {
        $fieldset = $form->addFieldset('config_fieldset', array(
            'legend' => Mage::helper('xtento_trackingimport')->__('Local Directory Configuration'),
        ));

        $fieldset->addField('path', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Import Directory'),
            'name' => 'path',
            'note' => Mage::helper('xtento_trackingimport')->__('Path to the directory where import files will be searched in. Use an absolute path or specify a path relative to the Magento root directory by putting a dot at the beginning. Example to import from the var/import/ directory located in the root directory of Magento: ./var/import/  Example to import from an absolute directory: /var/www/test/ would import from the absolute path /var/www/test (and not located in the Magento installation)'),
            'required' => true
        ));
        $fieldset->addField('filename_pattern', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Filename Pattern'),
            'name' => 'filename_pattern',
            'note' => Mage::helper('xtento_trackingimport')->__('This needs to be a valid regular expression. The regular expression will be used to detect import files. The import will fail if the pattern is invalid. Example: /csv/i for all files with the csv file extension or for all files in the import directory: //'),
            'required' => true,
            'class' => 'validate-regex-pattern',
            'after_element_html' => $this->_getRegexValidatorJs()
        ));
        $fieldset->addField('archive_path', 'text', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Archive Directory'),
            'name' => 'archive_path',
            'note' => Mage::helper('xtento_trackingimport')->__('If you want to move the imported file(s) to another directory after they have been processed, please enter the path here. Use an absolute path or specify a path relative to the Magento root directory by putting a dot at the beginning. Example to move to the var/import/archive/ directory located in the root directory of Magento: ./var/import/archive/  Example to move to an absolute directory: /var/www/test/archive/ would move to the absolute path /var/www/test/archive/ (and not located in the Magento installation) This directory has to exist. Leave empty if you don\'t want to archive the import files.'),
            'required' => false,
        ));
        $fieldset->addField('delete_imported_files', 'select', array(
            'label' => Mage::helper('xtento_trackingimport')->__('Delete imported files'),
            'name' => 'delete_imported_files',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note' => Mage::helper('xtento_trackingimport')->__('Set this to "Yes" if you want to delete the imported file from the local directory after it has been processed. You can\'t delete and archive at the same time, so choose either this option or the archive option above.')
        ));
    }

    private function _getRegexValidatorJs()
    {
        $errorMsg = Mage::helper('xtento_trackingimport')->__('This is no valid regular expression. It needs to begin and end with slashes: /sample/');
        $js = <<<EOT
<script>
Validation.add('validate-regex-pattern', '{$errorMsg}', function(v) {
     if (v == "") {
        return true;
     }
     return RegExp("^\/(.*)\/","gi").test(v);
});
</script>
EOT;
        return $js;
    }
}