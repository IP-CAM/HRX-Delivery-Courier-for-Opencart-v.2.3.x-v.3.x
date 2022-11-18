<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $hrx_m_title_general_settings; ?></h3>
        </div>

        <div class="panel-body">
            <form action="<?php echo $form_action; ?>" method="post" enctype="multipart/form-data" id="form-hrx_m" class="form-horizontal">
                <input type="hidden" name="general_settings_update">
                
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-tax-class"><?php echo $hrx_m_label_tax_class; ?></label>
                    <div class="col-sm-10">
                        <select name="hrx_m_tax_class_id" id="input-tax-class" class="form-control">
                            <option value="0"><?php echo $hrx_m_generic_none; ?></option>
                            <?php foreach ($tax_classes as $tax_class): ?>
                                <?php if ($tax_class['tax_class_id'] == $hrx_m_tax_class_id): ?>
                                    <option value="<?php echo $tax_class['tax_class_id']; ?>" selected="selected"><?php echo $tax_class['title']; ?></option>
                                <?php else: ?>
                                    <option value="<?php echo $tax_class['tax_class_id']; ?>"><?php echo $tax_class['title']; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $hrx_m_label_geo_zone; ?></label>
                    <div class="col-sm-10">
                        <select name="hrx_m_geo_zone_id" id="input-geo-zone" class="form-control">
                            <option value="0"><?php echo $hrx_m_generic_all_zones; ?></option>
                            <?php foreach ($geo_zones as $geo_zone): ?>
                            <?php if ($geo_zone['geo_zone_id'] == $hrx_m_geo_zone_id): ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                            <?php else: ?>
                                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-status"><?php echo $hrx_m_label_status; ?></label>
                    <div class="col-sm-10">
                        <select name="hrx_m_status" id="input-status" class="form-control">
                            <?php if ($hrx_m_status): ?>
                            <option value="1" selected="selected"><?php echo $hrx_m_generic_enabled; ?></option>
                            <option value="0"><?php echo $hrx_m_generic_disabled; ?></option>
                            <?php else: ?>
                            <option value="1"><?php echo $hrx_m_generic_enabled; ?></option>
                            <option value="0" selected="selected"><?php echo $hrx_m_generic_disabled; ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $hrx_m_label_sort_order; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="hrx_m_sort_order" value="<?php echo $hrx_m_sort_order; ?>" id="input-sort-order" class="form-control" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-sort-order-internal"><?php echo $hrx_m_label_sort_order_internal; ?></label>
                    <div class="col-sm-10">
                        <select name="hrx_m_sort_order_internal" id="input-sort-order-internal" class="form-control">
                            <?php foreach ($internal_sort_orders as $internal_sort_order_id => $internal_sort_order): ?>
                                <option value="<?php echo $internal_sort_order_id; ?>" <?php if ($hrx_m_sort_order_internal === $internal_sort_order_id): ?>selected="selected"<?php endif; ?>><?php echo $internal_sort_order; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="panel-footer clearfix">
            <div class="pull-right">
                <button type="submit" form="form-hrx_m" data-toggle="tooltip" title="<?php echo $hrx_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
            </div>
        </div>
    </div>
</div>