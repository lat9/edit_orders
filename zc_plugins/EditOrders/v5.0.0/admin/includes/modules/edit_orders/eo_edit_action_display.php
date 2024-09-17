<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
// Last modified v5.0.0
//

// -----
// Pull in the navigation element a the top-of-screen, which should be in the same directory as this file!
//
?>
<div class="container-fluid">
    <?php require 'eo_navigation.php'; ?>

    <div class="row">
        <div class="col-sm-6">
            <h1><?= HEADING_TITLE . ' #' . $oID ?></h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="<?= zen_href_link(FILENAME_ORDERS, zen_get_all_get_params()) ?>" class="btn btn-default" role="button">
                <?= IMAGE_BACK ?>
            </a>
            <a href="<?= zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(['action']) . '&action=edit') ?>" class="btn btn-primary" role="button">
                <?= DETAILS ?>
            </a>
        </div>
    </div>

    <?php require 'eo_edit_action_addresses_display.php'; ?>

    <div class="row">
        <div id="eo-addl-info" class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="h3"><?= TEXT_PANEL_HEADER_ADDL_INFO ?></span>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal">
<?php
// -----
// Give a watching observer the opportunity to supply additional contact-information for the order.
//
// The $additional_contact_info (supplied as the notification's 2nd parameter), if supplied, is a
// numerically-indexed array of arrays containing each label and associated content, e.g.:
//
// $additional_contact_info[] = ['label' => LABEL_TEXT, 'content' => $field_content];
//
$additional_contact_info = [];
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_CONTACT_INFORMATION', $order, $additional_contact_info);

if (is_array($additional_contact_info) && count($additional_contact_info) !== 0) {
    foreach ($additional_contact_info as $contact_info) {
        if (!empty($contact_info['label']) && !empty($contact_info['content'])) {
?>
                        <div class="row my-2">
                            <div class="col-sm-4 control-label">
                                <?= $contact_info['label'] ?>
                            </div>
                            <div class="col-sm-8">
                                <?= $contact_info['content'] ?>
                            </div>
                        </div>
<?php
        }
    }
}

// -----
// Note: Using loose comparison since the value is recorded (currently) as decimal(14,6)
// and shows up in the order as (string)1.000000 if the order's placed in the store's
// default currency.
//
if ($order->info['currency_value'] != 1) {
?>
                        <div class="row my-2">
                            <div class="col-sm-4 eo-label">
                                <?= sprintf(ENTRY_CURRENCY_VALUE, $order->info['currency']) ?>
                            </div>
                            <div class="col-sm-8">
                                <?= $order->info['currency_value'] ?>
                            </div>
                        </div>
<?php
}

$max_payment_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'payment_method') . '"';
?>
                        <div class="row my-2">
                            <div class="form-group">
                                <label for="payment-method" class="col-sm-4 control-label">
                                    <?= ENTRY_PAYMENT_METHOD ?>
                                </label>
                                <div class="col-sm-8">
                                    <?= zen_draw_input_field(
                                        'update_info_payment_method',
                                        zen_output_string_protected($order->info['payment_method']),
                                        $max_payment_length . ' id="payment-method" class="form-control"'
                                    ) ?>
                                    <?= ($order->info['payment_method'] !== TEXT_CREDIT_CARD) ? ENTRY_UPDATE_TO_CC : ENTRY_UPDATE_TO_CK ?>
                                </div>
                            </div>
                        </div>
<?php 
$max_type_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_type') . '"';
$max_owner_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_owner') . '"';
$max_number_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_number') . '"';
$max_expires_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_expires') . '"';

