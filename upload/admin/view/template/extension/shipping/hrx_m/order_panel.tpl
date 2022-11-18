<link rel="stylesheet" href="view/javascript/hrx_m/admin.css?20220920">
<?php echo $hrx_order_panel_partial; ?>

<script>
	var HRX_M_ORDER_DATA = {
		urlAjax: '<?php echo $url_ajax; ?>',
		defaultWarehouse: <?php echo json_encode($default_warehouse); ?>,
		orderId: <?php echo $order_id; ?>,
		refreshResult: '<?php echo $refresh_result; ?>',
	};
</script>
<script src="<?php echo $mijora_common_js_path; ?>"></script>
<script src="view/javascript/hrx_m/order.js?20220920" type="text/javascript"></script>
