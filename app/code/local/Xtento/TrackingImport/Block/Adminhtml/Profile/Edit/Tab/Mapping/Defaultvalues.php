<?php

/**
 * Product:       Xtento_TrackingImport (2.1.0)
 * ID:            0CE91KDAymk40+soa+YSqrvRuUQaAJq7YPuI3zFkClU=
 * Packaged:      2015-10-07T21:36:35+00:00
 * Last Modified: 2015-04-28T18:02:26+02:00
 * File:          app/code/local/Xtento/TrackingImport/Block/Adminhtml/Profile/Edit/Tab/Mapping/Defaultvalues.php
 * Copyright:     Copyright (c) 2015 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_TrackingImport_Block_Adminhtml_Profile_Edit_Tab_Mapping_Defaultvalues extends Mage_Core_Block_Abstract
{
    public function _toHtml()
    {
        $column = $this->getColumn();

        $html = '<input type="text" id="' . $this->getInputName() . '" name="' . $this->getInputName() . '" value="#{' . $this->getColumnName() . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
            (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';

        // Is it a select or a text field?
        $html .= <<<JS
        <script>
            var default_values = {$this->getMappingId()}_possible_default_values.get($(\'select_#{_id}\').value);
            if (default_values) {
                var field = $(\'{$this->getMappingId()}[#{_id}][default_value]\').parentNode;
                field.innerHTML = \'\';
                select = document.createElement(\'select\');
                select.setAttribute(\'style\', \'width: 99.9%;\');
                select.setAttribute(\'id\', \'select_default_#{_id}\');
                select.setAttribute(\'name\', \'{$this->getInputName()}\');
                select.setAttribute(\'class\', \'select\');
                option = document.createElement(\'option\');
                optionText = document.createTextNode(\'--- Select value ---\');
                option.appendChild(optionText);
                option.setAttribute(\'value\', \'\');
                select.appendChild(option);
                \$H(default_values).each(function(pair) {
                    option = document.createElement(\'option\');
                    optionText = document.createTextNode(pair.value);
                    option.appendChild(optionText);
                    option.setAttribute(\'value\', pair.key);
                    select.appendChild(option);
                });
                field.appendChild(select);
                if ({$this->getMappingId()}_default_values[\'#{_id}\']) {
                    $(select).setValue({$this->getMappingId()}_default_values[\'#{_id}\']);
                } else {
                    if ({$this->getMappingId()}_default_value[$(\'select_#{_id}\').value]) {
                        $(select).setValue({$this->getMappingId()}_default_value[$(\'select_#{_id}\').value]);
                    }
                }
            }
        <\/script>
JS;

        return str_replace(array("\r", "\n", "\r\n"), "", $html);
    }
}
