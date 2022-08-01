<?php
// Heading - without prefix as thats what opencart expects
$_['heading_title'] = 'HRX Delivery';

//Menu
$_['hrx_m_menu_head'] = 'HRX Delivery';
$_['hrx_m_menu_settings'] = 'Settings';
$_['hrx_m_menu_manifest'] = 'Orders';

// Breadcrumb
$_['hrx_m_text_extension'] = 'Extensions';

// Generic Options
$_['hrx_m_generic_none'] = '--- None ---';
$_['hrx_m_generic_all_zones'] = 'All zones';
$_['hrx_m_generic_enabled'] = 'Enabled';
$_['hrx_m_generic_disabled'] = 'Disabled';
$_['hrx_m_generic_no'] = 'No';
$_['hrx_m_generic_yes'] = 'Yes';

// Generic Buttons
$_['hrx_m_generic_btn_save'] = 'Save';
$_['hrx_m_generic_btn_cancel'] = 'Cancel';
$_['hrx_m_generic_btn_change'] = 'Change';
$_['hrx_m_generic_btn_filter'] = 'Filter';

// Generic Messages
$_['hrx_m_msg_setting_saved'] = 'Settings saved';

// Generic Errors
$_['hrx_m_error_permission'] = 'You do not have Modify Permission for HRX Delivery module settings';

/**
 * SETTINGS PAGE
 */

// Module new version notification
$_['hrx_m_new_version_notify'] = 'There is new module version v$$hrx_m_new_version$$!';
$_['hrx_m_button_download_version'] = 'Download';
// DB fix notification
$_['hrx_m_db_fix_notify'] = 'Problems found with DB tables';
$_['hrx_m_button_fix_db'] = 'FIX IT';
// XML fix notification
$_['hrx_m_xml_fix_notify'] = 'Newer version of modification file found for system/hrx_m_base.ocmod.xml';
$_['hrx_m_button_fix_xml'] = 'Update file';
$_['hrx_m_xml_updated'] = 'system/hrx_m_base.ocmod.xml updated. Please refresh modifications now.';

// Tab - General Settings
$_['hrx_m_tab_general'] = 'General';
$_['hrx_m_title_general_settings'] = 'General settings';
$_['hrx_m_label_tax_class'] = 'Tax class';
$_['hrx_m_label_geo_zone'] = 'Geo zone';
$_['hrx_m_label_status'] = 'Module status';
$_['hrx_m_label_sort_order'] = 'Sort order';

// Tab - API
$_['hrx_m_tab_api'] = 'API';
$_['hrx_m_title_api_settings'] = 'API settings';
$_['hrx_m_label_api_token'] = 'API token';
$_['hrx_m_label_api_test_mode'] = 'Test mode';
$_['hrx_m_btn_test_token'] = 'Test token';

// Tab - Warehouses
$_['hrx_m_tab_warehouse'] = 'Warehouses';
$_['hrx_m_warehouse_update_text'] = 'Last update:';
$_['hrx_m_warehouse_updated_never'] = 'Never';
$_['hrx_m_tab_warehouse'] = 'Warehouses';
$_['hrx_m_btn_refresh_warehouses'] = 'Load warehouses from API';
$_['hrx_m_alert_update_warehouse_list'] = 'Please update warehouse list from API';
$_['hrx_m_alert_no_default_warehouse'] = 'Please select default warehouse';
$_['hrx_m_label_warehouse_id'] = 'ID';
$_['hrx_m_label_warehouse_title'] = 'Title';
$_['hrx_m_label_warehouse_country_code'] = 'Country code';
$_['hrx_m_label_warehouse_zip'] = 'Postcode';
$_['hrx_m_label_warehouse_address'] = 'Address';
$_['hrx_m_radio_text_set_default'] = 'Set as default';

