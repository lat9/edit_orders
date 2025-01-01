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
use Zencart\Plugins\Admin\EditOrders\EoCart;
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

// -----
// The "queryCache" functionality present in the Zen Cart core can get in the way of
// Edit Orders due to the amount of database manipulation.  Remove the default instance
// of the class (used by the database-class) and replace it with a stubbed-out version
// for the EO processing.
//
$queryCache = new EditOrdersQueryCache();

// -----
// Create an initial copy of the requested order's information.
//
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
$order = new order($oID);

// -----
// Check, first, to see that the submitted order was found. If not, silently redirect
// back to the orders' listing.
//
if (empty($order->info)) {
    zen_redirect(zen_href_link(FILENAME_ORDERS));
}

// -----
// Now, make sure that the 'environment' in which EO is running supports
// its order-update calculations. If any critical issues are found, the
// method will redirect back to the base orders' processing.
//
$eo = new EditOrders($oID);

$action = $_GET['action'] ?? 'edit';
$eo->eoLog("Edit Orders entered action ($action). Enabled Order Totals: " . MODULE_ORDER_TOTAL_INSTALLED, 'with-date');

$eo->checkEnvironment();

// -----
// Make the modifications needed to coerce the recorded/queried order into
// its storefront 'cart' format. The method returns an indication as to whether/not
// it could successfully 'divine' that information.
//
// When the method returns false, its processing has set any messages to be displayed
// to the admin on the subsequent redirect back to the order's listing.
//
if ($eo->queryOrder($order) === false) {
    zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params()));
}

// -----
// Gather the two arrays for the order's status display.
//
['orders_statuses' => $orders_statuses, 'orders_status_array' => $orders_status_array] = zen_getOrdersStatuses();

// -----
// Set the order's currency into the session; any current value will be restored once EO's processing
// has completed.
//
$_SESSION['eo_saved_currency'] = $_SESSION['currency'] ?? false;
$_SESSION['currency'] = $order->info['currency'];

// -----
// Start action-related processing.
//
$zco_notifier->notify('EDIT_ORDERS_START_ACTION_PROCESSING');
switch ($action) {
    // Update Order
    case 'update_order':
        if (!isset($_SESSION['eoChanges'])) {
            $messageStack->add_session(WARNING_NO_UPDATES_TO_ORDER, 'warning');
            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit'));
        }

        $changed_values = $_SESSION['eoChanges']->getChangedValues();
        if (count($changed_values) === 0) {
            $messageStack->add_session(WARNING_NO_UPDATES_TO_ORDER, 'warning');
            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit'));
        }

        $original_order = $_SESSION['eoChanges']->getOriginalOrder();
        if ($oID !== (int)$original_order->info['order_id']) {
            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit'));
        }

        $updated_order = $_SESSION['eoChanges']->getUpdatedOrder();
        $order_table_updates = [];
        if (!empty($updated_order->info['changes'])) {
            $order_table_updates = $eo->getOrderInfoUpdateSql($original_order->info, $updated_order->info);
        }
        if (!empty($updated_order->customer['changes'])) {
            $order_table_updates = array_merge($order_table_updates, $eo->getAddressUpdateSql('customer_', $original_order->customer, $updated_order->customer));
        }
        if (!empty($updated_order->delivery['changes'])) {
            $order_table_updates = array_merge($eo->getAddressUpdateSql('delivery_', $original_order->delivery, $updated_order->delivery));
        }
        if (!empty($updated_order->billing['changes'])) {
            $order_table_updates = array_merge($eo->getAddressUpdateSql('billing_', $original_order->billing, $updated_order->billing));
        }
        if (count($order_table_updates) !== 0) {
            $order_table_updates[] = ['fieldName' => 'last_modified', 'value' => 'now()', 'type' => 'passthru',];
            $db->perform(
                TABLE_ORDERS,
                $order_table_updates,
                'update',
                'orders_id = ' . (int)$oID . ' LIMIT 1'
            );
        }

        $products_updates = '';
        if (!empty($changed_values['products'])) {
            $products_updates = $eo->updateOrderedProductsInDb($oID, $changed_values['products']);
        }

        $ot_updates = '';
        $totals_changes = $_SESSION['eoChanges']->getTotalsChanges();
        if (!empty($totals_changes)) {
            $ot_updates = $eo->updateOrderTotalsInDb($oID, $changed_values['order_totals'], $totals_changes);
        }

        if (!empty($updated_order->statuses['changes'])) {
            // -----
            // Copy the added comment's variables into $_POST for use during
            // the status-history record's creation.
            //
            $osh_info = $updated_order->statuses['changes'];
            foreach ($osh_info as $key => $value) {
                $_POST[$key] = $value;
            }
            zen_update_orders_history((int)$oID, $osh_info['message'], null, $osh_info['status'], $osh_info['notify'], $osh_info['notify_comments']);
        }

        $order_changed_message = '';
        foreach ($changed_values as $title => $changes) {
            if ($title === 'osh_info') {
                continue;
            }
            if ($title === 'order_totals') {
                $order_changed_message .= $ot_updates;
                continue;
            }
            if ($title === 'products') {
                $order_changed_message .= $products_updates;
                continue;
            }

            $order_changed_message .= '<li>' . $title . '</li>';
            $order_changed_message .= '<ol type="a">';
            foreach ($changes as $next_change) {
                $original_value = '"' . $next_change['original'] . '"';
                $updated_value = '"' . $next_change['updated'] . '"';
                $label = rtrim($next_change['label'], ':');
                $order_changed_message .= '<li>' . sprintf(TEXT_VALUE_CHANGED, $label, $original_value, $updated_value) . '</li>';
            }
            $order_changed_message .= '</ol>';
        }
        if ($order_changed_message !== '') {
            $order_changed_message = TEXT_OSH_CHANGED_VALUES . "\n<ol>" . $order_changed_message . '</ol>';
            zen_update_orders_history((int)$oID, $order_changed_message);
        }

        // -----
        // Note: Currently replicated in /includes/init_includes/init_eo_config.php, covering the
        // case where the admin has navigated away from editing an order.
        //
        unset(
            $_SESSION['cart'],
            $_SESSION['cart_errors'],
            $_SESSION['cc_id'],
            $_SESSION['cot_gv'],
            $_SESSION['customer_country_id'],
            $_SESSION['customer_id'],
            $_SESSION['customer_zone_id'],
            $_SESSION['customers_ip_address'],
            $_SESSION['eoChanges'],
            $_SESSION['eo-totals'],
            $_SESSION['payment'],
            $_SESSION['shipping'],
            $_SESSION['shipping_tax_description'],
            $_SESSION['valid_to_checkout'],
 
        );

        $messageStack->add_session(sprintf(SUCCESS_ORDER_UPDATED, (int)$oID), 'success');
        zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit'));
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
$_SESSION['eoChanges']->saveOrdersStatuses($orders_status_array);

// -----
// If a country referenced in the order's addresses is no longer present (or enabled)
// in the site, the order can't be edited since many of the tax- and shipping-related
// processing will result in invalid values for the order.
//
if (empty($order->customer['country_id']) || empty($order->billing['country_id']) || ($order->content_type !== 'virtual' && empty($order->delivery['country_id']))) {
    $messageStack->add_session(sprintf(ERROR_ADDRESS_COUNTRY_NOT_FOUND, $oID), 'error');
    zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params()));
}

