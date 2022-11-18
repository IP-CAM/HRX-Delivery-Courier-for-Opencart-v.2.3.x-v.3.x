<div class="container-fluid">
    <div class="panel panel-default">
        <div class="pabel-body panel-hrx-sync-actions">
            <div class="timestamp-wrapper">
                <span class="timestamp-text"><?php echo $hrx_m_warehouse_update_text; ?></span>
                <span class="timestamp-date">
                    <?php if ($warehouse_last_update): ?>
                        <?php echo $warehouse_last_update; ?>
                    <?php else: ?>
                        <?php echo $hrx_m_warehouse_updated_never; ?>
                    <?php endif; ?>
                </span>
            </div>
            <div>
                <button type="button" data-refresh-warehouses class="btn btn-default btn-hrx with-text"><i class="fa fa-refresh"></i><?php echo $hrx_m_btn_refresh_warehouses; ?></button>
            </div>
        </div>
    </div>

    <?php if (!$default_warehouse->id): ?>
    <div class="alert alert-danger" data-no-default-warehouse><?php echo $hrx_m_alert_no_default_warehouse; ?></div>
    <?php endif; ?>

    <?php if (empty($warehouses)): ?>
        <div class="alert alert-info"><?php echo $hrx_m_alert_update_warehouse_list; ?></div>
    <?php else: ?>
        <?php foreach ($warehouses as $warehouse): ?>
            <div class="panel panel-default panel-warehouse">
                <div class="panel-heading">
                    <img src="view/image/hrx_m/warehouse.svg" alt="">
                    <h3 class="panel-title"><?php echo $warehouse->name; ?></h3>
                </div>

                <div class="panel-body form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $hrx_m_label_warehouse_id; ?></label>
                        <div class="col-sm-10">
                            <input type="text" value="<?php echo $warehouse->id; ?>" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $hrx_m_label_warehouse_title; ?></label>
                        <div class="col-sm-10">
                            <input type="text" value="<?php echo $warehouse->name; ?>" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $hrx_m_label_warehouse_country_code; ?></label>
                        <div class="col-sm-10">
                            <input type="text" value="<?php echo $warehouse->country; ?>" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $hrx_m_label_warehouse_zip; ?></label>
                        <div class="col-sm-10">
                            <input type="text" value="<?php echo $warehouse->zip; ?>" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $hrx_m_label_warehouse_address; ?></label>
                        <div class="col-sm-10">
                            <input type="text" value="<?php echo $warehouse->address; ?>" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $hrx_m_radio_text_set_default; ?></label>
                        <div class="col-sm-10">
                            <label class="form-control hrx-checkbox-wrapper">
                                <input type="checkbox" name="hrx_m_default_warehouse" class="form-control" 
                                    data-warehouse-id="<?php echo $warehouse->id; ?>"
                                    <?php if ($warehouse->is_default): ?>checked<?php endif; ?>>
                            </label>
                        </div>
                    </div>

                </div>
            </div> <!-- Panel -->
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="row warehouse-paginator-wrapper">
        <div class="col-sm-12 text-center">
            <?php echo $warehouse_pagination; ?>
        </div>
    </div>
</div>