// Tab - Delivery Points
$_['hrx_m_tab_delivery'] = 'Delivery Locations';
$_['hrx_m_delivery_update_text'] = 'Last update:';
$_['hrx_m_delivery_updated_never'] = 'Never';
$_['hrx_m_btn_refresh_delivery_points'] = 'Load delivery locations from API';
$_['hrx_m_title_delivery_points'] = 'Delivery Locations';
$_['hrx_m_table_col_id'] = 'ID';
$_['hrx_m_table_col_address'] = 'Address';
$_['hrx_m_table_col_zip'] = 'ZIP';
$_['hrx_m_table_col_city'] = 'City';
$_['hrx_m_table_col_country'] = 'Country Code';
$_['hrx_m_table_col_dimmensions'] = 'Dimmensions L x W x H (cm)';
$_['hrx_m_table_col_min'] = 'Min';
$_['hrx_m_table_col_max'] = 'Max';
$_['hrx_m_table_col_weight'] = 'Weight (kg)';
$_['hrx_m_table_col_active'] = 'Active';
$_['hrx_m_alert_update_delivery_points_list'] = 'Please update delivery locations list from API';
$_['hrx_m_alert_delivery_points_loaded'] = 'Delivery locations loaded:';
$_['hrx_m_alert_delivery_points_do_not_close'] = 'Please DO NOT close this window';

// Tab - Prices
$_['hrx_m_tab_prices'] = 'Prices';
$_['hrx_m_title_price_settings'] = 'Price settings';
$_['hrx_m_label_price_country'] = 'Country';
$_['hrx_m_label_price_terminal'] = 'Price';
$_['hrx_m_label_price_range_type'] = 'Range type';
$_['hrx_m_button_add_price'] = 'Add Price';
$_['hrx_m_button_save_price'] = 'Save Price';
$_['hrx_m_placeholder_price_country'] = 'Select country';
$_['hrx_m_header_actions'] = 'Actions';
$_['hrx_m_help_price'] = 'Price field accepts range format <pre>range:price ; range:price</pre> Range is either cart subtotal or weight and price is what price should be set from that range untill next one.';
$_['hrx_m_help_price_country'] = 'Selection is limited to active delivery location countries';
$_['hrx_m_range_type_cart'] = 'Cart SubTotal';
$_['hrx_m_range_type_weight'] = 'Cart Weight';

// Order List JS strings
$_['hrx_m_js_filter_label_hrx_only'] = 'Show Only HRX Delivery Orders';
$_['hrx_m_js_filter_option_yes'] = 'Yes';
$_['hrx_m_js_filter_option_no'] = 'No';


/**
 * MANIFEST PAGE
 */
$_['hrx_m_manifest_page_title'] = 'HRX Delivery Orders';
$_['hrx_m_title_manifest_orders'] = 'Orders with HRX Delivery';
$_['hrx_m_manifest_col_order_id'] = 'Order ID';
$_['hrx_m_manifest_col_customer'] = 'Customer';
$_['hrx_m_manifest_col_status'] = 'Status';
$_['hrx_m_manifest_col_hrx_order_id'] = 'HRX Order ID';
// $_['hrx_m_manifest_col_manifest_id'] = 'Manifest ID';
$_['hrx_m_manifest_col_action'] = 'Actions';
$_['hrx_m_manifest_orders_no_results'] = 'No results!';
$_['hrx_m_btn_register_order'] = 'Register order';
$_['hrx_m_btn_print_label'] = 'Print shipment label';
$_['hrx_m_btn_print_label_return'] = 'Print return label';
// filters
$_['hrx_m_title_filters'] = 'Filters';
$_['hrx_m_label_order_id'] = 'Order ID';
$_['hrx_m_label_customer'] = 'Customer';
$_['hrx_m_label_hrx_id'] = 'HRX Order ID';
$_['hrx_m_label_order_status_id'] = 'Order Status';
$_['hrx_m_label_is_registered'] = 'Is Registered';
// $_['hrx_m_label_has_manifest'] = 'Is In Manifest';

$_['hrx_m_tooltip_print_labels'] = 'Print / Register Labels';
// $_['hrx_m_tooltip_create_manifest'] = 'Create Manifest';
$_['hrx_m_tooltip_call_courier'] = 'Call Courier';

$_['hrx_m_notify_is_registered'] = 'Order is registered in HRX';
$_['hrx_m_notify_registered_success'] = 'Order has been registered with HRX. Please wait about 30s before trying to print labels.';
$_['hrx_m_error_order_not_registered'] = 'Order must be registered with HRX first!';