// -----
// Initialize session-based customer variables expected by the storefront
// order-creation processing.
//
$_SESSION['customer_id'] = $order->info['customer_id'];
$_SESSION['customers_ip_address'] = '.';

// -----
// Initialize the session-based values for shipping and payment used in the
// storefront order's processing.  Ditto for any coupon/gift-certificate
// applied to the order.
//
if (!empty($order->info['coupon_code'])) {
    $coupon_check = $db->Execute(
        "SELECT coupon_id
           FROM " . TABLE_COUPONS . "
          WHERE coupon_code = '" . zen_db_prepare_input($order->info['coupon_code']) . "'
          LIMIT 1"
    );
    if ($coupon_check->EOF) {
        $messageStack->add(sprintf(WARNING_ORDER_COUPON_BAD, $order->info['coupon_code']), 'warning');
    } else {
        $_SESSION['cc_id'] = $coupon_check->fields['coupon_id'];
    }
}

$_SESSION['payment'] = $order->info['payment_module_code'];

foreach ($order->totals as $next_total) {
    switch ($next_total['class']) {
        case 'ot_shipping':
            $_SESSION['shipping'] = [
                'id' => $order->info['shipping_module_code'] . '_',
                'title' => $order->info['shipping_method'],
                'cost' => $next_total['value'],
            ];
            break;

        case 'ot_gv':
            $_SESSION['cot_gv'] = (string)$next_total['value'];
            break;

        default:
            if (!empty($GLOBALS[$next_total['class']]->eoCanBeAdded)) {
                $_SESSION['eo-totals'][$next_total['class']] = ['title' => $next_total['title'], 'value' => $next_total['value']];
            }
            break;
    }
}

// -----
// Instantiate EO's cart-override into the session for use by storefront
// shipping- and order-total-module handling.
//
$_SESSION['cart'] = new EoCart();
$_SESSION['cart']->loadFromOrder($order);
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
// Since EO's order-updating is now AJAX-driven, pull the initial order-display
// from the previous eo_edit_action_display.php.
//
?>
<div id="eo-main" class="container-fluid">
    <?php require DIR_WS_MODULES . 'eo_navigation.php'; ?>

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

    <?php require DIR_WS_MODULES . 'eo_edit_action_addresses_display.php'; ?>

    <div class="row">
        <div id="eo-addl-info" class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="h3"><?= TEXT_PANEL_HEADER_ADDL_INFO ?></span>
                </div>
                <div class="panel-body">
                    <form id="eo-addl-info" class="form-horizontal">
