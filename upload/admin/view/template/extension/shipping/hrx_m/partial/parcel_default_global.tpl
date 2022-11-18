<h4><?php echo $hrx_m_title_parcel_default_global; ?></h4>
<div class="input-group">
	<span class="input-group-addon"><?php echo $hrx_m_label_pd_weight; ?></span>
	<input type="text" class="form-control disabled" value="<?php echo $global_parcel_default->weight; ?>" disabled>
	<span class="input-group-addon">kg</span>
</div>
<div class="input-group">
	<span class="input-group-addon"><?php echo $hrx_m_label_pd_length; ?></span>
	<input type="text" class="form-control disabled" value="<?php echo $global_parcel_default->length; ?>" disabled>
	<span class="input-group-addon">cm</span>
</div>
<div class="input-group">
	<span class="input-group-addon"><?php echo $hrx_m_label_pd_width; ?></span>
	<input type="text" class="form-control disabled" value="<?php echo $global_parcel_default->width; ?>" disabled>
	<span class="input-group-addon">cm</span>
</div>
<div class="input-group">
	<span class="input-group-addon"><?php echo $hrx_m_label_pd_height; ?></span>
	<input type="text" class="form-control disabled" value="<?php echo $global_parcel_default->height; ?>" disabled>
	<span class="input-group-addon">cm</span>
</div>
<div class="form-group">
	<div class="text-right">
		<button type="button" data-modal="#parcel_default_modal" data-parcel-default-edit="<?php echo $global_parcel_default->category_id; ?>" class="btn btn-default"><?php echo $hrx_m_pd_btn_edit_global; ?></button>
	</div>
</div>
