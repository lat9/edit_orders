<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024 The zen-cart developers
//
// Last modified v5.0.0
//
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title text-center"><?= TEXT_PRODUCT_ADD_MODAL_TITLE ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div id="prod-add-choose" class="col-sm-6">
            <h5 class="text-center"><?= TEXT_PRODUCT_CHOOSE_SUBTITLE ?></h5>

            <div id="prod-choose-id" class="panel panel-info">
                <div class="panel-heading text-center fw-bold"><?= TEXT_PRODUCT_CHOOSE_BY_ID ?></div>
                <div class="panel-body">
                    <form class="form-horizontal" method="post" action="javascript:void(0);">
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="prod-id-prid"><?= rtrim(TEXT_PRODUCTS_ID, ' :') ?>:</label>
                            <div class="col-sm-8">
                                <?= zen_draw_input_field('prid', ($prid === 0) ? '' : $prid, 'id="prod-id-prid" class="form-control"') ?>
                            </div>
                            <div class="col-sm-2 text-right">
                                <button class="btn btn-primary prod-add"><?= BUTTON_CHOOSE ?></button>
                                <?= zen_draw_hidden_field('choose_form', 'id') ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <div id="prod-add-details" class="col-sm-6">
            <form id="prod-add-form" class="form-horizontal" method="post" action="javascript:void(0);">
<?php
if (!empty($new_product)) {
    // -----
    // Initialize the attributes for product-related input fields.
    //
    $name_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '"';
    $model_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '"';
    $uprid = $new_product['uprid'];

    $qty_available = $eo->getProductsAvailableStock($uprid, $_SESSION['cart']->contents[$uprid]['attributes'] ?? []);
?>
            <h5 class="text-center"><?= TEXT_PRODUCT_NEW_MODAL_TITLE ?></h5>

            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-qty-avail"><?= TEXT_LABEL_QTY_AVAIL ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('qty_avail', $qty_available, 'id="prod-qty-avail" class="form-control" disabled') ?>
                </div>
            </div>

            <div id="prod-messages"></div>
<?php
    $max_qty = '';
    if (STOCK_ALLOW_CHECKOUT === 'false') {
        $max_qty = ' max="' . $qty_available . '"';
    }
?>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-qty"><?= TEXT_LABEL_QTY ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('qty', $new_product['qty'], 'id="prod-qty" class="form-control" min="0" step="any"' . $max_qty, false, 'number') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-model"><?= TEXT_LABEL_MODEL ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('model', $new_product['model'], 'id="prod-model" class="form-control" ' . $model_params) ?>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-name"><?= TEXT_LABEL_NAME ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('name', $new_product['name'], 'id="prod-name" class="form-control" ' . $name_params) ?>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-tax"><?= TEXT_LABEL_TAX ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('tax', $new_product['tax'], 'id="prod-tax" class="form-control" disabled', false, 'number') ?>
                </div>
            </div>
<?php
    $final_price = $new_product['final_price'];
    $final_price_label = rtrim((DISPLAY_PRICE_WITH_TAX === 'true') ? TABLE_HEADING_UNIT_PRICE_NET : TABLE_HEADING_UNIT_PRICE, ':');
?>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-price-net"><?= $final_price_label ?>:</label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('final_price', $final_price, 'id="prod-price-net" class="form-control" disabled', false, 'number') ?>
                </div>
            </div>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $new_product['tax']);
?>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-price-gross"><?= rtrim(TABLE_HEADING_UNIT_PRICE_GROSS, ':') ?>:</label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('gross_price', $gross_price, 'id="prod-price-gross" class="form-control" disabled', false, 'number') ?>
                </div>
            </div>
<?php
    }

    // -----
    // If the to-be-added product has attributes, bring in the attribute-formatting
    // that's common to a product's update and addition.
    //
    // Note: No directory specified for the 'require', since the module is in the same
    // directory as this modal-display handler.
    //
    if (isset($new_product['attributes'])) {
        $updated_product = $new_product;
        require 'eo_attributes_display.php';
    }
?>
                <div class="row text-right">
                    <button id="recalculate-pricing" class="btn btn-info"><?= BUTTON_RECALCULATE ?></button>
                    <button id="add-to-order" class="btn btn-warning"><?= BUTTON_ADD ?></button>
                    <?= zen_draw_hidden_field('prid', $prid) ?>
                    <?= zen_draw_hidden_field('choose_form', $choose_form) ?>
                </div>
            </form>
        </div>
<?php
}
?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
</div>
