<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-money"></i> <?php echo $hrx_m_title_price_settings; ?></h3>
        </div>
        <div class="panel-body">
            <p class="help-block"><?php echo $hrx_m_help_price; ?></p>
            <div id="price-table" class="form-horizontal">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-country"><?php echo $hrx_m_label_price_country; ?></label>
                            <div class="col-sm-10">
                                <select name="country" class="js-select2" style="width: 100%" data-placeholder="<?php echo $hrx_m_placeholder_price_country; ?>">
                                    <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo $country['iso_code_2']; ?>"><?php echo $country['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="help-block"><?php echo $hrx_m_help_price_country; ?></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-terminal-price"><?php echo $hrx_m_label_price_terminal; ?></label>
                            <div class="col-sm-4">
                                <input type="text" name="terminal_price" value="" id="input-terminal-price" class="form-control" />
                            </div>

                            <label class="col-sm-2 control-label" for="input-terminal-price-type"><?php echo $hrx_m_label_price_range_type; ?></label>
                            <div class="col-sm-4">
                                <select name="terminal_price_range_type" value="0" id="input-terminal-price-range-type" class="form-control">
                                    <?php foreach ($price_range_types as $range_key => $price_range_type): ?>
                                        <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-courier-price"><?php echo $hrx_m_label_price_courier; ?></label>
                            <div class="col-sm-4">
                                <input type="text" name="courier_price" value="" id="input-courier-price" class="form-control" />
                            </div>

                            <label class="col-sm-2 control-label" for="input-courier-price-type"><?php echo $hrx_m_label_price_range_type; ?></label>
                            <div class="col-sm-4">
                                <select name="courier_price_range_type" value="0" id="input-courier-price-range-type" class="form-control">
                                    <?php foreach ($price_range_types as $range_key => $price_range_type): ?>
                                        <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <button id="add-price-btn" class="btn btn-default center"><?php echo $hrx_m_button_add_price; ?></button>
                        </div>
                    </div> <!-- price panel heading -->

                    <div class="panel-body table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo $hrx_m_label_price_country; ?></th>
                                    <th><?php echo $hrx_m_label_price_terminal; ?></th>
                                    <th><?php echo $hrx_m_label_price_range_type; ?></th>
                                    <th><?php echo $hrx_m_label_price_courier; ?></th>
                                    <th><?php echo $hrx_m_label_price_range_type; ?></th>
                                    <th><?php echo $hrx_m_header_actions; ?></th>
                                </tr>
                            </thead>
                            <tbody id="created-prices">
                                <tr id="no-price-notification" class=" <?php if ($hrx_m_prices): ?>hidden<?php endif; ?> ">
                                    <td colspan="6">No prices set</td>
                                </tr>
                                <?php foreach ($hrx_m_prices as $price): ?>
                                    <?php echo $price; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> <!-- price panel body -->
                </div> <!-- price panel -->
            </div>
        </div> <!-- panel body -->
    </div> <!-- panel -->
</div> <!-- container -->

<!-- Price EDIT Modal -->
<div class="edit-price-modal hidden">
    <div class="panel panel-default col-xs-11 col-md-9 col-lg-7">
        <div class="panel-body form-horizontal">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-modal-country"><?php echo $hrx_m_label_price_country; ?></label>
                <div class="col-sm-10">
                    <input type="hidden" name="country" value="">
                    <input name="country_name" type="text" readonly="" value="" id="input-modal-country" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-modal-terminal-price"><?php echo $hrx_m_label_price_terminal; ?></label>
                <div class="col-sm-4">
                    <input type="text" name="terminal_price" value="" id="input-modal-terminal-price" class="form-control" />
                </div>

                <label class="col-sm-2 control-label" for="input-modal-terminal-price-range-type"><?php echo $hrx_m_label_price_range_type; ?></label>
                <div class="col-sm-4">
                    <select name="terminal_price_range_type" value="0" id="input-modal-terminal-price-range-type" class="form-control">
                        <?php foreach ($price_range_types as $range_key => $price_range_type): ?>
                            <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-modal-courier-price"><?php echo $hrx_m_label_price_courier; ?></label>
                <div class="col-sm-4">
                    <input type="text" name="courier_price" value="" id="input-modal-courier-price" class="form-control" />
                </div>

                <label class="col-sm-2 control-label" for="input-modal-courier-price-range-type"><?php echo $hrx_m_label_price_range_type; ?></label>
                <div class="col-sm-4">
                    <select name="courier_price_range_type" value="0" id="input-modal-courier-price-range-type" class="form-control">
                        <?php foreach ($price_range_types as $range_key => $price_range_type): ?>
                            <option value="<?php echo $range_key; ?>"><?php echo $price_range_type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group text-center">
                <button data-price-edit-save="true" class="btn btn-default center"><?php echo $hrx_m_generic_btn_save; ?></button>
                <button data-price-edit-cancel class="btn btn-default center"><?php echo $hrx_m_generic_btn_cancel; ?></button>
            </div>
        </div>
    </div>
</div>