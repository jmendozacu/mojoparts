<?php

class CommerceExtensions_Sitetransferimportexport_Model_Profile extends Mage_Dataflow_Model_Profile
{
    protected function _construct()
    {
        $this->_init('dataflow/profile');
    }
    
    public function _parseGuiData()
    {
        $nl = "\r\n";
        $import = $this->getDirection()==='import';
        $p = $this->getGuiData();

        if ($this->getDataTransfer()==='interactive') {
//            $p['file']['type'] = 'file';
//            $p['file']['filename'] = $p['interactive']['filename'];
//            $p['file']['path'] = 'var/export';

            $interactiveXml = '<action type="dataflow/convert_adapter_http" method="'
                . ($import ? 'load' : 'save') . '">' . $nl;
            #$interactiveXml .= '    <var name="filename"><![CDATA['.$p['interactive']['filename'].']]></var>'.$nl;
            $interactiveXml .= '</action>';

            $fileXml = '';
        } else {
            $interactiveXml = '';

            $fileXml = '<action type="dataflow/convert_adapter_io" method="'
                . ($import ? 'load' : 'save') . '">' . $nl;
            $fileXml .= '    <var name="type">' . $p['file']['type'] . '</var>' . $nl;
            $fileXml .= '    <var name="path">' . $p['file']['path'] . '</var>' . $nl;
            $fileXml .= '    <var name="filename"><![CDATA[' . $p['file']['filename'] . ']]></var>' . $nl;
   			#$fileXml .= '    <var name="link">/export/download.php?download_file=' . $p['file']['filename'] . '</var>' . $nl;
			
            if ($p['file']['type']==='ftp') {
                $hostArr = explode(':', $p['file']['host']);
                $fileXml .= '    <var name="host"><![CDATA[' . $hostArr[0] . ']]></var>' . $nl;
                if (isset($hostArr[1])) {
                    $fileXml .= '    <var name="port"><![CDATA[' . $hostArr[1] . ']]></var>' . $nl;
                }
                if (!empty($p['file']['passive'])) {
                    $fileXml .= '    <var name="passive">true</var>' . $nl;
                }
                if ((!empty($p['file']['file_mode']))
                        && ($p['file']['file_mode'] == FTP_ASCII || $p['file']['file_mode'] == FTP_BINARY)
                ) {
                    $fileXml .= '    <var name="file_mode">' . $p['file']['file_mode'] . '</var>' . $nl;
                }
                if (!empty($p['file']['user'])) {
                    $fileXml .= '    <var name="user"><![CDATA[' . $p['file']['user'] . ']]></var>' . $nl;
                }
                if (!empty($p['file']['password'])) {
                    $fileXml .= '    <var name="password"><![CDATA[' . $p['file']['password'] . ']]></var>' . $nl;
                }
            }
            if ($import) {
                $fileXml .= '    <var name="format"><![CDATA[' . $p['parse']['type'] . ']]></var>' . $nl;
            }
            $fileXml .= '</action>' . $nl . $nl;
        }

        switch ($p['parse']['type']) {
            case 'excel_xml':
                $parseFileXml = '<action type="dataflow/convert_parser_xml_excel" method="'
                    . ($import ? 'parse' : 'unparse') . '">' . $nl;
                $parseFileXml .= '    <var name="single_sheet"><![CDATA['
                    . ($p['parse']['single_sheet'] !== '' ? $p['parse']['single_sheet'] : '')
                    . ']]></var>' . $nl;
                break;

            case 'csv':
                $parseFileXml = '<action type="dataflow/convert_parser_csv" method="'
                    . ($import ? 'parse' : 'unparse') . '">' . $nl;
                $parseFileXml .= '    <var name="delimiter"><![CDATA['
                    . $p['parse']['delimiter'] . ']]></var>' . $nl;
                $parseFileXml .= '    <var name="enclose"><![CDATA['
                    . $p['parse']['enclose'] . ']]></var>' . $nl;
                break;
        }
        $parseFileXml .= '    <var name="fieldnames">' . $p['parse']['fieldnames'] . '</var>' . $nl;
        $parseFileXmlInter = $parseFileXml;
        $parseFileXml .= '</action>' . $nl . $nl;

        $mapXml = '';

        if (isset($p['map']) && is_array($p['map'])) {
            foreach ($p['map'] as $side=>$fields) {
                if (!is_array($fields)) {
                    continue;
                }
				if(isset($fields['db'])) {
					foreach ($fields['db'] as $i=>$k) {
						if ($k=='' || $k=='0') {
							unset($p['map'][$side]['db'][$i]);
							unset($p['map'][$side]['file'][$i]);
						}
					}
                }
            }
        }
        $mapXml .= '<action type="dataflow/convert_mapper_column" method="map">' . $nl;
        $map = $p['map'][$this->getEntityType()];
		if(isset($fields['db'])) {
			if (sizeof($map['db']) > 0) {
				$from = $map[$import?'file':'db'];
				$to = $map[$import?'db':'file'];
				$mapXml .= '    <var name="map">' . $nl;
				$parseFileXmlInter .= '    <var name="map">' . $nl;
				foreach ($from as $i=>$f) {
					$mapXml .= '        <map name="' . $f . '"><![CDATA[' . $to[$i] . ']]></map>' . $nl;
					$parseFileXmlInter .= '        <map name="' . $f . '"><![CDATA[' . $to[$i] . ']]></map>' . $nl;
				}
				$mapXml .= '    </var>' . $nl;
				$parseFileXmlInter .= '    </var>' . $nl;
			}
		}
        if ($p['map']['only_specified']) {
            $mapXml .= '    <var name="_only_specified">' . $p['map']['only_specified'] . '</var>' . $nl;
            //$mapXml .= '    <var name="map">' . $nl;
            $parseFileXmlInter .= '    <var name="_only_specified">' . $p['map']['only_specified'] . '</var>' . $nl;
        }
        $mapXml .= '</action>' . $nl . $nl;

        $parsers = array(
            'product'=>'commerceextensions_productimportexport/convert_parser_productexport',
            'attribute'=>'commerceextensions_productattributesimportexport/convert_parser_ProductAttributesexport',
            'order'=>'commerceextensions_orderimportexport/convert_parser_exportorders',
            'category'=>'commerceextensions_categoriesimportexport/convert_parser_categoryexport',
            'customer'=>'commerceextensions_advancedcustomerimportexport/convert_parser_customerexport',
            'customerreviews'=>'commerceextensions_customerreviewsimportexport/convert_parser_customerreviewexport',
            'couponcode'=>'commerceextensions_shoppingcartrulesimportexport/convert_parser_couponcodeexport',
        );

        if ($import) {
//            if ($this->getDataTransfer()==='interactive') {
                $parseFileXmlInter .= '    <var name="store"><![CDATA[' . $this->getStoreId() . ']]></var>' . $nl;
//            } else {
//                $parseDataXml = '<action type="' . $parsers[$this->getEntityType()] . '" method="parse">' . $nl;
//                $parseDataXml = '    <var name="store"><![CDATA[' . $this->getStoreId() . ']]></var>' . $nl;
//                $parseDataXml .= '</action>'.$nl.$nl;
//            }
//            $parseDataXml = '<action type="'.$parsers[$this->getEntityType()].'" method="parse">'.$nl;
//            $parseDataXml .= '    <var name="store"><![CDATA['.$this->getStoreId().']]></var>'.$nl;
//            $parseDataXml .= '</action>'.$nl.$nl;
        } else {
            $parseDataXml = '<action type="' . $parsers[$this->getEntityType()] . '" method="unparse">' . $nl;
            $parseDataXml .= '    <var name="store"><![CDATA[' . $this->getStoreId() . ']]></var>' . $nl;
            if (isset($p['export']['add_url_field'])) {
                $parseDataXml .= '    <var name="url_field"><![CDATA['
                    . $p['export']['add_url_field'] . ']]></var>' . $nl;
            }
            if($this->getEntityType() == "order") {
				// Custom fields            
				if (isset($p['unparse']['date_from']) && strlen($p['unparse']['date_from']) > 0)
				{
					$parseDataXml .= '    <var name="date_from"><![CDATA[' . $p['unparse']['date_from'] . ']]></var>' . $nl;
				}
				
				if (isset($p['unparse']['date_to']) && strlen($p['unparse']['date_to']) > 0)
				{
					$parseDataXml .= '    <var name="date_to"><![CDATA[' . $p['unparse']['date_to'] . ']]></var>' . $nl;
				}
				
				$recordLimit = isset($p['unparse']['recordlimit']) ? $p['unparse']['recordlimit'] : '0';
				$parseDataXml .= '    <var name="recordlimit"><![CDATA[' . $recordLimit . ']]></var>' . $nl;
				
				if (isset($p['unparse']['filter_by_order_status']) && strlen($p['unparse']['filter_by_order_status']) > 0)
				{
					$parseDataXml .= '    <var name="filter_by_order_status"><![CDATA[' . $p['unparse']['filter_by_order_status'] . ']]></var>' . $nl;
				}
				
				$exportProductPricing = isset($p['unparse']['export_product_pricing']) ? $p['unparse']['export_product_pricing'] : 'false';
				$parseDataXml .= '    <var name="export_product_pricing"><![CDATA[' . $exportProductPricing . ']]></var>' . $nl;
			}
			
			if($this->getEntityType() == "product") {
				// Custom fields
				
				$recordLimitStart = isset($p['unparse']['productrecordlimitstart']) ? $p['unparse']['productrecordlimitstart'] : '0';
				$parseDataXml .= '    <var name="recordlimitstart"><![CDATA[' . $recordLimitStart . ']]></var>' . $nl;
				
				$recordLimitEnd = isset($p['unparse']['productrecordlimitend']) ? $p['unparse']['productrecordlimitend'] : '100';
				$parseDataXml .= '    <var name="recordlimitend"><![CDATA[' . $recordLimitEnd . ']]></var>' . $nl;
				
				$exportGroupedPosition = isset($p['unparse']['export_grouped_position']) ? $p['unparse']['export_grouped_position'] : 'false';
				$parseDataXml .= '    <var name="export_grouped_position"><![CDATA[' . $exportGroupedPosition . ']]></var>' . $nl;
				
				$exportRelatedPosition = isset($p['unparse']['export_related_position']) ? $p['unparse']['export_related_position'] : 'false';
				$parseDataXml .= '    <var name="export_related_position"><![CDATA[' . $exportRelatedPosition . ']]></var>' . $nl;
				
				$exportCrossellPosition = isset($p['unparse']['export_crossell_position']) ? $p['unparse']['export_crossell_position'] : 'false';
				$parseDataXml .= '    <var name="export_crossell_position"><![CDATA[' . $exportCrossellPosition . ']]></var>' . $nl;
				
				$exportUpsellPosition = isset($p['unparse']['export_upsell_position']) ? $p['unparse']['export_upsell_position'] : 'false';
				$parseDataXml .= '    <var name="export_upsell_position"><![CDATA[' . $exportUpsellPosition . ']]></var>' . $nl;
				
				$exportCategoryPaths = isset($p['unparse']['export_category_paths']) ? $p['unparse']['export_category_paths'] : 'false';
				$parseDataXml .= '    <var name="export_category_paths"><![CDATA[' . $exportCategoryPaths . ']]></var>' . $nl;
				
				$exportFullImagePaths = isset($p['unparse']['export_full_image_paths']) ? $p['unparse']['export_full_image_paths'] : 'false';
				$parseDataXml .= '    <var name="export_full_image_paths"><![CDATA[' . $exportFullImagePaths . ']]></var>' . $nl;
				
				$export_multi_store = isset($p['unparse']['export_multi_store']) ? $p['unparse']['export_multi_store'] : 'false';
				$parseDataXml .= '    <var name="export_multi_store"><![CDATA[' . $export_multi_store . ']]></var>' . $nl;
            }
			if($this->getEntityType() == "attribute") {
				// Custom fields
				$entitytypeid = isset($p['unparse']['entitytypeid']) ? $p['unparse']['entitytypeid'] : '10';
				$parseDataXml .= '    <var name="entitytypeid"><![CDATA[' . $entitytypeid . ']]></var>' . $nl;
				
				#$export_recordlimit = isset($p['unparse']['recordlimit']) ? $p['unparse']['recordlimit'] : '100';
				$parseDataXml .= '    <var name="recordlimit"><![CDATA[99999999999999999]]></var>' . $nl;
				
				$export_w_sort_order = isset($p['unparse']['export_w_sort_order']) ? $p['unparse']['export_w_sort_order'] : 'false';
				$parseDataXml .= '    <var name="export_w_sort_order"><![CDATA[' . $export_w_sort_order . ']]></var>' . $nl;
				
				$attribute_options_delimiter = isset($p['unparse']['attribute_options_delimiter']) ? $p['unparse']['attribute_options_delimiter'] : '|';
				$parseDataXml .= '    <var name="attribute_options_delimiter"><![CDATA[' . $attribute_options_delimiter . ']]></var>' . $nl;
				
				$attribute_options_value_delimiter = isset($p['unparse']['attribute_options_value_delimiter']) ? $p['unparse']['attribute_options_value_delimiter'] : ',';
				$parseDataXml .= '    <var name="attribute_options_value_delimiter"><![CDATA[' . $attribute_options_value_delimiter . ']]></var>' . $nl;
				
			}
			if($this->getEntityType() == "category") {
			
				// Custom fields
				$categorydelimiter = isset($p['unparse']['rootids']) ? $p['unparse']['rootids'] : '2';
				$parseDataXml .= '    <var name="rootids"><![CDATA[' . $categorydelimiter . ']]></var>' . $nl;
				
				$categorydelimiter = isset($p['unparse']['categorydelimiter']) ? $p['unparse']['categorydelimiter'] : '/';
				$parseDataXml .= '    <var name="categorydelimiter"><![CDATA[' . $categorydelimiter . ']]></var>' . $nl;
				
				$export_categories_for_transfer = isset($p['unparse']['export_categories_for_transfer']) ? $p['unparse']['export_categories_for_transfer'] : 'false';
				$parseDataXml .= '    <var name="export_categories_for_transfer"><![CDATA[' . $export_categories_for_transfer . ']]></var>' . $nl;
				
				$export_products_for_categories = isset($p['unparse']['export_products_for_categories']) ? $p['unparse']['export_products_for_categories'] : 'false';
				$parseDataXml .= '    <var name="export_products_for_categories"><![CDATA[' . $export_products_for_categories . ']]></var>' . $nl;
				
				$export_product_position = isset($p['unparse']['export_product_position']) ? $p['unparse']['export_product_position'] : 'false';
				$parseDataXml .= '    <var name="export_product_position"><![CDATA[' . $export_product_position . ']]></var>' . $nl;
				
			}
			if($this->getEntityType() == "customer") {
				// Custom fields
				$recordLimitStart = isset($p['unparse']['recordlimitstart']) ? $p['unparse']['recordlimitstart'] : '0';
				$parseDataXml .= '    <var name="recordlimitstart"><![CDATA[' . $recordLimitStart . ']]></var>' . $nl;
				
				$recordLimitEnd = isset($p['unparse']['recordlimitend']) ? $p['unparse']['recordlimitend'] : '100';
				$parseDataXml .= '    <var name="recordlimitend"><![CDATA[' . $recordLimitEnd . ']]></var>' . $nl;
				
				$exportCustomerId = isset($p['unparse']['export_customer_id']) ? $p['unparse']['export_customer_id'] : 'false';
				$parseDataXml .= '    <var name="export_customer_id"><![CDATA[' . $exportCustomerId . ']]></var>' . $nl;
				
				$exportMultipleAddresses = isset($p['unparse']['export_multiple_addresses']) ? $p['unparse']['export_multiple_addresses'] : 'false';
				$parseDataXml .= '    <var name="export_multiple_addresses"><![CDATA[' . $exportMultipleAddresses . ']]></var>' . $nl;
			}
			if($this->getEntityType() == "customerreviews") {
			
				$reviews_by_sku = isset($p['unparse']['reviews_by_sku']) ? $p['unparse']['reviews_by_sku'] : 'false';
				$parseDataXml .= '    <var name="reviews_by_sku"><![CDATA[' . $reviews_by_sku . ']]></var>' . $nl;
				
				$customers_by_email = isset($p['unparse']['customers_by_email']) ? $p['unparse']['customers_by_email'] : 'false';
				$parseDataXml .= '    <var name="customers_by_email"><![CDATA[' . $customers_by_email . ']]></var>' . $nl;
			
			}
            $parseDataXml .= '</action>' . $nl . $nl;
        }

        $adapters = array(
            'product'=>'commerceextensions_productimportexport/convert_adapter_productimport',
            'order'=>'commerceextensions_orderimportexport/convert_adapter_importorders',
            'attribute'=>'commerceextensions_productattributesimportexport/convert_adapter_ProductAttributesimport',
            'category'=>'commerceextensions_categoriesimportexport/convert_adapter_categoryimport',
            'customer'=>'commerceextensions_advancedcustomerimportexport/convert_adapter_customerimport',
            'customerreviews'=>'commerceextensions_customerreviewsimportexport/convert_adapter_customerreviewimport',
            'couponcode'=>'commerceextensions_shoppingcartrulesimportexport/convert_adapter_couponcodeimport',
        );

        if ($import) {
            $entityXml = '<action type="' . $adapters[$this->getEntityType()] . '" method="save">' . $nl;
            $entityXml .= '    <var name="store"><![CDATA[' . $this->getStoreId() . ']]></var>' . $nl;
            $entityXml .= '</action>' . $nl . $nl;
        } else {
			if($this->getEntityType() != "category" && $this->getEntityType() != "customerreviews" && $this->getEntityType() != "couponcode" && $this->getEntityType() != "attribute") {
			
			$entityXml = '<action type="' . $adapters[$this->getEntityType()] . '" method="load">' . $nl;
			$entityXml .= '    <var name="store"><![CDATA[' . $this->getStoreId() . ']]></var>' . $nl;
			
			if(is_array($p[$this->getEntityType()]['filter'])) {
            foreach ($p[$this->getEntityType()]['filter'] as $f=>$v) {

                if (empty($v)) {
                    continue;
                }
                if (is_scalar($v)) {
                    $entityXml .= '    <var name="filter/' . $f . '"><![CDATA[' . $v . ']]></var>' . $nl;
                    $parseFileXmlInter .= '    <var name="filter/' . $f . '"><![CDATA[' . $v . ']]></var>' . $nl;
                } elseif (is_array($v)) {
                    foreach ($v as $a=>$b) {

                        if (strlen($b) == 0) {
                            continue;
                        }
                        $entityXml .= '    <var name="filter/' . $f . '/' . $a
                            . '"><![CDATA[' . $b . ']]></var>' . $nl;
                        $parseFileXmlInter .= '    <var name="filter/' . $f . '/'
                            . $a . '"><![CDATA[' . $b . ']]></var>' . $nl;
                    }
                }
            }
			}
            
            #$entityXml .= '    <var name="filter/adressType"><![CDATA[default_billing]]></var>' . $nl;
            
            $entityXml .= '</action>' . $nl . $nl;
			
			}
        }

        // Need to rewrite the whole xml action format
        if ($import) {
            $numberOfRecords = isset($p['import']['number_of_records']) ? $p['import']['number_of_records'] : 1;
            $decimalSeparator = isset($p['import']['decimal_separator']) ? $p['import']['decimal_separator'] : ' . ';
            $parseFileXmlInter .= '    <var name="number_of_records">'
                . $numberOfRecords . '</var>' . $nl;
            $parseFileXmlInter .= '    <var name="decimal_separator"><![CDATA['
                . $decimalSeparator . ']]></var>' . $nl;
            
			if($this->getEntityType() == "order") {
			// Custom fields
            $updateOrders = isset($p['parse']['update_orders']) ? $p['parse']['update_orders'] : 'false';
            $parseFileXmlInter .= '    <var name="update_orders"><![CDATA[' . $updateOrders . ']]></var>' . $nl;
			
            $updateCustomerAddress = isset($p['parse']['update_customer_address']) ? $p['parse']['update_customer_address'] : 'true';
            $parseFileXmlInter .= '    <var name="update_customer_address"><![CDATA[' . $updateCustomerAddress . ']]></var>' . $nl;
            
            $createInvoice = isset($p['parse']['create_invoice']) ? $p['parse']['create_invoice'] : 'false';
            $parseFileXmlInter .= '    <var name="create_invoice"><![CDATA[' . $createInvoice . ']]></var>' . $nl;
            
            $createShipment = isset($p['parse']['create_shipment']) ? $p['parse']['create_shipment'] : 'false';
            $parseFileXmlInter .= '    <var name="create_shipment"><![CDATA[' . $createShipment . ']]></var>' . $nl;
			
			//custom mapping data for product import
			
            $order_map_website_codes = isset($p['parse']['order_map_website_codes']) ? $p['parse']['order_map_website_codes'] : '';
            $parseFileXmlInter .= '    <var name="order_map_website_codes"><![CDATA[' . $order_map_website_codes . ']]></var>' . $nl;
			
            $order_map_store_ids = isset($p['parse']['order_map_store_ids']) ? $p['parse']['order_map_store_ids'] : '';
            $parseFileXmlInter .= '    <var name="order_map_store_ids"><![CDATA[' . $order_map_store_ids . ']]></var>' . $nl;
			
			}
			if($this->getEntityType() == "product") {
            // Custom fields
            $rootCatalogId = isset($p['parse']['root_catalog_id']) ? $p['parse']['root_catalog_id'] : '2';
            $parseFileXmlInter .= '    <var name="root_catalog_id"><![CDATA[' . $rootCatalogId . ']]></var>' . $nl;
            
            $update_products_only = isset($p['parse']['update_products_only']) ? $p['parse']['update_products_only'] : 'false';
            $parseFileXmlInter .= '    <var name="update_products_only"><![CDATA[' . $update_products_only . ']]></var>' . $nl;
			
            $import_images_by_url = isset($p['parse']['import_images_by_url']) ? $p['parse']['import_images_by_url'] : 'false';
            $parseFileXmlInter .= '    <var name="import_images_by_url"><![CDATA[' . $import_images_by_url . ']]></var>' . $nl;
			
            $multistoreimages = isset($p['parse']['multi_store_images']) ? $p['parse']['multi_store_images'] : 'false';
            $parseFileXmlInter .= '    <var name="multi_store_images"><![CDATA[' . $multistoreimages . ']]></var>' . $nl;
			
            $reimportImages = isset($p['parse']['reimport_images']) ? $p['parse']['reimport_images'] : 'false';
            $parseFileXmlInter .= '    <var name="reimport_images"><![CDATA[' . $reimportImages . ']]></var>' . $nl;
            
            $deleteallAndreimportImages = isset($p['parse']['deleteall_andreimport_images']) ? $p['parse']['deleteall_andreimport_images'] : 'false';
            $parseFileXmlInter .= '    <var name="deleteall_andreimport_images"><![CDATA[' . $deleteallAndreimportImages . ']]></var>' . $nl;
            
            $excludeImages = isset($p['parse']['exclude_images']) ? $p['parse']['exclude_images'] : 'false';
            $parseFileXmlInter .= '    <var name="exclude_images"><![CDATA[' . $excludeImages . ']]></var>' . $nl;
            
            $excludeGalleryImages = isset($p['parse']['exclude_gallery_images']) ? $p['parse']['exclude_gallery_images'] : 'false';
            $parseFileXmlInter .= '    <var name="exclude_gallery_images"><![CDATA[' . $excludeGalleryImages . ']]></var>' . $nl;
            
            $appendTierPrices = isset($p['parse']['append_tier_prices']) ? $p['parse']['append_tier_prices'] : 'false';
            $parseFileXmlInter .= '    <var name="append_tier_prices"><![CDATA[' . $appendTierPrices . ']]></var>' . $nl;
            
            $appendGroupPrices = isset($p['parse']['append_group_prices']) ? $p['parse']['append_group_prices'] : 'false';
            $parseFileXmlInter .= '    <var name="append_group_prices"><![CDATA[' . $appendGroupPrices . ']]></var>' . $nl;
            
            $appendCategories = isset($p['parse']['append_categories']) ? $p['parse']['append_categories'] : 'false';
            $parseFileXmlInter .= '    <var name="append_categories"><![CDATA[' . $appendCategories . ']]></var>' . $nl;
			
			//custom mapping data for product import
			
            $product_map_store_codes = isset($p['parse']['product_map_store_codes']) ? $p['parse']['product_map_store_codes'] : '';
            $parseFileXmlInter .= '    <var name="product_map_store_codes"><![CDATA[' . $product_map_store_codes . ']]></var>' . $nl;
			
            $product_map_website_codes = isset($p['parse']['product_map_website_codes']) ? $p['parse']['product_map_website_codes'] : '';
            $parseFileXmlInter .= '    <var name="product_map_website_codes"><![CDATA[' . $product_map_website_codes . ']]></var>' . $nl;
			
            $product_map_store_ids = isset($p['parse']['product_map_store_ids']) ? $p['parse']['product_map_store_ids'] : '';
            $parseFileXmlInter .= '    <var name="product_map_store_ids"><![CDATA[' . $product_map_store_ids . ']]></var>' . $nl;
            
			}
			
			if($this->getEntityType() == "attribute") {
			 // Custom fields
			
            $import_w_sort_order = isset($p['parse']['import_w_sort_order']) ? $p['parse']['import_w_sort_order'] : 'false';
            $parseFileXmlInter .= '    <var name="import_w_sort_order"><![CDATA[' . $import_w_sort_order . ']]></var>' . $nl;
			
            $attribute_options_delimiter = isset($p['parse']['attribute_options_delimiter']) ? $p['parse']['attribute_options_delimiter'] : '|';
            $parseFileXmlInter .= '    <var name="attribute_options_delimiter"><![CDATA[' . $attribute_options_delimiter . ']]></var>' . $nl;
			
            $attribute_options_value_delimiter = isset($p['parse']['attribute_options_value_delimiter']) ? $p['parse']['attribute_options_value_delimiter'] : ',';
            $parseFileXmlInter .= '    <var name="attribute_options_value_delimiter"><![CDATA[' . $attribute_options_value_delimiter . ']]></var>' . $nl;
			//custom mapping data for product attribute import
			
            $map_store_ids = isset($p['parse']['map_store_ids']) ? $p['parse']['map_store_ids'] : '';
            $parseFileXmlInter .= '    <var name="map_store_ids"><![CDATA[' . $map_store_ids . ']]></var>' . $nl;
			
			}
			
			if($this->getEntityType() == "category") {
				// Custom fields
				$categorydelimiter = isset($p['parse']['categorydelimiter']) ? $p['parse']['categorydelimiter'] : '/';
				$parseFileXmlInter .= '    <var name="categorydelimiter"><![CDATA[' . $categorydelimiter . ']]></var>' . $nl;
				
				//custom mapping data for product import
			
				$map_store_codes = isset($p['parse']['map_store_codes']) ? $p['parse']['map_store_codes'] : '';
				$parseFileXmlInter .= '    <var name="map_store_codes"><![CDATA[' . $map_store_codes . ']]></var>' . $nl;
				
				$map_root_ids = isset($p['parse']['map_root_ids']) ? $p['parse']['map_root_ids'] : '';
				$parseFileXmlInter .= '    <var name="map_root_ids"><![CDATA[' . $map_root_ids . ']]></var>' . $nl;
				
			}
			if($this->getEntityType() == "customer") {
            // Custom fields
            
            $insert_customer_id = isset($p['parse']['insert_customer_id']) ? $p['parse']['insert_customer_id'] : 'false';
            $parseFileXmlInter .= '    <var name="insert_customer_id"><![CDATA[' . $insert_customer_id . ']]></var>' . $nl;
			
			
            $import_multiple_customer_address = isset($p['parse']['import_multiple_customer_address']) ? $p['parse']['import_multiple_customer_address'] : 'false';
            $parseFileXmlInter .= '    <var name="import_multiple_customer_address"><![CDATA[' . $import_multiple_customer_address . ']]></var>' . $nl;
			
            //$update_customer_password = isset($p['parse']['update_customer_password']) ? $p['parse']['update_customer_password'] : 'false';
            //$parseFileXmlInter .= '    <var name="update_customer_password"><![CDATA[' . $update_customer_password . ']]></var>' . $nl;
			
            //$email_customer_password = isset($p['parse']['email_customer_password']) ? $p['parse']['email_customer_password'] : 'false';
            //$parseFileXmlInter .= '    <var name="email_customer_password"><![CDATA[' . $email_customer_password . ']]></var>' . $nl;
			
			
			//custom mapping data for customer import
			$customer_map_website_codes = isset($p['parse']['customer_map_website_codes']) ? $p['parse']['customer_map_website_codes'] : '';
			$parseFileXmlInter .= '    <var name="customer_map_website_codes"><![CDATA[' . $customer_map_website_codes . ']]></var>' . $nl;
			
			}
			if($this->getEntityType() == "customerreviews") {
			
			//custom mapping data for customerreviews import
            $customerreviews_map_store_ids = isset($p['parse']['customerreviews_map_store_ids']) ? $p['parse']['customerreviews_map_store_ids']:'';
            $parseFileXmlInter .= '    <var name="customerreviews_map_store_ids"><![CDATA[' . $customerreviews_map_store_ids . ']]></var>' . $nl;
			
			}
			if($this->getEntityType() == "couponcode") {
			
			//custom mapping data for couponcodeimport
            $couponcode_map_website_ids = isset($p['parse']['couponcode_map_website_ids']) ? $p['parse']['couponcode_map_website_ids']:'';
            $parseFileXmlInter .= '    <var name="couponcode_map_website_ids"><![CDATA[' . $couponcode_map_website_ids . ']]></var>' . $nl;
			
            $couponcode_customer_group_ids = isset($p['parse']['couponcode_customer_group_ids']) ? $p['parse']['couponcode_customer_group_ids']:'';
            $parseFileXmlInter .= '    <var name="couponcode_customer_group_ids"><![CDATA[' . $couponcode_customer_group_ids . ']]></var>' . $nl;
			
			}
			
            if ($this->getDataTransfer()==='interactive') {
                $xml = $parseFileXmlInter;
                $xml .= '    <var name="adapter">' . $adapters[$this->getEntityType()] . '</var>' . $nl;
                $xml .= '    <var name="method">parse</var>' . $nl;
                $xml .= '</action>';
            } else {
                $xml = $fileXml;
                $xml .= $parseFileXmlInter;
                $xml .= '    <var name="adapter">' . $adapters[$this->getEntityType()] . '</var>' . $nl;
                $xml .= '    <var name="method">parse</var>' . $nl;
                $xml .= '</action>';
            }
            //$xml = $interactiveXml.$fileXml.$parseFileXml.$mapXml.$parseDataXml.$entityXml;

        } else {
            $xml = $entityXml . $parseDataXml . $mapXml . $parseFileXml . $fileXml . $interactiveXml;
        }

        $this->setGuiData($p);
        $this->setActionsXml($xml);
/*echo "<pre>" . print_r($p,1) . "</pre>";
echo "<xmp>" . $xml . "</xmp>";
die;*/
        return $this;
    }
}