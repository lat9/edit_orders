<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024-2025 The zen-cart developers
//
// Last modified v5.0.0
//
$ot_class = $_POST['ot_class'] ?? 'Unknown';
$updated_order = $_SESSION['eoChanges']->getUpdatedOrder();
?>
<form class="form-horizontal" method="post" action="javascript:void(0);">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title text-center"><?= sprintf(TEXT_OT_ADD_MODAL_TITLE, $ot_class) ?></h4>
    </div>

    <div class="modal-body">
        <div id="eo-ot-messages"></div>
        <div class="row">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
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
        $ot_title = $GLOBALS[$ot_class]->title ?? 'Unknown';
        $credit_selections = $eo->getCreditSelections($ot_class);
        if (empty($credit_selections)) {
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

        foreach ($credit_selections['fields'] as $next_field) {
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="<?= $next_field['tag'] ?>"><?= $next_field['title'] ?></label>
                    <div class="col-sm-9">
                        <?= $next_field['field'] ?>
                    </div>
                </div>
<?php
        }
        break;
}
?>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button id="eo-ot-add-update" class="btn btn-warning mx-2"><?= IMAGE_UPDATE ?></button>
        <?= zen_draw_hidden_field('ot_class', $ot_class) ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
    </div>
</form>
