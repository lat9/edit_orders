<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024 The zen-cart developers
//
// Last modified v5.0.0
//
use Zencart\Plugins\Admin\EditOrders\EoAttributes;

$uprid = $_POST['uprid'] ?? '';

// -----
// Retrieve the original and updated versions of the requested product.
//
$original_product = $_SESSION['eoChanges']->getOriginalProductByUprid($uprid);
$updated_product = $_SESSION['eoChanges']->getUpdatedProductByUprid($uprid);
?>
<form class="form-horizontal" method="post" action="javascript:void(0);">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title text-center"><?= TEXT_PRODUCT_UPDATE_MODAL_TITLE ?></h4>
    </div>

    <div class="modal-body">
        <div id="eo-prod-messages"></div>
<?php
if (empty($original_product) && empty($updated_product)) {
    $update_button = '';
?>
        <p class="text-center text-danger"><?= sprintf(ERROR_PRODUCT_NOT_FOUND, $uprid) ?></p>
<?php
} else {
    $update_button = '<button id="eo-prod-add-update" class="btn btn-warning mx-2">' . IMAGE_UPDATE . '</button>';
    $update_button .= zen_draw_hidden_field('uprid', $uprid) . zen_draw_hidden_field('payment_calc_method', $_POST['payment_calc_method']);
?>
        <div class="row">
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_ORIGINAL_ORDER ?></h5>
<?php
    if (count($original_product) === 0) {
?>
                <p><?= TEXT_PRODUCT_BEING_ADDED ?></p>
<?php
    } else {
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-qty-avail-o"></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('qty_avail', '', 'id="prod-qty-avail-o" class="form-control invisible" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-qty-o"><?= TEXT_LABEL_QTY ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('qty', $original_product['qty'], 'id="prod-qty-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-model-o"><?= TEXT_LABEL_MODEL ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('model', $original_product['model'], 'id="prod-model-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-name-o"><?= TEXT_LABEL_NAME ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('name', $original_product['name'], 'id="prod-name-o" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
        if (isset($original_product['attributes'])) {
?>
                <div class="panel">
                    <div class="panel-heading text-center"><?= TEXT_PRODUCT_ATTRIBUTES ?></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label col-sm-3" for="prod-otc-o"><?= TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?></label>
                            <div class="col-sm-9">
                                <?= zen_draw_input_field('onetime_charges', $original_product['onetime_charges'], 'id="prod-otc-o" class="form-control" disabled') ?>
                            </div>
                        </div>
                    </div>
<?php
            foreach ($original_product['attributes'] as $next_attribute) {
            }
?>
                </div>
<?php
        }
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-tax-o"><?= TEXT_LABEL_TAX ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('tax', $original_product['tax'], 'id="prod-tax-o" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
        $final_price = $original_product['final_price'];
        $final_price_label = rtrim((DISPLAY_PRICE_WITH_TAX === 'true') ? TABLE_HEADING_UNIT_PRICE_NET : TABLE_HEADING_UNIT_PRICE, ':');
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-price-net-o"><?= $final_price_label ?>:</label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('final_price', $final_price, 'id="prod-price-net-o" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
        if (DISPLAY_PRICE_WITH_TAX === 'true') {
            $gross_price = zen_add_tax($final_price, $original_product['tax']);
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-price-gross-o"><?= rtrim(TABLE_HEADING_UNIT_PRICE_GROSS, ':') ?>:</label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('gross_price', $gross_price, 'id="prod-price-gross-o" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
        }
    }
?>
            </div>
<?php
    // -----
    // Determine whether prices can be manually updated.
    //
    $price_entry_disabled = ($_POST['payment_calc_method'] === 'Manual') ? '' : 'disabled';

    // -----
    // Initialize the attributes for product-related input fields.
    //
    $name_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '"';
    $model_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '"';

    $qty_available = $eo->getProductsStock($updated_product['uprid']);
?>
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_UPDATED_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-qty-avail"><?= TEXT_LABEL_QTY_AVAIL ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('qty_avail', $qty_available, 'id="prod-qty-avail" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-qty"><?= TEXT_LABEL_QTY ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('qty', $updated_product['qty'], 'id="prod-qty" class="form-control"') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-model"><?= TEXT_LABEL_MODEL ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('model', $updated_product['model'], 'id="prod-model" class="form-control" ' . $model_params) ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-name"><?= TEXT_LABEL_NAME ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('name', $updated_product['name'], 'id="prod-name" class="form-control" ' . $name_params) ?>
                    </div>
                </div>
<?php
    if (isset($updated_product['attributes'])) {
?>
                <div class="panel">
                    <div class="panel-heading text-center"><?= TEXT_PRODUCT_ATTRIBUTES ?></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label col-sm-3" for="prod-otc"><?= TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?></label>
                            <div class="col-sm-9">
                                <?= zen_draw_input_field('onetime_charges', $updated_product['onetime_charges'], 'id="prod-otc" class="form-control" min="0" step="any" ' . $price_entry_disabled, false, 'number') ?>
                            </div>
                        </div>
                    </div>
<?php
        $eo_attribs = new EoAttributes((int)$updated_product['id']);
        foreach ($updated_product['attributes'] as $next_attribute) {
        }
?>
                </div>
<?php
    }
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-tax"><?= TEXT_LABEL_TAX ?></label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('tax', $updated_product['tax'], 'id="prod-tax" class="form-control" min="0" max="100" step="any"', false, 'number') ?>
                    </div>
                </div>
<?php
    $final_price = $updated_product['final_price'];
    $final_price_label = rtrim((DISPLAY_PRICE_WITH_TAX === 'true') ? TABLE_HEADING_UNIT_PRICE_NET : TABLE_HEADING_UNIT_PRICE, ':');
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-price-net"><?= $final_price_label ?>:</label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('final_price', $final_price, 'id="prod-price-net" class="form-control" min="0" step="any" ' . $price_entry_disabled, false, 'number') ?>
                    </div>
                </div>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $updated_product['tax']);
?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="prod-price-gross"><?= rtrim(TABLE_HEADING_UNIT_PRICE_GROSS, ':') ?>:</label>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field('gross_price', $gross_price, 'id="prod-price-gross" class="form-control" min="0" step="any" ' . $price_entry_disabled, false, 'number') ?>
                    </div>
                </div>
<?php
    }
?>
            </div>

        </div>
<?php
}
?>
    </div>

    <div class="modal-footer">
        <?= $update_button ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
    </div>
</form>
