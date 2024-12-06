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
<form id="prod-update-form" class="form-horizontal" method="post" action="javascript:void(0);">
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
    $update_button = '<button id="eo-prod-update" class="btn btn-warning mx-2">' . IMAGE_UPDATE . '</button>';
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
                    <label class="control-label col-sm-2" for="prod-qty-avail-o"></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('qty_avail', '', 'id="prod-qty-avail-o" class="form-control invisible" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-qty-o"><?= TEXT_LABEL_QTY ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('qty', $original_product['qty'], 'id="prod-qty-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-model-o"><?= TEXT_LABEL_MODEL ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('model', $original_product['model'], 'id="prod-model-o" class="form-control" disabled') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-name-o"><?= TEXT_LABEL_NAME ?></label>
                    <div class="col-sm-10">
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
                            <label class="control-label col-sm-2" for="prod-otc-o"><?= TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_input_field('onetime_charges', $original_product['onetime_charges'], 'id="prod-otc-o" class="form-control" disabled') ?>
                            </div>
                        </div>

<?php
            foreach ($original_product['attributes'] as $next_attribute) {
                $for = 'prod-a-' . $next_attribute['option_id'] . '-' . $next_attribute['value_id'] . '-o';
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= zen_output_string_protected($next_attribute['option']) ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_input_field($for, zen_output_string_protected($next_attribute['value']), 'id="' . $for . '" class="form-control" disabled') ?>
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
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-tax-o"><?= TEXT_LABEL_TAX ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('tax', $original_product['tax'], 'id="prod-tax-o" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
        $final_price = $original_product['final_price'];
        $final_price_label = rtrim((DISPLAY_PRICE_WITH_TAX === 'true') ? TABLE_HEADING_UNIT_PRICE_NET : TABLE_HEADING_UNIT_PRICE, ':');
?>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-price-net-o"><?= $final_price_label ?>:</label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('final_price', $final_price, 'id="prod-price-net-o" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
        if (DISPLAY_PRICE_WITH_TAX === 'true') {
            $gross_price = zen_add_tax($final_price, $original_product['tax']);
?>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-price-gross-o"><?= rtrim(TABLE_HEADING_UNIT_PRICE_GROSS, ':') ?>:</label>
                    <div class="col-sm-10">
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

    $qty_available = $eo->getProductsAvailableStock($updated_product['uprid'], $updated_product['attributes'] ?? []);
?>
            <div class="col-sm-6">
                <h5 class="text-center"><?= TEXT_UPDATED_ORDER ?></h5>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-qty-avail"><?= TEXT_LABEL_QTY_AVAIL ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('qty_avail', $qty_available, 'id="prod-qty-avail" class="form-control" disabled') ?>
                    </div>
                </div>
<?php
    $max_qty = '';
    if (STOCK_ALLOW_CHECKOUT === 'false') {
        $max_qty = ' max="' . ($original_product['qty'] ?? 0) + $qty_available . '"';
    }
?>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-qty"><?= TEXT_LABEL_QTY ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('qty', $updated_product['qty'], 'id="prod-qty" class="form-control" min="0" step="any"' . $max_qty, false, 'number') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-model"><?= TEXT_LABEL_MODEL ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('model', $updated_product['model'], 'id="prod-model" class="form-control" ' . $model_params) ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-name"><?= TEXT_LABEL_NAME ?></label>
                    <div class="col-sm-10">
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
                            <label class="control-label col-sm-2" for="prod-otc"><?= TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_input_field(
                                    'onetime_charges',
                                    $updated_product['onetime_charges'],
                                    'id="prod-otc" class="form-control" min="0" step="any" ' . $price_entry_disabled,
                                    false,
                                    'number') ?>
                            </div>
                        </div>
<?php
        // -----
        // Retrieve all attributes associated with the current product.
        //
        $attribs = new EoAttributes($updated_product);

        foreach ($attribs->getOptionsValues() as $option_id => $option_info) {
            $for = "attrib-$option_id";
            switch ($option_info['type']) {
                case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
                case PRODUCTS_OPTIONS_TYPE_RADIO:
                case PRODUCTS_OPTIONS_TYPE_SELECT:
                case PRODUCTS_OPTIONS_TYPE_SELECT_SBA:
                    $options_values_array = [];
                    $default_option = $attribs->getOptionCurrentValueId($option_id);
                    foreach ($option_info['values'] as $value_id => $value_info) {
                        $default_option ??= $value_id;
                        $options_values_array[] = [
                            'id' => $value_id,
                            'text' => $value_info['name'],
                        ];
                    }
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_pull_down_menu("id[$option_id]", $options_values_array, $default_option, 'id="' . $for . '" class="form-control"') ?>
                            </div>
                        </div>
<?php
                    break;

                case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
<?php
                    foreach ($option_info['values'] as $value_id => $value_info) {
                        $checked = $attribs->isOptionValueSelected($option_id, $value_id);
?>
                                <label class="checkbox-inline">
                                    <?= zen_draw_checkbox_field("id[$option_id][$value_id]", 'on', $checked) ?>
                                    <?= $value_info['name'] ?>
                                </label>
<?php
                    }
?>
                            </div>
                        </div>
<?php
                    break;

                case PRODUCTS_OPTIONS_TYPE_TEXT:
                    $text = zen_output_string_protected($attribs->getOptionCurrentValue($option_id));
                    $size = $option_info['size'];
                    $length = $option_info['length'];
                    $rows = $option_info['rows'];
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
<?php
                    if ($rows > 1) {
                        echo zen_draw_textarea_field("id[$option_id]", 'hard', $size, $rows, $text, 'id="' . $for . '" class="form-control"');
                    } else {
                        echo zen_draw_input_field("id[$option_id]", $text, 'id="' . $for . '" class="form-control" size="' . $size . '" maxlength="' . $length . '"');
                    }
?>
                            </div>
                        </div>
<?php
                    break;

                case PRODUCTS_OPTIONS_TYPE_FILE:
                    $option_value = $attribs->getOptionCurrentValue($option_id);
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_input_field('unused', $option_value ?? TEXT_FILE_UPLOAD_NOT_SUPPORTED, 'id="' . $for . '" class="form-control" disabled') ?>
                                <?= zen_draw_hidden_field("id[$option_id]", $option_value ?? '') ?>
                            </div>
                        </div>
<?php
                    break;

                case PRODUCTS_OPTIONS_TYPE_READONLY:
                    foreach ($option_info['values'] as $values_id => $values_name) {
                        $option_value = $values_name . TEXT_ATTRIBUTE_READONLY;
                        break;
                    }
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_input_field('unused', $option_value, 'id="' . $for . '" class="form-control" disabled') ?>
                            </div>
                        </div>
<?php
                    break;

                default:
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
                                <?= zen_draw_input_field('unused', sprintf(TEXT_ATTRIBUTES_UNKNOWN_OPTION_TYPE, (int)$option_info['type']), 'id="' . $for . '" class="form-control" disabled') ?>
                            </div>
                        </div>
<?php
                    break;
            }
        }
?>
                    </div>
                </div>
<?php
    }
?>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-tax"><?= TEXT_LABEL_TAX ?></label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('tax', $updated_product['tax'], 'id="prod-tax" class="form-control" min="0" max="100" step="any"', false, 'number') ?>
                    </div>
                </div>
<?php
    $final_price = $updated_product['final_price'];
    $final_price_label = rtrim((DISPLAY_PRICE_WITH_TAX === 'true') ? TABLE_HEADING_UNIT_PRICE_NET : TABLE_HEADING_UNIT_PRICE, ':');
?>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-price-net"><?= $final_price_label ?>:</label>
                    <div class="col-sm-10">
                        <?= zen_draw_input_field('final_price', $final_price, 'id="prod-price-net" class="form-control" min="0" step="any" ' . $price_entry_disabled, false, 'number') ?>
                    </div>
                </div>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $updated_product['tax']);
?>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="prod-price-gross"><?= rtrim(TABLE_HEADING_UNIT_PRICE_GROSS, ':') ?>:</label>
                    <div class="col-sm-10">
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
