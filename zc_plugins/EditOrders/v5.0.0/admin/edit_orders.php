<?php
// -----
// Part of the Edit Orders encapsulated plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003, 2024 The zen-cart developers
//
// Last modified v5.0.0
//
use Zencart\Plugins\Admin\EditOrders\EditOrders;
use Zencart\Plugins\Admin\EditOrders\EditOrdersQueryCache;
use Zencart\Plugins\Admin\EditOrders\EoOrderChanges;

require 'includes/application_top.php';

// -----
// If the to-be-edited order's ID isn't supplied, quietly redirect back to the
// admin's orders-listing, as this condition "shouldn't happen".
//
$oID = (int)($_GET['oID'] ?? '0');
if ($oID === 0) {
    zen_redirect(zen_href_link(FILENAME_ORDERS));
}

// Start the currencies code
if (!class_exists('currencies')) {
    require DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php';
}
$currencies = new currencies();

$step = (isset($_POST['step'])) ? (int)$_POST['step'] : 0;
if (isset($_POST['add_product_categories_id'])) {
    $add_product_categories_id = zen_db_prepare_input($_POST['add_product_categories_id']);
}
if (isset($_POST['add_product_products_id'])) {
    $add_product_products_id = zen_db_prepare_input($_POST['add_product_products_id']);
}
if (isset($_POST['add_product_quantity'])) {
    $add_product_quantity = zen_db_prepare_input($_POST['add_product_quantity']);
}
  
// -----
// The "queryCache" functionality present in the Zen Cart core can get in the way of
// Edit Orders due to the amount of database manipulation.  Remove the default instance
// of the class (used by the database-class) and replace it with a stubbed-out version
// for the EO processing.
//
unset($queryCache);
$queryCache = new EditOrdersQueryCache();

// -----
// Include and instantiate the EditOrders class, which now also manipulates and
// instantiates the 'base' order-class.
//
zen_define_default('EO_DEBUG_TAXES_ONLY', 'false');  //-Either 'true' or 'false'

require DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
$eo = new EditOrders($oID);

// -----
// Check, first, to see that the submitted order was found. If not, silently redirect
// back to the orders' listing.
//
if ($eo->isOrderFound() === false) {
    zen_redirect(zen_href_link(FILENAME_ORDERS));
}

// -----
// Now, make sure that the 'environment' in which EO is running supports
// its order-update calculations. If any critical issues are found, the
// method will redirect back to the base orders' processing.
//
$eo->checkEnvironment();

// -----
// Make the modifications needed to coerce the recorded/queried order into
// its storefront format. The method returns an indication as to whether/not
// it could successfully 'divine' that information.
//
// When the method returns false, its processing has set any messages to be displayed
// to the admin on the subsequent redirect back to the order's listing.
//
if ($eo->queryOrder() === false) {
    zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params()));
}

// -----
// Check to see if any warning messages were detected by the EO class. [FIXME] what to do if present!
//
$eo_messages_exist = $eo->checkEnvironment();

// -----
// Gather the two arrays for the order's status display.
//
['orders_statuses' => $orders_statuses, 'orders_status_array' => $orders_status_array] = zen_getOrdersStatuses();

// -----
// Start action-related processing.
//
$action = $_GET['action'] ?? 'edit';
$eo->eoLog("\n" . date('Y-m-d H:i:s') . ", Edit Orders entered action ($action)\nEnabled Order Totals: " . MODULE_ORDER_TOTAL_INSTALLED);
$zco_notifier->notify('EDIT_ORDERS_START_ACTION_PROCESSING');
switch ($action) {
    // Update Order
    case 'update_order':
        require DIR_WS_MODULES . 'edit_orders/eo_update_order_action_processing.php';
        zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit', 'NONSSL'));
        break;

    default:
        $action = 'edit';
        break; 
}

// -----
// Create an instance of the to-be-edited order and record its contents
// in the session-based class through which the various AJAX methods
// communicate the admin's changes.
//
$order = $eo->getOrder();
$_SESSION['eoChanges'] = new EoOrderChanges($order);

