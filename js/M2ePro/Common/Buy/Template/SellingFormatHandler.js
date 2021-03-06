CommonBuyTemplateSellingFormatHandler = Class.create(CommonBuyTemplateEditHandler, {

    // ---------------------------------------

    initialize: function()
    {
        this.setValidationCheckRepetitionValue('M2ePro-price-tpl-title',
                                                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                                                'Template_SellingFormat', 'title', 'id',
                                                M2ePro.formData.id,
                                                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Buy::NICK'));

        Validation.add('M2ePro-validate-price-coefficient', M2ePro.translator.translate('Coefficient is not valid.'), function(value) {

            if (value == '') {
                return true;
            }

            if (value == '0' || value == '0%') {
                return false;
            }

            return value.match(/^[+-]?\d+[.]?\d*[%]?$/g);
        });

        Validation.add('validate-qty', M2ePro.translator.translate('Wrong value. Only integer numbers.'), function(value, el) {

            if (!el.up('tr').visible()) {
                return true;
            }

            if (value.match(/[^\d]+/g)) {
                return false;
            }

            if (value <= 0) {
                return false;
            }

            return true;
        });

        Validation.add('M2ePro-validate-vat-percent', M2ePro.translator.translate('Wrong value. Must be no more than 30. Max applicable length is 6 characters, including the decimal (e.g., 12.345).'), function(value) {

            if (value.length > 6) {
                return false;
            }

            if (value < 0) {
                return false;
            }

            value = Math.ceil(value);

            return value > 0 && value <= 30;
        });
    },

    // ---------------------------------------

    duplicate_click: function($super, $headId)
    {
        this.setValidationCheckRepetitionValue('M2ePro-price-tpl-title',
                                                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                                                'Template_SellingFormat', 'title', '','',
                                                M2ePro.php.constant('Ess_M2ePro_Helper_Component_Buy::NICK'));

        $super($headId, M2ePro.translator.translate('Add Selling Format Policy.'));
    },

    // ---------------------------------------

    qty_mode_change: function()
    {
        $('qty_custom_value_tr', 'qty_percentage_tr', 'qty_modification_mode_tr').invoke('hide');

        $('qty_custom_attribute').value = '';
        if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_NUMBER')) {
            $('qty_custom_value_tr').show();
        } else if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE')) {
            BuyTemplateSellingFormatHandlerObj.updateHiddenValue(this, $('qty_custom_attribute'));
        }

        $('qty_modification_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODIFICATION_MODE_OFF');

        if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
            this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
            this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {

            $('qty_modification_mode_tr').show();

            $('qty_modification_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODIFICATION_MODE_ON');

            if (M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
                M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
                M2ePro.formData.qty_mode == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {
                $('qty_modification_mode').value = M2ePro.formData.qty_modification_mode;
            }
        }

        $('qty_modification_mode').simulate('change');

        if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT') ||
            this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_ATTRIBUTE') ||
            this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::QTY_MODE_PRODUCT_FIXED')) {

            $('qty_percentage_tr').show();
        }
    },

    qtyPostedMode_change: function()
    {
        $('qty_min_posted_value_tr').hide();
        $('qty_max_posted_value_tr').hide();

        if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODIFICATION_MODE_ON')) {
            $('qty_min_posted_value_tr').show();
            $('qty_max_posted_value_tr').show();
        }
    },

    // ---------------------------------------

    price_mode_change: function()
    {
        var self = BuyTemplateSellingFormatHandlerObj;

        $('price_custom_attribute').value = '';
        if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_SellingFormat::PRICE_MODE_ATTRIBUTE')) {
            self.updateHiddenValue(this, $('price_custom_attribute'));
        }

        $('price_note').innerHTML = M2ePro.translator.translate('Product Price for Rakuten.com Listing(s).');
    },

    // ---------------------------------------

    price_increase_vat_percent_mode_change: function()
    {
        var vatPercentTr = $('price_vat_percent_tr'),
            vatPercent = $('price_vat_percent');

        vatPercentTr.hide();
        vatPercent.removeClassName('required-entry');
        vatPercent.removeClassName('M2ePro-validate-vat-percent');

        if (+this.value) {
            vatPercentTr.show();
            vatPercent.addClassName('M2ePro-validate-vat-percent');
            vatPercent.addClassName('required-entry');
        } else {
            vatPercent.value = '';
        }
    }

    // ---------------------------------------
});
