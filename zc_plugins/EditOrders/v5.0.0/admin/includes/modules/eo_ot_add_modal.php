<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024 The zen-cart developers
//
// Last modified v5.0.0
//
$ot_class = $_POST['ot_class'] ?? 'Unknown';
$updated_order = $_SESSION['eoChanges']->getUpdatedOrder();
?>
<form class="form-horizontal" method="post" action="javascript:void(0);">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?= sprintf(TEXT_OT_ADD_MODAL_TITLE, $ot_class) ?></h4>
    </div>

    <div class="modal-body">
        <div id="eo-ot-messages"></div>
<?php
switch ($ot_class) {
    case 'ot_coupon':
?>
        <div class="form-group">
            <label class="control-label col-sm-3" for="ot-title"><?= TEXT_LABEL_COUPON_CODE ?></label>
            <div class="col-sm-9">
                <?= zen_draw_input_field('dc_redeem_code', $updated_order->info['coupon_code'], 'id="ot-title" class="form-control"') ?>
            </div>
        </div>
<?php
        break;

    default:
        $unused_order_totals = $eo->getUnusedOrderTotalModules($updated_order);
        $ot_title = 'Unknown';
        foreach ($unused_order_totals as $i => $next_total) {
            if ($next_total['id'] === $ot_class) {
                $ot_title = $next_total['text'];
                break;
            }
        }
?>
        <div class="form-group">
            <label class="control-label col-sm-3" for="ot-title"><?= TEXT_LABEL_TITLE ?></label>
            <div class="col-sm-9">
                <?= zen_draw_input_field('title', $ot_title, 'id="ot-title" class="form-control"') ?>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-3" for="ot-value"><?= TEXT_LABEL_VALUE ?></label>
            <div class="col-sm-9">
                <?= zen_draw_input_field('value', '0.00', 'id="ot-value" class="form-control"') ?>
            </div>
        </div>
<?php
        break;
}
?>
    </div>

    <div class="modal-footer">
        <button id="eo-ot-add-update" class="btn btn-warning mx-2"><?= IMAGE_UPDATE ?></button>
        <?= zen_draw_hidden_field('ot_class', $ot_class) . zen_draw_hidden_field('payment_calc_method', 'Manual') ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
    </div>
</form>
