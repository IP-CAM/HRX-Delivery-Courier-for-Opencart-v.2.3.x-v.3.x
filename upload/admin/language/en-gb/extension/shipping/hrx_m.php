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
$_['hrx_m_generic_btn_filter_reset'] = 'Filter reset';

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
$_['hrx_m_label_sort_order_internal'] = 'Options sort order';
$_['hrx_m_sort_order_internal_0'] = 'Courier &gt;&gt; Terminals';
$_['hrx_m_sort_order_internal_1'] = 'Terminals &gt;&gt; Courier';

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

// Tab - Delivery Locations (Courier) - uses some strings from Tab - Delivery Locations
$_['hrx_m_tab_delivery_courier'] = 'Courier Delivery Locations';
$_['hrx_m_btn_refresh_delivery_courier_locations'] = 'Load delivery locations from API';
$_['hrx_m_title_delivery_courier_locations'] = 'Delivery Locations';

// Tab - Prices
$_['hrx_m_tab_prices'] = 'Prices';
$_['hrx_m_title_price_settings'] = 'Price settings';
$_['hrx_m_label_price_country'] = 'Country';
$_['hrx_m_label_price_terminal'] = 'Terminal Price';
$_['hrx_m_label_price_courier'] = 'Courier Price';
$_['hrx_m_label_price_range_type'] = 'Range type';
$_['hrx_m_button_add_price'] = 'Add Price';
$_['hrx_m_button_save_price'] = 'Save Price';
$_['hrx_m_placeholder_price_country'] = 'Select country';
$_['hrx_m_header_actions'] = 'Actions';
$_['hrx_m_help_price'] = 'Price field accepts range format <pre>range:price ; range:price</pre> Range is either cart subtotal or weight and price is what price should be set from that range untill next one. If price field is left empty that option will be disabled in checkout';
$_['hrx_m_help_price_country'] = 'Selection is limited to active terminal and courier delivery countries';
$_['hrx_m_range_type_cart'] = 'Cart SubTotal';
$_['hrx_m_range_type_weight'] = 'Cart Weight';

// Tab - Parcel default
$_['hrx_m_tab_parcel_default'] = 'Default dimensions';
$_['hrx_m_title_parcel_default_global'] = 'Global default dimensions';
$_['hrx_m_title_pd_table'] = 'Category list';
$_['hrx_m_label_pd_weight'] = 'Weight';
$_['hrx_m_label_pd_dimensions'] = 'Dimensions (L x W x H)';
$_['hrx_m_label_pd_length'] = 'Length';
$_['hrx_m_label_pd_width'] = 'Width';
$_['hrx_m_label_pd_height'] = 'Height';
$_['hrx_m_pd_table_no_categories'] = 'OpenCart has no categories';
$_['hrx_m_pd_table_id'] = 'ID';
$_['hrx_m_pd_table_name'] = 'Category Name';
$_['hrx_m_pd_table_has_custom_defaults'] = 'Has Custom Defaults';
$_['hrx_m_pd_table_action'] = 'Action';
$_['hrx_m_pd_btn_edit_global'] = 'Edit global dimensions';
$_['hrx_m_pd_btn_edit'] = 'Edit';
$_['hrx_m_pd_btn_reset'] = 'Reset';
$_['hrx_m_description_parcel_defaults'] = 'In case product does not have one of dimensions, it will be replaced with the set one either global or by product category. In case multiple categories assigned to product only highest value will be used.';
$_['hrx_m_help_parcel_defaults'] = 'Weight and parcel dimensions must not be empty or 0';

// Order List JS strings
$_['hrx_m_js_filter_label_hrx_only'] = 'Show Only HRX Delivery Orders';
$_['hrx_m_js_filter_option_yes'] = 'Yes';
$_['hrx_m_js_filter_option_no'] = 'No';


/**
 * ORDER PANEL
 */
