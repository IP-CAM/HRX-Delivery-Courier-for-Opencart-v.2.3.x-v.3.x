<div data-hrx-order-panel class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			<span class="hrx-panel-logo-wrapper"><img src="view/image/hrx_m/logo-hrx.svg" alt="Logo" style="height: 33px;"></span>
			<?php echo $hrx_m_panel_title; ?>
		</h3>
		<?php if ($hrx_order->getHrxOrderId()): ?>
			<div class="hrx-panel-actions">
				<div class="btn-group">
					<button type="button" class="btn btn-default btn-hrx dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<i class="fa fa-print"></i>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu dropdown-menu-right">
						<li>
							<a href="#" data-print-label data-label-type="shipment"><?php echo $hrx_m_btn_print_label; ?></a>
						</li>
						<?php if ($hrx_order->canPrintReturnLabel()): ?>
							<li>
								<a href="#" data-print-label-return data-label-type="return"><?php echo $hrx_m_btn_print_label_return; ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="panel-body">

		<?php if (!$box_size['fits']): ?>
			<div class="hrx-alert alert-warning">
				<i class="fa fa-exclamation-circle"></i>
				<?php echo $hrx_m_warning_does_not_fit; ?>
			</div>
		<?php endif; ?>
		
		<?php if ($notify_missing_warehouse): ?>
			<div class="hrx-alert alert-warning">
				<i class="fa fa-exclamation-circle"></i>
				<?php echo $notify_missing_warehouse; ?>
			</div>
		<?php endif; ?>

		<ul class="container-fluid nav nav-tabs">
			<li class="active">
				<a href="#hrx-tab-order-general" data-toggle="tab"><?php echo $hrx_m_panel_tab_general; ?></a>
			</li>
			<?php if ($hrx_order->getHrxOrderId()): ?>
			<li>
				<a href="#hrx-tab-order-tracking" data-toggle="tab"><?php echo $hrx_m_panel_tab_tracking_events; ?></a>
			</li>
			<?php endif; ?>
		</ul>

		<div class="tab-content">
			<div class="tab-pane active" id="hrx-tab-order-general">
				<div class="form-horizontal">
					<div class="form-group">
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_hrx_order_status; ?></label>
						<?php if (!$hrx_order->getHrxOrderId() || $hrx_order->isCancelled() || $hrx_order->canBeCancelled()): ?>
							<div class="col-sm-6">
								<pre><?php echo $hrx_m_panel_order_status; ?></pre>
							</div>
						<?php else: ?>
							<div class="col-sm-10">
								<pre><?php echo $hrx_m_panel_order_status; ?></pre>
							</div>
						<?php endif; ?>

						<?php if (!$hrx_order->getHrxOrderId() || $hrx_order->isCancelled()): ?>
							<?php /* only enable register button if parcel box_size.fits limitations */ ?>
							<div class="col-sm-4">
								<button data-register-order class="btn btn-default btn-hrx" <?php if (!$box_size['fits']): ?> disabled <?php endif; ?>><?php echo $hrx_m_panel_btn_register; ?></button>
							</div>
						<?php elseif ($hrx_order->canBeCancelled()): ?>
							<div class="col-sm-4">
								<button data-cancel-order class="btn btn-danger"><?php echo $hrx_m_btn_cancel_order; ?></button>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<?php if ($hrx_order->getHrxOrderId() && !$hrx_order->isCancelled() && $hrx_order->getHrxTrackingNumber()): ?>
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_hrx_track_nr; ?></label>
							<div class="col-sm-10">
								<div class="form-control">
									<a href="<?php echo $hrx_order->getHrxTrackingUrl(); ?>" target="_blank"><?php echo $hrx_order->getHrxTrackingNumber(); ?></a>
								</div>
							</div>
						</div>
					</div>
				<?php endif ?>

				<?php 
					$disabled = '';
					if ($hrx_order->getHrxOrderId() && !$hrx_order->isCancelled()) {
						$disabled = 'disabled';
					}
				?>

				<div class="form-horizontal">
					<div class="form-group">
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_warehouse; ?></label>
						<div class="col-sm-10">
							<select class="form-control" name="hrx_warehouse" data-value="<?php echo $selected_warehouse; ?>" onchange="HRX_M_ORDER.markChange(this)" <?php echo $disabled; ?>>
								<?php foreach ($warehouses as $option): ?>
									<option value="<?php echo $option->id; ?>" <?php if ($option->id == $selected_warehouse): ?> selected <?php endif; ?>><?php echo $option->getNameWithAddress(); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<?php if ($delivery_point->getId()): ?>
						<div class="form-group">
							<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_terminal; ?></label>
							<div class="col-sm-10">
								<div class="form-control"><?php echo $delivery_point->getFormatedAddress(); ?></div>
							</div>
						</div>
					<?php endif; ?>

					<div class="form-group">
						<?php if ($parcel_dimensions == 'saved'): ?>
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_box_size_saved; ?></label>
						<?php elseif ($parcel_dimensions == 'registered'): ?>
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_box_size_registered; ?></label>
						<?php else: ?>
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_box_size_predicted; ?></label>
						<?php endif; ?>
						<div class="col-sm-10 hrx-order-dimensions">
							<div class="input-group">
								<span class="input-group-addon"><?php echo $hrx_m_panel_label_length; ?></span>
								<input type="number" step="1" name="hrx_length" class="form-control" data-value="<?php echo $box_size['length']; ?>" value="<?php echo $box_size['length']; ?>" onchange="HRX_M_ORDER.markChange(this)" <?php echo $disabled; ?>>
								<span class="input-group-addon">cm</span>
							</div>

							<div class="input-group">
								<span class="input-group-addon"><?php echo $hrx_m_panel_label_width; ?></span>
								<input type="number" step="1" name="hrx_width" class="form-control" data-value="<?php echo $box_size['width']; ?>" value="<?php echo $box_size['width']; ?>" onchange="HRX_M_ORDER.markChange(this)" <?php echo $disabled; ?>>
								<span class="input-group-addon">cm</span>
							</div>

							<div class="input-group">
								<span class="input-group-addon"><?php echo $hrx_m_panel_label_height; ?></span>
								<input type="number" step="1" name="hrx_height" class="form-control" data-value="<?php echo $box_size['height']; ?>" value="<?php echo $box_size['height']; ?>" onchange="HRX_M_ORDER.markChange(this)" <?php echo $disabled; ?>>
								<span class="input-group-addon">cm</span>
							</div>

							<div class="input-group">
								<span class="input-group-addon"><?php echo $hrx_m_panel_label_weight; ?></span>
								<input type="number" step="0.01" name="hrx_weight" class="form-control" data-value="<?php echo $box_size['weight']; ?>" value="<?php echo $box_size['weight']; ?>" onchange="HRX_M_ORDER.markChange(this)" <?php echo $disabled; ?>>
								<span class="input-group-addon">kg</span>
							</div>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_comment; ?></label>
						<div class="col-sm-10">
							<input type="text" name="hrx_comment" class="form-control" data-value="<?php echo $hrx_order->getComment(); ?>" value="<?php echo $hrx_order->getComment(); ?>" onchange="HRX_M_ORDER.markChange(this)" <?php echo $disabled; ?>>
						</div>
					</div>

					<div class="form-group">
						<h4 class="col-sm-12"><?php echo $hrx_m_panel_title_dimensions_limitations; ?></h4>
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_limit_dimensions; ?></label>
						<div class="col-sm-4">
							<div class="form-control">Min: <?php echo $delivery_point->getMinDimensions(); ?> Max: <?php echo $delivery_point->getMaxDimensions(); ?></div>
						</div>
						<label class="col-sm-2 control-label"><?php echo $hrx_m_panel_label_limit_weight; ?></label>
						<div class="col-sm-4">
							<div class="form-control">Min: <?php echo $delivery_point->getMinWeight(); ?> Max: <?php echo $delivery_point->getMaxWeight(); ?></div>
						</div>
					</div>

				</div>
			</div> <!-- tab-pane -->
			
			<?php if ($hrx_order->getHrxOrderId()): ?>
				<div class="tab-pane" id="hrx-tab-order-tracking">
					<button data-get-tracking-info class="btn btn-default"><?php echo $hrx_m_panel_btn_load_events; ?></button>
					<div class="table-responsive" data-hrx-tracking-events>
						<?php echo $events_partial; ?>
					</div>
				</div>
			<?php endif; ?>
		</div> <!-- tab-content -->
	</div> <!-- panel-body -->
	<div class="panel-footer">
		<?php if ($hrx_order->getHrxOrderId() && !$hrx_order->isCancelled()): ?>
			<button data-refresh-order="<?php echo $hrx_order->getHrxOrderId(); ?>" class="btn btn-default"><?php echo $hrx_m_btn_refresh_order; ?></button>

			<?php if ($hrx_order->canUpdateReadyState() && !$hrx_order->isMarkedForPickup()): ?>
				<button data-change-order-state="1" class="btn btn-default"><?php echo $hrx_m_btn_ready_for_pickup; ?></button>
			<?php endif; ?>

			<?php if ($hrx_order->canUpdateReadyState() && $hrx_order->isMarkedForPickup()): ?>
				<button data-change-order-state="0" class="btn btn-default"><?php echo $hrx_m_btn_cancel_pickup; ?></button>
			<?php endif; ?>
		<?php else: ?>
			<button data-edit-order class="btn btn-success" disabled><?php echo $hrx_m_panel_btn_edit; ?></button>
		<?php endif; ?>
	</div>
</div>
