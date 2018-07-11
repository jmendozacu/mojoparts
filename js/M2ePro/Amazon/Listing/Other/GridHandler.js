AmazonListingOtherGridHandler = Class.create(ListingOtherGridHandler, {

    // ---------------------------------------

    getComponent: function()
    {
        return 'amazon';
    },

    getLogViewUrl: function(rowId)
    {
        return M2ePro.url.get('adminhtml_amazon_log/listingOther', {
            id: rowId,
            filter: base64_encode('component_mode=' + this.getComponent())
        });
    }

    // ---------------------------------------
});