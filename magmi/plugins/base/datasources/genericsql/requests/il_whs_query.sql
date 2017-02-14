select mvi.item_number as 'sku', mvi.qty as 'il_whs_qty'
from mojo_vendor_inventory mvi
where mvi.item_number is not null
and mvi.item_number <> ''
and mvi.vendor = 'PFG'
and mvi.warehouse='IL';
