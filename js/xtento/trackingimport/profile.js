function xtShowActionConditionPopup(url, field_name) {
    if ($('browser_window') && typeof(Windows) != 'undefined') {
        Windows.focus('browser_window');
        return;
    }
    var dialogWindow = Dialog.info(null, {
        closable: true,
        resizable: false,
        draggable: true,
        className: 'magento',
        windowClassName: 'popup-window',
        title: 'Action Condition: ' + field_name,
        top: 100,
        width: 650,
        height: 450,
        zIndex: 10000,
        recenterAuto: false,
        hideEffect: Element.hide,
        showEffect: Element.show,
        id: 'browser_window',
        url: url,
        onClose: function (param, el) {

        },
        onShow: function (param, el) {
            $('overlay_modal').observe('click', function () {
                Windows.closeAll();
            }); // window.parent.closeWindows();
        }
    });
}

function xtSaveHiddenData(mapperId, rowId, field, value, empty) {
    if (typeof ace !== 'undefined') {
        value = editor.getSession().getValue();
    }
    if (empty) {
        value = "";
    }
    inputName = mapperId + '[' + rowId + '][' + field + ']';
    if ($(inputName)) {
        $(inputName).value = value;
    } else {
        $(mapperId + '_additional_config').insert({ 'after': '<input type="hidden" id="' + inputName + '" name="' + inputName + '" value="' + quoteAttribute(value) + '"/>' });
    }
    Windows.closeAll();
}

function quoteAttribute(s, preserveCR) {
    preserveCR = preserveCR ? '&#13;' : '\n';
    return ('' + s)/* Forces the conversion to string. */
        .replace(/&/g, '&amp;')/* This MUST be the 1st replacement. */
        .replace(/'/g, '&apos;')/* The 4 other predefined entities, required. */
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        /*
         You may add other replacements here for HTML only
         (but it's not necessary).
         Or for XML, only if the named entities are defined in its DTD.
         */
        .replace(/\r\n/g, preserveCR)/* Must be before the next replacement. */
        .replace(/[\r\n]/g, preserveCR);
}