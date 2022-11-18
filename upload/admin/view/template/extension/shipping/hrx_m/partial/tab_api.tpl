<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-cogs"></i> <?php echo $hrx_m_title_api_settings; ?></h3>
        </div>

        <div class="panel-body">
            <form action="<?php echo $form_action; ?>" method="post" enctype="multipart/form-data" id="form-hrx_m-api" class="form-horizontal">
                <input type="hidden" name="api_settings_update">

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-api-token"><?php echo $hrx_m_label_api_token; ?></label>
                    <div class="col-sm-10">
                        <input type="text" name="hrx_m_api_token" value="<?php echo $hrx_m_api_token; ?>" id="input-api-token" class="form-control" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-api-test-mode"><?php echo $hrx_m_label_api_test_mode; ?></label>
                    <div class="col-sm-10">
                        <select name="hrx_m_api_test_mode" id="input-api-test-mode" class="form-control">
                            <option value="0"><?php echo $hrx_m_generic_no; ?></option>
                            <option value="1" <?php if ($hrx_m_api_test_mode == 1): ?>selected<?php endif; ?>><?php echo $hrx_m_generic_yes; ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="panel-footer clearfix">
            <div class="pull-left">
                <button type="button" data-test-api class="btn btn-default btn-hrx with-text"><i class="fa fa-refresh"></i><?php echo $hrx_m_btn_test_token; ?></button>
            </div>
            <div class="pull-right">
                <button type="submit" form="form-hrx_m-api" data-toggle="tooltip" title="<?php echo $hrx_m_generic_btn_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
            </div>
        </div>
    </div>
</div>