<?php
// -----
// Give a watching observer the opportunity to supply additional contact-information for the order.
//
// The $additional_contact_info (supplied as the notification's 2nd parameter), if supplied, is a
// numerically-indexed array of arrays containing each label and associated content, e.g.:
//
// $additional_contact_info[] = [
//     'label' => LABEL_TEXT,
//     'for' => 'input-field-id',
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
                                        'payment_method',
                                        zen_output_string_protected($order->info['payment_method']),
                                        $max_payment_length . ' id="payment-method" class="eo-entry form-control"'
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
                                        'cc_type',
                                        zen_output_string_protected((string)$order->info['cc_type']),
                                        $max_type_length . ' class="eo-entry form-control"'
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
                                        'cc_owner',
                                        zen_output_string_protected((string)$order->info['cc_owner']),
                                        $max_owner_length . ' class="eo-entry form-control"'
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
                                        'cc_number',
                                        zen_output_string_protected((string)$order->info['cc_number']),
                                        $max_number_length . ' class="eo-entry form-control"'
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
                                        'cc_expires',
                                        zen_output_string_protected((string)$order->info['cc_expires']),
                                        $max_expires_length . ' class="eo-entry form-control"'
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
$display_payment_calc_label = false;
if (EO_PRODUCT_PRICE_CALC_METHOD === 'Choose') {
    $choices = [
        ['id' => 'AutoSpecials', 'text' => PAYMENT_CALC_AUTOSPECIALS],
        ['id' => 'Manual', 'text' => PAYMENT_CALC_MANUAL]
    ];
    $default = EO_PRODUCT_PRICE_CALC_DEFAULT;
    if (isset($_SESSION['eo_price_calculations']) && in_array($_SESSION['eo_price_calculations'], ['AutoSpecials', 'Manual'], true)) {
        $default = $_SESSION['eo_price_calculations'];
    }
    $_SESSION['eo_price_calculations'] = $default;
    $price_is_manual = ($default === 'Manual');

    $display_payment_calc_label = true;
    $payment_calc_choice = zen_draw_pull_down_menu('payment_calc_method', $choices, $default, 'id="calc-method" class="form-control w-auto"');
} elseif (EO_PRODUCT_PRICE_CALC_METHOD === 'AutoSpecials') {
    $price_is_manual = false;
    $payment_calc_choice =
        '<p class="text-center">' . PRODUCT_PRICES_CALC_AUTOSPECIALS . '</p>' .
        zen_draw_hidden_field('payment_calc_method', 'AutoSpecials', 'id="calc-method"');
} else {
    $price_is_manual = true;
    $payment_calc_choice =
        '<p class="text-center">' . PRODUCT_PRICES_CALC_MANUAL . '</p>' .
        zen_draw_hidden_field('payment_calc_method', 'Manual', 'id="calc-method"');
}

// -----
// Note: For EO versions prior to 5.0.0, this notification was 'EDIT_ORDERS_FORM_ADDITIONAL_INPUTS'.
//
$additional_inputs = '';
$zco_notifier->notify('NOTIFY_EO_UPDATE_FORM_ADDL_INPUTS', $order, $additional_inputs);
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
                <div id="update-form-wrapper" class="panel-footer text-center d-none">
                    <div>
                        <?= zen_draw_form('edit_order', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'paycc']) . 'action=update_order', 'post', 'id="update-form" class="form-inline"') ?>
                            <button id="update-verify" type="button" class="btn btn-danger"><?= IMAGE_UPDATE ?></button>
                            <?= "&nbsp;$additional_inputs" ?>
                        <?= '</form>' ?>
                    </div>
                    <div id="update-modal" class="modal fade address-modal" role="dialog"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <?= zen_draw_hidden_field('ot_changes', '0', 'id="ot-changes" class="eo-changed"') ?>
        <?= zen_draw_hidden_field('product_changes', '0', 'id="product-changes" class="eo-changed"') ?>
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
$zco_notifier->notify('NOTIFY_EDIT_ORDERS_PRODUCTS_HEADING_1', [], $extra_headings);

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
                <th class="dataTableHeadingContent text-center" colspan="3"><?= TABLE_HEADING_PRODUCTS_MODEL ?></th>
                <th class="dataTableHeadingContent"><?= TABLE_HEADING_PRODUCTS ?></th>
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

            <?php require DIR_WS_MODULES . 'eo_prod_table_display.php'; ?>
            <?php require DIR_WS_MODULES . 'eo_edit_action_ot_table_display.php'; ?>
        </table>
    </div>

    <div id="ot-edit-modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <div id="prod-edit-modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <?php require DIR_WS_MODULES . 'eo_edit_action_osh_table_display.php'; ?>
</div>

<?php require DIR_WS_INCLUDES . 'footer.php'; ?>

</body>
</html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
