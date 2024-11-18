<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024 The zen-cart developers
//
// Last modified v5.0.0
//
$ot_class = $_POST['ot_class'] ?? 'Unknown';
?>
<form class="form-horizontal" method="post" action="javascript:void(0);">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?= sprintf(TEXT_OT_UPDATE_MODAL_TITLE, $ot_class) ?></h4>
    </div>

    <div class="modal-body">
        <div id="eo-ot-messages"></div>
<?php
$module_list = explode(';', str_replace('.php', '', MODULE_ORDER_TOTAL_INSTALLED));
if (!in_array($ot_class, $module_list)) {
    $update_button = '';
?>
        <p class="text-center text-danger"><?= sprintf(ERROR_OT_NOT_INSTALLED, $ot_class) ?></p>
<?php
} else {
    $original_order = $_SESSION['eoChanges']->getOriginalOrder();
    $ot_title_o = '';
    $ot_value_o = 0;
    for ($i = 0, $n = count($original_order->totals); $i < $n; $i++) {
        if ($original_order->totals[$i]['class'] === $ot_class) {
            $ot_title_o = $original_order->totals[$i]['title'];
            $ot_value_o = $original_order->totals[$i]['value'];
            break;
        }
    }

    $updated_order = $_SESSION['eoChanges']->getUpdatedOrder();
    $ot_title_u = '';
    $ot_value_u = 0;
    for ($i = 0, $n = count($updated_order->totals); $i < $n; $i++) {
        if (($updated_order->totals[$i]['class'] ?? $updated_order->totals[$i]['code']) === $ot_class) {
            $ot_title_u = $updated_order->totals[$i]['title'];
            $ot_value_u = $updated_order->totals[$i]['value'];
            break;
        }
    }

    $update_button = '<button id="eo-ot-add-update" class="btn btn-warning mx-2">' . IMAGE_UPDATE . '</button>';
    $update_button .= zen_draw_hidden_field('ot_class', $ot_class) . zen_draw_hidden_field('payment_calc_method', 'Manual');
    switch ($ot_class) {
        case 'ot_coupon':
?>
        <div class="row">
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_ORIGINAL_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-title-o"><?= TEXT_LABEL_COUPON_CODE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('dc_redeem_code', $original_order->info['coupon_code'], 'id="ot-title-o" class="form-control" disabled') ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_UPDATED_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-title-u"><?= TEXT_LABEL_COUPON_CODE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('dc_redeem_code', $updated_order->info['coupon_code'], 'id="ot-title-u" class="form-control"') ?>
                    </div>
                </div>
            </div>
        </div>
<?php
            break;

        case 'ot_shipping':
?>
        <div class="row">
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_ORIGINAL_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-module-o"><?= TEXT_LABEL_MODULE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('module', $original_order->info['shipping_module_code'], 'id="ot-module-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-title-o"><?= TEXT_LABEL_METHOD ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('title', $original_order->info['shipping_method'], 'id="ot-title-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-module-o"><?= TEXT_LABEL_TAX ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('tax', $original_order->info['shipping_tax_rate'], 'id="ot-tax-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-value-o"><?= TEXT_LABEL_VALUE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('value', $ot_value_o, 'id="ot-value-o" class="form-control" disabled') ?>
                    </div>
                </div>
            </div>
<?php
    $available_modules = $eo->getAvailableShippingModules($original_order);
    $shipping_module_code = $updated_order->info['shipping_module_code'];
?>
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_UPDATED_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-module-u"><?= TEXT_LABEL_MODULE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_pull_down_menu('module', $available_modules, $shipping_module_code, 'id="ot-module-u" class="form-control"') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-title-u"><?= TEXT_LABEL_METHOD ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('title', $updated_order->info['shipping_method'], 'id="ot-title-u" class="form-control"') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-module-u"><?= TEXT_LABEL_TAX ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('tax', $updated_order->info['shipping_tax_rate'], 'id="ot-tax-u" class="form-control"') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-value-u"><?= TEXT_LABEL_VALUE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('value', $ot_value_u, 'id="ot-value-u" class="form-control"') ?>
                    </div>
                </div>
            </div>
        </div>
<?php
            break;

        default:
?>
        <div class="row">
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_ORIGINAL_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-title-o"><?= TEXT_LABEL_TITLE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('title', $ot_title_o, 'id="ot-title-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-value-o"><?= TEXT_LABEL_VALUE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('value', $ot_value_o, 'id="ot-value-o" class="form-control" disabled') ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_UPDATED_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-title-u"><?= TEXT_LABEL_TITLE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('title', $ot_title_u, 'id="ot-title-u" class="form-control"') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="ot-value-u"><?= TEXT_LABEL_VALUE ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('value', $ot_value_u, 'id="ot-value-u" class="form-control"') ?>
                    </div>
                </div>
            </div>
        </div>
<?php
            break;
    }
}
?>
    </div>

    <div class="modal-footer">
        <?= $update_button ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
    </div>
</form>
