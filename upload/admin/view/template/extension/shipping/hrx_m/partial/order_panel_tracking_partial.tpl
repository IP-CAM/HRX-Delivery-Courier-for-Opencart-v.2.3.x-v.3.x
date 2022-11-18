<table class="table">
	<thead>
		<tr>
			<th><?php echo $hrx_m_panel_events_col_timestamp; ?></th>
			<th><?php echo $hrx_m_panel_events_col_location; ?></th>
			<th><?php echo $hrx_m_panel_events_col_event; ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if (empty($track_events)): ?>
			<tr>
				<td colspan="3"><?php if ($is_placeholder): ?><?php echo $hrx_m_panel_notify_load_events; ?><?php else: ?><?php echo $hrx_m_panel_notify_no_events; ?><?php endif; ?></td>
			</tr>
        <?php else: ?>
			<?php foreach ($track_events as $track_event): ?>
			<tr>
				<th scope="row"><?php echo $track_event['timestamp']; ?></th>
				<td><?php echo $track_event['location']; ?></td>
				<td><?php echo $track_event['event']; ?></td>
			</tr>    
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
