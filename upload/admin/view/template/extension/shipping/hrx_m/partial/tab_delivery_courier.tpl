<div class="container-fluid">
    <div class="panel panel-default">
        <div class="pabel-body panel-hrx-sync-actions">
            <div class="timestamp-wrapper">
                <span class="timestamp-text"><?php echo $hrx_m_delivery_update_text; ?></span>
                <span class="timestamp-date">
                    <?php if ($delivery_locations_last_update): ?>
                        <?php echo $delivery_locations_last_update; ?>
                    <?php else: ?>
                        <?php echo $hrx_m_delivery_updated_never; ?>
                    <?php endif; ?>
                </span>
            </div>
            <div>
                <button type="button" data-refresh-delivery-locations="Courier" class="btn btn-default btn-hrx with-text"><i class="fa fa-refresh"></i><?php echo $hrx_m_btn_refresh_delivery_courier_locations; ?></button>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo $hrx_m_title_delivery_courier_locations; ?></h3>
        </div>

        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th rowspan="2"><?php echo $hrx_m_table_col_country; ?></th>
                            <th colspan="2"><?php echo $hrx_m_table_col_dimmensions; ?></th>
                            <th colspan="2"><?php echo $hrx_m_table_col_weight; ?></th>
                            <th rowspan="2"><?php echo $hrx_m_table_col_active; ?></th>
                        </tr>
                        <tr>
                            <th><?php echo $hrx_m_table_col_min; ?></th>
                            <th><?php echo $hrx_m_table_col_max; ?></th>
                            <th><?php echo $hrx_m_table_col_min; ?></th>
                            <th><?php echo $hrx_m_table_col_max; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($delivery_locations)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="alert alert-info"><?php echo $hrx_m_alert_update_delivery_points_list; ?></div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($delivery_locations as $delivery_location): ?>
                        <tr>
                            <td><?php echo $delivery_location->country; ?></td>
                            <td><?php echo $delivery_location->getMinDimensions(); ?></td>
                            <td><?php echo $delivery_location->getMaxDimensions(); ?></td>
                            <td><?php echo $delivery_location->getMinWeight(); ?></td>
                            <td><?php echo $delivery_location->getMaxWeight(); ?></td>
                            <td>
                                <?php if ($delivery_location->active): ?>
                                    <span class="delivery-point-enabled"></span>
                                <?php else: ?>
                                    <span class="delivery-point-disabled"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> <!-- Delivery locations panel -->

    <div class="row">
        <div class="col-sm-12 text-center">
            <?php echo $delivery_locations_pagination; ?>
        </div>
    </div>
</div>