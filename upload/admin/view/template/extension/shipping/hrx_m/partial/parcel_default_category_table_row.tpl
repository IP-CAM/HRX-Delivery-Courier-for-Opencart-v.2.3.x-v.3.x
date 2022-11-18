<tr data-row-category-id="<?php echo $oc_category_row['category_id']; ?>" data-parcel-default="<?php if ($oc_category_row['hrx_parcel_default']): ?><?php echo $oc_category_row['hrx_parcel_default']->getAttributesJsonAsBase64(); ?><?php endif; ?>">
	<td><?php echo $oc_category_row['category_id']; ?></td>
	<td data-category-name><?php echo $oc_category_row['name']; ?></td>
	<td>
		<?php if ($oc_category_row['hrx_parcel_default']): ?>
			<span class="has-parcel-default"><?php echo $oc_category_row['hrx_parcel_default']->length; ?> x <?php echo $oc_category_row['hrx_parcel_default']->width; ?> x <?php echo $oc_category_row['hrx_parcel_default']->height; ?>, <?php echo $oc_category_row['hrx_parcel_default']->weight; ?>kg</span>
		<?php else: ?>
			<span class="no-parcel-default"></span>
		<?php endif; ?>
	</td>
	<td>
		<button data-modal="#parcel_default_modal" type="button" class="btn btn-default" data-parcel-default-edit="<?php echo $oc_category_row['category_id']; ?>"><?php echo $hrx_m_pd_btn_edit; ?></button>

		<?php if ($oc_category_row['hrx_parcel_default']): ?>
			<button type="button" class="btn btn-default" data-parcel-default-reset="<?php echo $oc_category_row['category_id']; ?>"><?php echo $hrx_m_pd_btn_reset; ?></button>
		<?php endif; ?>
	</td>
</tr>
