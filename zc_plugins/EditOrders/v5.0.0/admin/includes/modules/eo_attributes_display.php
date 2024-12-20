<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024 The zen-cart developers
//
// Last modified v5.0.0
//
use Zencart\Plugins\Admin\EditOrders\EoAttributes;
?>
                <div class="panel panel-info dataTableRow">
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

                        <div id="attrib-messages"></div>
<?php
// -----
// Retrieve all attributes associated with the current product.
//
$attribs = new EoAttributes((int)$uprid, $updated_product['attributes']);

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
                                    <?= zen_draw_checkbox_field('id[' . $option_id . '_chk' . $value_id . ']', 'on', $checked) ?>
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
            $options_id_name = 'id[txt_' . $option_id . ']';
?>
                        <div class="form-group">
                            <label class="control-label col-sm-2" for="<?= $for ?>"><?= $option_info['name'] ?></label>
                            <div class="col-sm-10">
<?php
            if ($rows > 1) {
                echo zen_draw_textarea_field($options_id_name, 'hard', $size, $rows, $text, 'id="' . $for . '" class="form-control"');
            } else {
                echo zen_draw_input_field($options_id_name, $text, 'id="' . $for . '" class="form-control" size="' . $size . '" maxlength="' . $length . '"');
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
                                <?= zen_draw_hidden_field('id[file_' . $option_id . ']', $option_value ?? '') ?>
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