$_['hrx_m_panel_title'] = 'HRX Delivery';
$_['hrx_m_panel_tab_general'] = 'General';
$_['hrx_m_panel_tab_tracking_events'] = 'Tracking Events';
$_['hrx_m_panel_label_hrx_order_status'] = 'HRX Status';
$_['hrx_m_panel_label_hrx_track_nr'] = 'Tracking #';
$_['hrx_m_panel_order_status_'] = 'Order not registered in HRX system';
$_['hrx_m_panel_label_warehouse'] = 'Warehouse';
$_['hrx_m_panel_label_terminal'] = 'Terminal';
$_['hrx_m_panel_label_box_size_saved'] = 'Saved parcel size';
$_['hrx_m_panel_label_box_size_registered'] = 'Registered parcel size';
$_['hrx_m_panel_label_box_size_predicted'] = 'Predicted parcel size';
$_['hrx_m_panel_title_dimensions_limitations'] = 'Delivery location limitations';
$_['hrx_m_panel_label_limit_dimensions'] = 'Dimensions L x W x H (cm)';
$_['hrx_m_panel_label_limit_weight'] = 'Weight (kg)';
$_['hrx_m_panel_label_weight'] = 'Weight';
$_['hrx_m_panel_label_length'] = 'Length';
$_['hrx_m_panel_label_width'] = 'Width';
$_['hrx_m_panel_label_height'] = 'Height';
$_['hrx_m_panel_label_comment'] = 'Comment';
$_['hrx_m_panel_btn_register'] = 'Register';
$_['hrx_m_panel_btn_edit'] = 'Save changes';
$_['hrx_m_warning_bad_comment'] = 'Warning! Comment can have only letters, numbers, spaces and _-';
$_['hrx_m_warning_missing_dimensions'] = 'Warning! Some dimensions are not set or invalid!';
$_['hrx_m_warning_does_not_fit'] = 'Warning! Parcel size and/or weight is above destination maximum limit.';
$_['hrx_m_warning_warehouse_not_found'] = 'Selected warehouse not found';
$_['hrx_m_warning_notify_missing_warehouse'] = 'Previously selected warehouse could not be found. Using default warehouse.';
$_['hrx_m_panel_btn_load_events'] = 'Load Events';
$_['hrx_m_panel_events_col_timestamp'] = 'Timestamp';
$_['hrx_m_panel_events_col_location'] = 'Location';
$_['hrx_m_panel_events_col_event'] = 'Event';
$_['hrx_m_panel_notify_load_events'] = 'Press Load Events button to see registered events';
$_['hrx_m_panel_notify_no_events'] = 'No Events found';
$_['hrx_m_notify_no_data_change'] = 'No data changed';


/**
 * MANIFEST PAGE
 */
$_['hrx_m_manifest_page_title'] = 'HRX Delivery Orders';
$_['hrx_m_title_manifest_orders'] = 'Orders with HRX Delivery';
$_['hrx_m_manifest_col_order_id'] = 'Order ID';
$_['hrx_m_manifest_col_customer'] = 'Customer';
$_['hrx_m_manifest_col_status'] = 'Status';
$_['hrx_m_manifest_col_hrx_data'] = 'HRX Data';
$_['hrx_m_manifest_col_action'] = 'Actions';
$_['hrx_m_manifest_orders_no_results'] = 'No results!';
$_['hrx_m_hrx_data_id'] = 'HRX ID:';
$_['hrx_m_hrx_data_status'] = 'HRX Status:';
$_['hrx_m_hrx_data_deliver'] = 'Deliver to:';
$_['hrx_m_hrx_data_tracking'] = 'Track. #:';
$_['hrx_m_btn_refresh_orders'] = 'Refresh HRX data for Orders';
$_['hrx_m_btn_register_order'] = 'Register order';
$_['hrx_m_btn_manifest_action'] = 'Actions';
$_['hrx_m_btn_print_label'] = 'Print shipment label';
$_['hrx_m_btn_print_label_return'] = 'Print return label';
$_['hrx_m_btn_refresh_order'] = 'Refresh HRX Order data';
$_['hrx_m_btn_cancel_order'] = 'Cancel HRX Order';
$_['hrx_m_btn_ready_for_pickup'] = 'Ready for pickup';
$_['hrx_m_btn_cancel_pickup'] = 'Cancel pickup';
// filters
$_['hrx_m_title_filters'] = 'Filters';
$_['hrx_m_label_order_id'] = 'Order ID';
$_['hrx_m_label_customer'] = 'Customer';
$_['hrx_m_label_hrx_id'] = 'HRX Order ID';
$_['hrx_m_label_hrx_tracking_num'] = 'Tracking #';
$_['hrx_m_label_order_status_id'] = 'Order Status';
$_['hrx_m_label_is_registered'] = 'Is Registered';

$_['hrx_m_tooltip_print_labels'] = 'Print / Register Labels';
$_['hrx_m_tooltip_call_courier'] = 'Call Courier';

$_['hrx_m_notify_is_registered'] = 'Order already registered in HRX';
$_['hrx_m_notify_warehouse_not_found'] = 'Could not find warehouse ID';
$_['hrx_m_notify_cant_cancel'] = 'This HRX Order can not be cancelled';
$_['hrx_m_notify_cant_change_status'] = 'Not allowed to change status for this HRX Order';
$_['hrx_m_notify_no_change_status'] = 'No need for order state change';
$_['hrx_m_notify_registered_success'] = 'Order has been registered with HRX. Please wait about 30s before trying to print labels.';
$_['hrx_m_error_order_not_registered'] = 'Order must be registered with HRX first!';
$_['hrx_m_error_order_canceled'] = 'HRX Order canceled!';
$_['hrx_m_error_order_not_refreshable'] = 'Order completed, nothing to update';
$_['hrx_m_error_order_no_return_label'] = 'HRX Order has no return label!';
$_['hrx_m_exception_delivery_location_unavailable'] = 'Delivery location no longer available!';

$_['hrx_m_alert_orders_refreshed'] = 'Orders HRX data refreshed:';
$_['hrx_m_alert_orders_refreshing_do_not_close'] = 'Please DO NOT close this window';
