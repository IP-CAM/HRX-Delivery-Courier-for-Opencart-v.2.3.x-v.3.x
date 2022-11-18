<link rel="stylesheet" href="view/javascript/hrx_m/admin.css?20220920">
<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content" class="hrx_m_content">
    <div class="container-fluid page-header">
        <div class="hrx_m_manifest-header">
            <h1><img src="view/image/hrx_m/logo-hrx.svg" alt="Logo"></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb): ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div id="header-action-buttons" class="hrx_m_manifest-header-actions">
                <button type="button" data-toggle="tooltip" title="" data-btn-refresh-orders
                    class="btn btn-default btn-hrx" data-original-title="<?php echo $hrx_m_btn_refresh_orders; ?>">
                    <i class="fa fa-refresh"></i>
                </button>
                <button type="button" data-toggle="tooltip" title="" 
                    onclick="$('#filter-order').toggleClass('hidden-sm hidden-xs');" class="btn btn-default btn-hrx hidden-md hidden-lg" data-original-title="<?php echo $hrx_m_title_filters; ?>">
                    <i class="fa fa-filter"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid hidden" data-orders-refresh-loader>
        <div class="alert alert-info hrx-orders-refresh-loading">
            <i class="fa fa-refresh fa-spin"></i>
            <span><?php echo $hrx_m_alert_orders_refreshed; ?></span>
            <span data-orders-refreshed>00</span>
            <span><?php echo $hrx_m_alert_orders_refreshing_do_not_close; ?></span>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div id="filter-order" class="col-md-3 col-md-push-9 col-sm-12 hidden-sm hidden-xs">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-filter"></i> <?php echo $hrx_m_title_filters; ?></h3>
                    </div>

                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label" for="input-order-id"><?php echo $hrx_m_label_order_id; ?></label>
                            <input type="text" name="filter_order_id" value="" id="input-order-id" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="control-label" for="input-customer"><?php echo $hrx_m_label_customer; ?></label>
                            <input type="text" name="filter_customer" value="" id="input-customer" class="form-control" />
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="input-hrx-id"><?php echo $hrx_m_label_hrx_id; ?></label>
                            <input type="text" name="filter_hrx_id" value="" id="input-hrx-id" class="form-control" />
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="input-hrx-tracking-num"><?php echo $hrx_m_label_hrx_tracking_num; ?></label>
                            <input type="text" name="filter_hrx_tracking_num" value="" id="input-hrx-tracking-num" class="form-control" />
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="input-is_registered"><?php echo $hrx_m_label_is_registered; ?></label>
                            <select name="filter_is_registered" id="input-is_registered" class="form-control">
                                <option value="0">-</option>
                                <option value="1"><?php echo $hrx_m_generic_no; ?></option>
                                <option value="2"><?php echo $hrx_m_generic_yes; ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="input-order-status"><?php echo $hrx_m_label_order_status_id; ?></label>
                            <select name="filter_order_status_id" id="input-order-status" class="form-control">
                                <option value="0">-</option>
                                <?php foreach ($order_statuses as $order_status): ?>
                                    <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                                <?php endforeach; ?>            
                            </select>
                        </div>

                        <div class="form-group hrx-filter-btn-wrapper">
                            <button type="button" data-btn-filter-reset class="btn btn-warning"><i class="fa fa-recycle"></i> <?php echo $hrx_m_generic_btn_filter_reset; ?></button>
                            <button type="button" data-btn-filter class="btn btn-default"><i class="fa fa-filter"></i> <?php echo $hrx_m_generic_btn_filter; ?></button>
                        </div>
                    </div>
                </div>
            </div> <!-- filter end -->

            <div id="manifest_list" class="col-md-9 col-md-pull-3 col-sm-12">
                <?php echo $partial_manifest_list; ?>
            </div> <!-- results end -->
        </div> <!-- row end -->
    </div> <!-- container end -->
</div> <!-- content end -->

<script>
    const HRX_M_MANIFEST_DATA = <?php echo json_encode($hrx_m_data); ?>;
</script>
<script src="<?php echo $mijora_common_js_path; ?>"></script>
<script src="view/javascript/hrx_m/manifest.js?202211181640"></script>

<?php echo $footer; ?> 