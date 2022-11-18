<h4><?php echo $hrx_m_title_pd_table; ?></h4>
<div class="table-responsive">
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th><?php echo $hrx_m_pd_table_id; ?></th>
				<th><?php echo $hrx_m_pd_table_name; ?></th>
				<th><?php echo $hrx_m_pd_table_has_custom_defaults; ?></th>
				<th><?php echo $hrx_m_pd_table_action; ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($category_list)): ?>
				<tr>
					<td class="text-center" colspan="4"><?php echo $hrx_m_pd_table_no_categories; ?></td>
				</tr>
			<?php else: ?>
				<?php foreach ($category_list as $category_row): ?>
				<?php echo $category_row; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<div class="row">
	<div class="col-sm-12 text-center">
		<?php echo $parcel_default_pagination; ?>
	</div>
</div>
