<div class="panel panel-default panel-manifests">
    <div class="panel-heading">
        <img src="view/image/hrx_m/manifest-table.svg">
        <h3 class="panel-title"><?php echo $hrx_m_title_manifest_orders; ?></h3>
        <div class="hrx-panel-actions">
            <div class="btn-group">
                <button type="button" class="btn btn-default btn-hrx dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-truck"></i>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li>
                        <a href="#" data-mass-action="#state_change" data-order-state="1"><?php echo $hrx_m_btn_ready_for_pickup; ?></a>
                    </li>
                    <li>
                        <a href="#" data-mass-action="#state_change" data-order-state="0"><?php echo $hrx_m_btn_cancel_pickup; ?></a>
                    </li>
                </ul>
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-default btn-hrx dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-print"></i>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li>
                        <a href="#" data-mass-action="#multiple_labels" data-label-type="shipment"><?php echo $hrx_m_btn_print_label; ?></a>
                    </li>
                    <li>
                        <a href="#" data-mass-action="#multiple_labels" data-label-type="return"><?php echo $hrx_m_btn_print_label_return; ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <td style="width: 1px;" class="text-center"><input data-check-all="[name='selected[]']" type="checkbox"/></td>
                        <td class="text-right"><?php echo $hrx_m_manifest_col_order_id; ?></td>
                        <td class="text-left"> <?php echo $hrx_m_manifest_col_customer; ?></td>
                        <td class="text-left"> <?php echo $hrx_m_manifest_col_status; ?></td>
                        <td class="text-right"><?php echo $hrx_m_manifest_col_hrx_data; ?></td>
                        <td class="text-right"><?php echo $hrx_m_manifest_col_action; ?></td>
                    </tr>
                </thead>
                <tbody id="hrx_m-manifest-orders">
                    <?php if (empty($order_rows)): ?>
                        <tr>
                            <td class="text-center" colspan="6"><?php echo $hrx_m_manifest_orders_no_results; ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($order_rows as $order_row): ?>
                            <?php echo $order_row; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-sm-12 text-center">
                <?php echo $manifest_list_pagination; ?>
            </div>
        </div>
    </div>
</div>