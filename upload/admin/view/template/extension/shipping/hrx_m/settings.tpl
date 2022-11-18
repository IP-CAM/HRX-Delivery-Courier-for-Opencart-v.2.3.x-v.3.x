<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content" class="hrx_m_content">
    <div class="page-header">
        <div class="container-fluid">
            <h1><img src="view/image/hrx_m/logo-hrx.svg" alt="Logo" style="height: 33px;"></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb): ?>
                    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php endforeach; ?>
            </ul>
            <span class="hrx-version">v<?php echo $hrx_m_version; ?></span>
        </div>
    </div>

    <!-- Errors / Success -->
    <div class="container-fluid">
        <?php if ($error_warning): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- VERSION CHECK -->
    <?php if ($hrx_m_git_version): ?>
    <div class="container-fluid">
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo str_replace('$$hrx_m_new_version$$', $hrx_m_git_version['version'], $hrx_m_new_version_notify); ?> 
            <a href="<?php echo $hrx_m_git_version['download_url']; ?>" target="_blank" class="btn btn-success"><?php echo $hrx_m_button_download_version; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- DB CHECK -->
    <?php if ($hrx_m_db_check): ?>
    <div class="container-fluid">
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $hrx_m_db_fix_notify; ?> 
            <a href="<?php echo $hrx_m_db_fix_url; ?>" class="btn btn-success"><?php echo $hrx_m_button_fix_db; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- XML CHECK -->
    <?php if ($hrx_m_xml_check): ?>
    <div class="container-fluid">
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> <?php echo $hrx_m_xml_fix_notify; ?> 
            <a href="<?php echo $hrx_m_xml_fix_url; ?>" class="btn btn-success"><?php echo $hrx_m_button_fix_xml; ?></a>
        </div>
    </div>
    <?php endif; ?>

    <div class="container-fluid hidden" data-delivery-points-loader>
        <div class="alert alert-info hrx-delivery-points-loading">
            <i class="fa fa-refresh fa-spin"></i>
            <span><?php echo $hrx_m_alert_delivery_points_loaded; ?></span>
            <span data-delivery-points-loaded>00</span>
            <span><?php echo $hrx_m_alert_delivery_points_do_not_close; ?></span>
        </div>
    </div>

    <ul class="container-fluid nav nav-tabs">
        <li class="active"><a href="#tab-general" data-toggle="tab"><?php echo $hrx_m_tab_general; ?></a></li>
        <li><a href="#tab-api" data-toggle="tab"><?php echo $hrx_m_tab_api; ?></a></li>
        <li><a href="#tab-warehouse" data-toggle="tab"><?php echo $hrx_m_tab_warehouse; ?></a></li>
        <li><a href="#tab-delivery" data-toggle="tab"><?php echo $hrx_m_tab_delivery; ?></a></li>
        <li><a href="#tab-delivery-courier" data-toggle="tab"><?php echo $hrx_m_tab_delivery_courier; ?></a></li>
        <li><a href="#tab-prices" data-toggle="tab"><?php echo $hrx_m_tab_prices; ?></a></li>
        <li><a href="#tab-parcel-default" data-toggle="tab"><?php echo $hrx_m_tab_parcel_default; ?></a></li>
    </ul>

    <div class="tab-content">
        <!-- General Settings -->
        <div class="tab-pane active" id="tab-general">
            <?php echo $partial_tab_general; ?>
        </div>

        <!-- API Settings -->
        <div class="tab-pane" id="tab-api">
            <?php echo $partial_tab_api; ?>
        </div>

        <!-- Warehouse -->
        <div class="tab-pane" id="tab-warehouse">
            <?php echo $partial_tab_warehouse; ?>
        </div>

        <!-- Delivery Points -->
        <div class="tab-pane" id="tab-delivery">
            <?php echo $partial_tab_delivery_point; ?>
        </div>
        
        <!-- Delivery Locations (Courier) -->
        <div class="tab-pane" id="tab-delivery-courier">
            <?php echo $partial_tab_delivery_courier_location; ?>
        </div>

        <!-- Price Settings -->
        <div class="tab-pane" id="tab-prices">
            <?php echo $partial_tab_prices; ?>
        </div>
        
        <!-- Parcel Defaults -->
        <div class="tab-pane" id="tab-parcel-default">
            <?php echo $partial_tab_parcel_default; ?>
        </div>

    </div> <!-- Content panel -->
</div>

<link rel="stylesheet" href="view/javascript/hrx_m/admin.css?20220920">
<script>
var HRX_M_SETTINGS_DATA = {
    syncWarehousePerPage: <?php echo $sync_warehouse_per_page; ?>,
    syncDeliveryPointsPerPage: <?php echo $sync_delivery_points_per_page; ?>,
    defaultWarehouse: <?php echo json_encode($default_warehouse); ?>,
    url_ajax: '<?php echo $ajax_url; ?>',
    geo_zones: <?php echo json_encode($geo_zones); ?>
};
</script>
<script src="<?php echo $mijora_common_js_path; ?>"></script>
<script src="view/javascript/hrx_m/settings.js?20220920"></script>
<?php echo $footer; ?> 