// -----
// If a country referenced in the order's addresses is no longer present (or enabled)
// in the site, the order can't be edited since many of the tax- and shipping-related
// processing will result in invalid values for the order.
//
if (empty($order->customer['country_id']) || empty($order->billing['country_id']) || (!$eo->eoOrderIsVirtual($order) && empty($order->delivery['country_id']))) {
    $messageStack->add_session(sprintf(ERROR_ADDRESS_COUNTRY_NOT_FOUND, $oID), 'error');
    zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params()));
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
<!-- header //-->
<div class="header-area">
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
</div>
<!-- header_eof //-->
<?php
// -----
// A store can override EO's application of the 'type="number"' parameters by adding the definition
//
// define('EDIT_ORDERS_USE_NUMERIC_FIELDS', '0');
//
// to a site-specific /admin/extra_datafiles module.
//
// Note that EO's rendering of input fields is (currently) a mixture of directly-coded <input /> tags
// and inputs generated via zen_draw_input_field.  The variables set below that start with $input_ are
// used on the function-call field-generation and the others are used when directly-coded.
//
zen_define_default('EDIT_ORDERS_USE_NUMERIC_FIELDS', '1');
if (EDIT_ORDERS_USE_NUMERIC_FIELDS !== '1') {
    $input_value_params = '';
    $input_tax_params = '';
    $value_params = '';
    $tax_params = '';
    $input_field_type = 'text';
} else {
    $input_value_params = ' min="0" step="any"';
    $input_tax_params = ' min="0" max="100" step="any"';
    $value_params = $input_value_params . ' type="number"';
    $tax_params = $input_tax_params . ' type="number"';
    $input_field_type = 'number';
}


// -----
// Since EO's order-updating is now AJAX-driven, pull the initial order-display
// from the previous eo_edit_action_display.php.
//
define('DIR_WS_EO_MODULES', DIR_WS_MODULES . 'edit_orders/');
?>
<div class="container-fluid">
    <?php require DIR_WS_EO_MODULES . 'eo_navigation.php'; ?>

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

    <?php require DIR_WS_EO_MODULES . 'eo_edit_action_addresses_display.php'; ?>

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
// $additional_contact_info[] = [
//     'label' => LABEL_TEXT,
//     'for' => 'input-field-id',   //- Optional, see below
//     'content' => $field_content,
// ];
//
// Note: If the 'for' element is not supplied, the 'label' and 'content' will be displayed
// as 'simple' form elements.
//
// For EO versions prior to 5.0.0, this notification was 'EDIT_ORDERS_ADDITIONAL_CONTACT_INFORMATION'.
//
$additional_contact_info = [];
$zco_notifier->notify('NOTIFY_EO_ADDL_CONTACT_INFO', $order, $additional_contact_info);

