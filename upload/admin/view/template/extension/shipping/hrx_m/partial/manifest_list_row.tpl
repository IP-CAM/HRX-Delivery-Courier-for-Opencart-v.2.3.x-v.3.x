<tr data-row-order-id="<?php echo $order->getOrderId(); ?>">
	<td class="text-center">
		<input type="checkbox" name="selected[]" value="<?php echo $order->getOrderId(); ?>">
		<input type="hidden" name="shipping_code[]" value="<?php echo $order->getShippingCode(true); ?>">
	</td>
	<td class="text-right"><?php echo $order->getOrderId(); ?></td>
	<td class="text-left">
		<a target="_blank" href="<?php echo $order_url; ?>&order_id=<?php echo $order->getOrderId(); ?>"><?php echo $order->getCustomer(); ?></a>
	</td>
	<td class="text-left"><?php echo $order->getOrderStatus(); ?></td>
	<td class="text-left hrx-order-data">
		<?php if ($order->getHrxOrderId() && !$order->isCancelled()): ?>
			<p>
				<span><?php echo $hrx_m_hrx_data_id; ?></span>
				<?php echo $order->getHrxOrderId(); ?>
			</p>

			<p>
				<span><?php echo $hrx_m_hrx_data_status; ?></span>
				<?php echo $order->getHrxOrderStatus(); ?>
			</p>

			<p>
				<span><?php echo $hrx_m_hrx_data_deliver; ?></span>
				<?php echo $order->getHrxDeliveryAddress(); ?>
			</p>

			<?php if ($order->getHrxTrackingNumber()): ?>
				<p>
					<span><?php echo $hrx_m_hrx_data_tracking; ?></span>
					<a target="_blank" href="<?php echo $order->getHrxTrackingUrl(); ?>"><?php echo $order->getHrxTrackingNumber(); ?></a>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</td>
	<td>
		<div class="hrx_m-actions-col">
			<?php if (!$order->getHrxOrderId() || $order->isCancelled()): ?>
				<button data-register-order data-oc-order-id="<?php echo $order->getOrderId(); ?>" class="btn btn-default"><?php echo $hrx_m_btn_register_order; ?></button>
			<?php else: ?>
				<div class="btn-group">
					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false"><?php echo $hrx_m_btn_manifest_action; ?>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu dropdown-menu-right">
						<li>
							<a href="#" data-refresh-order="<?php echo $order->getHrxOrderId(); ?>" data-oc-order-id="<?php echo $order->getOrderId(); ?>"><?php echo $hrx_m_btn_refresh_order; ?></a>
						</li>

						<li role="separator" class="divider"></li>

						<li>
							<a href="#" data-print-label data-label-type="shipment" data-oc-order-id="<?php echo $order->getOrderId(); ?>"><?php echo $hrx_m_btn_print_label; ?></a>
						</li>
						<?php if ($order->canPrintReturnLabel()): ?>
							<li>
								<a href="#" data-print-label-return data-label-type="return" data-oc-order-id="<?php echo $order->getOrderId(); ?>"><?php echo $hrx_m_btn_print_label_return; ?></a>
							</li>
						<?php endif; ?>

						<li role="separator" class="divider"></li>

						<?php if ($order->canUpdateReadyState() && !$order->isMarkedForPickup()): ?>
							<li>
								<a href="#" data-order-state-btn data-change-order-state="1" data-oc-order-id="<?php echo $order->getOrderId(); ?>"><?php echo $hrx_m_btn_ready_for_pickup; ?></a>
							</li>
						<?php endif; ?>
						<?php if ($order->canUpdateReadyState() && $order->isMarkedForPickup()): ?>
							<li>
								<a href="#" data-order-state-btn data-change-order-state="0" data-oc-order-id="<?php echo $order->getOrderId(); ?>"><?php echo $hrx_m_btn_cancel_pickup; ?></a>
							</li>
						<?php endif; ?>

						<li role="separator" class="divider"></li>

						<?php if ($order->canBeCancelled()): ?>
							<li>
								<a href="#" data-cancel-order data-oc-order-id="<?php echo $order->getOrderId(); ?>"><?php echo $hrx_m_btn_cancel_order; ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</td>
</tr>