$cc_fields_display = 'd-none';
if (!empty($order->info['cc_type']) || !empty($order->info['cc_owner']) || $order->info['payment_method'] === TEXT_CREDIT_CARD || !empty($order->info['cc_number'])) {
    $cc_fields_display = '';
}
?>
                        <div class="row cc-field my-2 <?= $cc_fields_display ?>">
                            <div class="form-group">
                                <label for="cc-type" class="col-sm-4 control-label">
                                    <?= ENTRY_CREDIT_CARD_TYPE ?>
                                </label>
                                <div class="col-sm-8">
                                    <?= zen_draw_input_field(
                                        'update_info_cc_type',
                                        zen_output_string_protected((string)$order->info['cc_type']),
                                        $max_type_length . ' id="cc-type" class="form-control"'
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        <div class="row cc-field my-2 <?= $cc_fields_display ?>">
                            <div class="form-group">
                                <label for="cc-owner" class="col-sm-4 control-label">
                                    <?= ENTRY_CREDIT_CARD_OWNER ?>
                                </label>
                                <div class="col-sm-8">
                                    <?= zen_draw_input_field(
                                        'update_info_cc_owner',
                                        zen_output_string_protected((string)$order->info['cc_owner']),
                                        $max_owner_length . ' id="cc-owner" class="form-control"'
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        <div class="row cc-field my-2 <?= $cc_fields_display ?>">
                            <div class="form-group">
                                <label for="cc-number" class="col-sm-4 control-label">
                                    <?= ENTRY_CREDIT_CARD_NUMBER ?>
                                </label>
                                <div class="col-sm-8">
                                    <?= zen_draw_input_field(
                                        'update_info_cc_number',
                                        zen_output_string_protected((string)$order->info['cc_number']),
                                        $max_number_length . ' id="cc-number" class="form-control"'
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        <div class="row cc-field my-2 <?= $cc_fields_display ?>">
                            <div class="form-group">
                                <label for="cc-expires" class="col-sm-4 control-label">
                                    <?= ENTRY_CREDIT_CARD_EXPIRES ?>
                                </label>
                                <div class="col-sm-8">
                                    <?= zen_draw_input_field(
                                        'update_info_cc_expires',
                                        zen_output_string_protected((string)$order->info['cc_expires']),
                                        $max_expires_length . ' id="cc-expires" class="form-control"'
                                    ) ?>
                                </div>
                            </div>
                        </div>
<?php
// -----
// NOTE: No maximum lengths provided for these non-standard fields, since there's no way to know what database table
// the information is stored in!
//
if (isset($order->info['account_name']) || isset($order->info['account_number']) || isset($order->info['po_number'])) {
?>
                        <hr>
<?php
    if (isset($order->info['account_name'])) {
?>
                        <div class="row my-2">
                            <div class="col-sm-4 eo-label">
                                <?= ENTRY_ACCOUNT_NAME ?>
                            </div>
                            <div class="col-sm-8">
                                <?= zen_output_string_protected($order->info['account_name']) ?>
                            </div>
                        </div>
<?php
    }
    if (isset($order->info['account_number'])) {
?>
                        <div class="row my-4">
                            <div class="col-sm-3 eo-label">
                                <?= ENTRY_ACCOUNT_NUMBER ?>
                            </div>
                            <div class="col-sm-8">
                                <?= zen_output_string_protected($order->info['account_number']) ?>
                            </div>
                        </div>
<?php
    }
    if (isset($order->info['po_number'])) {
?>
                        <div class="row my-2">
                            <div class="col-sm-4 eo-label">
                                <?= ENTRY_PURCHASE_ORDER_NUMBER ?>
                            </div>
                            <div class="col-sm-8">
                                <?= zen_output_string_protected($order->info['po_number']) ?>
                            </div>
                        </div>
<?php
    }
}
?>
                    </form>
                </div>
            </div>
        </div>
<?php
$payment_calc_choice = '';
$price_is_hidden = '';
$priceMessage = '';
if (EO_PRODUCT_PRICE_CALC_METHOD === 'Choose') {
    $choices = [
        ['id' => 1, 'text' => PAYMENT_CALC_AUTOSPECIALS],
        ['id' => 2, 'text' => PAYMENT_CALC_AUTO],
        ['id' => 3, 'text' => PAYMENT_CALC_MANUAL]
    ];
    switch (EO_PRODUCT_PRICE_CALC_DEFAULT) {
        case 'AutoSpecials':
            $default = 1;
            break;
        case 'Auto':
            $default = 2;
            break;
        default:
            $default = 3;
            break;
    }
    if (isset($_SESSION['eo_price_calculations']) && $_SESSION['eo_price_calculations'] >= 1 && $_SESSION['eo_price_calculations'] <= 3) {
        $default = $_SESSION['eo_price_calculations'];
    }
    $payment_calc_choice = zen_draw_pull_down_menu('payment_calc_method', $choices, $default, 'id="calc-method" class="form-control w-auto"');
} else {
    switch (EO_PRODUCT_PRICE_CALC_METHOD) {
        case 'AutoSpecials':
            $payment_calc_choice = PRODUCT_PRICES_CALC_AUTOSPECIALS;
            $price_is_hidden = ' d-none';
            $priceMessage = EO_PRICE_AUTO_GRID_MESSAGE;
            break;
        case 'Auto':
            $payment_calc_choice = PRODUCT_PRICES_CALC_AUTO;
            $price_is_hidden = ' d-none';
            $priceMessage = EO_PRICE_AUTO_GRID_MESSAGE;
            break;
        default:
            $payment_calc_choice = PRODUCT_PRICES_CALC_MANUAL;
            break;
    }
    $payment_calc_choice = zen_draw_input_field('payment_calc_method', $payment_calc_choice, 'id="calc-method" class="form-control w-auto" disabled');
}

$additional_inputs = '';
$zco_notifier->notify('EDIT_ORDERS_FORM_ADDITIONAL_INPUTS', $order, $additional_inputs);

$reset_totals_block =
    '<div class="checkbox">' .
        '<label>' .
            zen_draw_checkbox_field('reset_totals', '', EO_TOTAL_RESET_DEFAULT === 'on') .
            '&nbsp;' . RESET_TOTALS .
        '</label>' .
    '</div>';
?>
        <div id="eo-update-info" class="col-md-8">
            <div class="panel panel-warning">
                <div class="panel-heading text-center">
                    <span class="h3"><?= TEXT_PANEL_HEADER_UPDATE_INFO ?></span>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label for="calc-method" class="col-sm-3 control-label"><?= PAYMENT_CALC_METHOD ?>&nbsp;</label>
                            <div class="col-sm-9">
                                <?= $payment_calc_choice ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="update-form" class="panel-footer text-center d-none">
                    <div>
                        <?= zen_draw_form('edit_order', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'paycc']) . 'action=update_order', 'post', 'class="form-inline"') ?>
                            <button id="update-submit" type="button" class="btn btn-danger"><?= IMAGE_UPDATE ?></button>
                            <?= "&nbsp;$reset_totals_block&nbsp;$additional_inputs" ?>
                        <?= '</form>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Begin Products Listing Block -->
    <div class="row">
        <table id="products-listing" class="table table-striped mb-2">
            <tr class="dataTableHeadingRow">
<?php
// -----
// To add more columns at the beginning of the order's products' table, a
// watching observer can provide an associative array in the form:
//
// $extra_headings = [
//     [
//       'align' => $alignment,    // One of 'center', 'right' or 'left' (optional)
//       'text' => $value
//     ],
// ];
//
// Observer note:  Be sure to check that the $p2/$extra_headings value is specifically (bool)false before initializing, since
// multiple observers might be injecting content!
//
$extra_headings = false;
$zco_notifier->notify('EDIT_ORDERS_PRODUCTS_HEADING_1', [], $extra_headings);

$base_orders_columns = 7;
if (is_array($extra_headings)) {
    foreach ($extra_headings as $heading_info) {
        $base_orders_columns++;
        $align = '';
        if (isset($heading_info['align'])) {
            switch ($heading_info['align']) {
                case 'center':
                    $align = ' text-center';
                    break;
                case 'right':
                    $align = ' text-right';
                    break;
                default:
                    $align = '';
                    break;
            }
        }
?>
                <th class="dataTableHeadingContent<?= $align ?>"><?= $heading_info['text'] ?></th>
<?php
    }
}
?>
                <th class="dataTableHeadingContent text-center" colspan="3"><?= TABLE_HEADING_PRODUCTS ?></th>
                <th class="dataTableHeadingContent"><?= TABLE_HEADING_PRODUCTS_MODEL ?></th>
                <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_TAX ?></th>
<?php
// -----
// Starting with v4.4.0, show both the net and gross unit prices when the store is configured to display prices with tax.
//
if (DISPLAY_PRICE_WITH_TAX === 'true') {
    $base_orders_columns++;
?>
                <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_UNIT_PRICE_NET ?></th>
                <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_UNIT_PRICE_GROSS ?></th>
<?php
} else {
?>
                <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_UNIT_PRICE ?></th>
<?php
}
?>
                <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_TOTAL_PRICE ?></th>
            </tr>
<?php
// -----
// Initialize (outside of the loop, for performance) the attributes for the various product-related
// input fields.
//
$name_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '"';
$model_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '"';
foreach ($order->products as $next_product) {
?>
            <tr class="dataTableRow">
<?php
    // -----
    // To add more columns at the beginning of the order's products' table, a
    // watching observer can provide an associative array in the form:
    //
    // $extra_data = [
    //     [
    //       'align' => $alignment,    // One of 'center', 'right' or 'left' (optional)
    //       'text' => $value
    //     ],
    // ];
    //
    // Observer note:  Be sure to check that the $p2/$extra_data value is specifically (bool)false before initializing, since
    // multiple observers might be injecting content!
    //
    $extra_data = false;
    $zco_notifier->notify('EDIT_ORDERS_PRODUCTS_DATA_1', $next_product, $extra_data);
    if (is_array($extra_data)) {
        foreach ($extra_data as $data) {
            $align = '';
            if (isset($data['align'])) {
                switch ($data['align']) {
                    case 'center':
                        $align = ' text-center';
                        break;
                    case 'right':
                        $align = ' text-right';
                        break;
                    default:
                        $align = '';
                        break;
                }
            }
?>
                <td class="dataTableContent<?= $align ?>"><?= $data['text'] ?></td>
<?php
        }
    }

    $orders_products_id = $next_product['orders_products_id'];
    $base_var_name = 'update_products[' . $orders_products_id . ']';
    $data_index = ' data-opi="' . $orders_products_id . '"';
?>
                <td class="dataTableContent text-center">
                    <?= zen_draw_input_field($base_var_name . '[qty]', $next_product['qty'], 'class="mx-auto prod-qty form-control"' . $input_value_params, false, $input_field_type) ?>
<?php
    if (isset($next_product['attributes'])) {
?>
                    <button class="update-attributes btn btn-sm btn-warning mt-2"<?= $data_index ?> title="<?= TEXT_BUTTON_CHANGE_ATTRIBS_ALT ?>">
                        <?= TEXT_BUTTON_CHANGE ?>
                    </button>
<?php
    }
?>
                </td>

                <td>&nbsp;X&nbsp;</td>

                <td class="dataTableContent">
                    <?= zen_draw_input_field($base_var_name . '[name]', $next_product['name'], $name_params . ' class="form-control"') ?>
<?php
    if (isset($next_product['attributes'])) {
?>
                    <div class="row">
                        <small>&nbsp;<i><?= TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?></i></small>
                        <?= zen_draw_input_field($base_var_name . '[onetime_charges]', $next_product['onetime_charges'], 'class="form-control form-control-sm amount-onetime d-inline"') ?>
                    </div>

                    <ul class="attribs-list">
<?php
        foreach ($next_product['attributes'] as $next_attribute) {
?>
                        <li><?= $next_attribute['option'] . ': ' . nl2br(zen_output_string_protected($next_attribute['value'])) ?></li>
<?php
        }
?>
                    </ul>
<?php
    }
?>
                </td>

                <td class="dataTableContent">
                    <?= zen_draw_input_field($base_var_name . '[model]', $next_product['model'], $model_params . ' class="model form-control"') ?>
                </td>
<?php
    // -----
    // Starting with EO v4.4.0, both the net and gross prices are displayed when the store displays prices with tax.
    //
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $final_price = $next_product['final_price'];
        $onetime_charges = $next_product['onetime_charges'];
    } else {
        $final_price = $next_product['final_price'];
        $onetime_charges = $eo->eoRoundCurrencyValue($next_product['onetime_charges']);
    }
?>
                <td class="dataTableContent text-right">
                    <div class="tax-percentage">&nbsp;%</div>
                    <?= zen_draw_input_field(
                        $base_var_name . '[tax]',
                        zen_display_tax_value($next_product['tax']),
                        'class="amount form-control d-inline-block price-tax"' . $input_tax_params . $data_index,
                        false,
                        $input_field_type
                    ) ?>
                </td>

                <td class="dataTableContent text-right">
                    <?= zen_draw_input_field($base_var_name . '[final_price]', $final_price, $value_params . ' class="form-control amount price-net' . $price_is_hidden . '"' . $data_index) ?>
                    <?= $priceMessage ?>
                </td>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $next_product['tax']);
        $final_price = $gross_price;
?>
                <td class="dataTableContent text-right">
                    <?= zen_draw_input_field($base_var_name . '[gross]', $gross_price, $value_params . ' class="amount form-control"' . $data_index) ?>
                </td>
<?php
    }
?>
                <td class="dataTableContent text-right">
                    <?= $currencies->format($final_price * $next_product['qty'] + $onetime_charges, true, $order->info['currency'], $order->info['currency_value']) ?>
                </td>
            </tr>
<?php
}
?>
<!-- End Products Listings Block -->

<!-- Begin Order Total Block -->
            <?php require 'eo_edit_action_ot_table_display.php'; ?>
<!-- End Order Total Block -->
        </table>
    </div>

<!-- Begin Status-History Block -->
    <?php require 'eo_edit_action_osh_table_display.php'; ?>
<!-- End Status-History Block -->
</div>