if (is_array($additional_contact_info) && count($additional_contact_info) !== 0) {
    foreach ($additional_contact_info as $contact_info) {
        if (!empty($contact_info['label']) && !empty($contact_info['content'])) {
            if (!isset($contact_info['for'])) {
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
                continue;
            }
?>
                        <div class="row my-2">
                            <div class="form-group">
                                <label for="<?= $contact_info['for'] ?>" class="col-sm-4 control-label">
                                    <?= $contact_info['label'] ?>
                                </label>
                                <div class="col-sm-8">
                                    <?= $contact_info['content'] ?>
                                </div>
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
    $_SESSION['eo_price_calculations'] = $default;
    $price_is_manual = ($default === 3);

    $display_payment_calc_label = true;
    $payment_calc_choice = zen_draw_pull_down_menu('payment_calc_method', $choices, $default, 'id="calc-method" class="form-control w-auto"');
} else {
    switch (EO_PRODUCT_PRICE_CALC_METHOD) {
        case 'AutoSpecials':
            $payment_calc_choice = PRODUCT_PRICES_CALC_AUTOSPECIALS;
            $price_is_manual = false;
            break;
        case 'Auto':
            $payment_calc_choice = PRODUCT_PRICES_CALC_AUTO;
            $price_is_manual = false;
            break;
        default:
            $payment_calc_choice = PRODUCT_PRICES_CALC_MANUAL;
            $price_is_manual = true;
            break;
    }
    $display_payment_calc_label = false;
    $payment_calc_choice = '<p class="text-center">' . $payment_calc_choice . '</p>';
}

// -----
// Note: For EO versions prior to 5.0.0, this notification was 'EDIT_ORDERS_FORM_ADDITIONAL_INPUTS'.
//
$additional_inputs = '';
$zco_notifier->notify('NOTIFY_EO_UPDATE_FORM_ADDL_INPUTS', $order, $additional_inputs);

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
<?php
if ($display_payment_calc_label === false) {
?>
                            <?= $payment_calc_choice ?>
<?php
} else {
?>
                            <label for="calc-method" class="col-sm-3 control-label"><?= PAYMENT_CALC_METHOD ?>&nbsp;</label>
                            <div class="col-sm-9">
                                <?= $payment_calc_choice ?>
                            </div>
<?php
}
?>
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
    $price_entry_disabled = ($price_is_manual === true) ? '' : 'disabled';
?>
                <td class="dataTableContent text-center">
                    <?= zen_draw_input_field($base_var_name . '[qty]', $next_product['qty'], 'class="mx-auto prod-qty form-control"' . $input_value_params, false, $input_field_type) ?>
<?php
    if (isset($next_product['attributes'])) {
?>
                    <button class="update-attributes btn btn-sm btn-warning mt-2"<?= $data_index ?> title="<?= TEXT_BUTTON_CHANGE_ATTRIBS_ALT ?>">
                        <?= ICON_EDIT ?>
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
                        <?= zen_draw_input_field(
                            $base_var_name . '[onetime_charges]',
                            $next_product['onetime_charges'],
                            'class="form-control form-control-sm amount-onetime d-inline" ' . $price_entry_disabled
                        ) ?>
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
                    <?= zen_draw_input_field(
                        $base_var_name . '[final_price]',
                        $final_price,
                        $value_params . ' class="form-control amount price-net" ' . $price_entry_disabled . $data_index) ?>
                </td>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $next_product['tax']);
        $final_price = $gross_price;
?>
                <td class="dataTableContent text-right">
                    <?= zen_draw_input_field(
                        $base_var_name . '[gross]',
                        $gross_price,
                        $value_params . ' class="form-control amount price-gross" ' . $price_entry_disabled . $data_index
                    ) ?>
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
            <?php require DIR_WS_EO_MODULES . 'eo_edit_action_ot_table_display.php'; ?>
<!-- End Order Total Block -->
        </table>
    </div>

<!-- Begin Status-History Block -->
    <?php require DIR_WS_EO_MODULES . 'eo_edit_action_osh_table_display.php'; ?>
<!-- End Status-History Block -->
</div>
<?php
// -----
// Include id-specific javascript only if the associated blocks have been rendered.
//
if (!empty($additional_totals_displayed)) {
?>
<script>
/*
    handleShipping();
    function handleShipping() {
        if (document.getElementById('update_total_code') != undefined && document.getElementById('update_total_code').value == 'ot_shipping') {
            document.getElementById('update_total_shipping').style.display = 'table-cell';
        } else {
            document.getElementById('update_total_shipping').style.display = 'none';
        }
    }
    document.getElementById('update_total_code').onchange = function(){handleShipping();};
*/
</script>
<!-- body_eof //-->
<?php
}
?>
<!-- footer //-->
<?php 
require DIR_WS_INCLUDES . 'footer.php'; 
?>
<!-- footer_eof //-->
</body>
</html>
<?php
unset($_SESSION['customer_id'], $_SESSION['customer_country_id'], $_SESSION['customer_zone_id'], $_SESSION['cart'], $_SESSION['shipping'], $_SESSION['payment']);
require DIR_WS_INCLUDES . 'application_bottom.php';
