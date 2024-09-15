<?php
// -----
// Part of the Edit Orders encapsulated plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
// Last modified v5.0.0
//
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
require DIR_WS_CLASSES . 'EditOrdersQueryCache.php';
$queryCache = new EditOrdersQueryCache();

// -----
// Include and instantiate the EditOrders class, which now also manipulates and
// instantiates the 'base' order-class.
//
zen_define_default('EO_DEBUG_TAXES_ONLY', 'false');  //-Either 'true' or 'false'

require DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
require DIR_WS_CLASSES . 'EditOrders.php';
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
// Check to see if any warning messages were detected by the EO class. TBD what to do if present!
//
$eo_messages_exist = $eo->checkEnvironment();

$orders_statuses = [];
$orders_status_array = [];
$orders_status_query = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . (int)$_SESSION['languages_id'] . "
  ORDER BY sort_order ASC"
);
foreach ($orders_status_query as $orders_status) {
    $status_id = $orders_status['orders_status_id'];
    $status_name = $orders_status['orders_status_name'];
    $orders_statuses[] = [
        'id' => $status_id,
        'text' => "$status_name [$status_id]"
    ];
    $orders_status_array[$status_id] = $status_name;
}
unset($orders_status_query);

$action = $_GET['action'] ?? 'edit';
$eo->eoLog("\n" . date('Y-m-d H:i:s') . ", Edit Orders entered action ($action)\nEnabled Order Totals: " . MODULE_ORDER_TOTAL_INSTALLED);
$zco_notifier->notify('EDIT_ORDERS_START_ACTION_PROCESSING');
switch ($action) {
    // Update Order
    case 'update_order':
        require DIR_WS_MODULES . 'edit_orders/eo_update_order_action_processing.php';
        zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit', 'NONSSL'));
        break;

    case 'add_prdct':
        require DIR_WS_MODULES . 'edit_orders/eo_add_prdct_action_processing.php';
        if ($redirect_required) {
            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit'));
        }
        break;

    default:
        $action = 'edit';
        break; 
}

$order = $eo->getOrder();

if (!$eo->eoOrderIsVirtual($order) && (!isset($order->customer['country']['id']) || !isset($order->billing['country']['id']) || !isset($order->delivery['country']['id']))) {
    $messageStack->add(WARNING_ADDRESS_COUNTRY_NOT_FOUND, 'warning');
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
// Start action-based rendering ...
//
if ($action === 'edit') {
    require DIR_WS_MODULES . 'edit_orders/eo_edit_action_display.php';
} elseif ($action === 'add_prdct') { 
    require DIR_WS_MODULES . 'edit_orders/eo_add_prdct_action_display.php';
}

// -----
// Include id-specific javascript only if the associated blocks have been rendered.
//
if (!empty($additional_totals_displayed)) {
?>
<!-- body_text_eof //-->
<script>
    handleShipping();
    function handleShipping() {
        if (document.getElementById('update_total_code') != undefined && document.getElementById('update_total_code').value == 'ot_shipping') {
            document.getElementById('update_total_shipping').style.display = 'table-cell';
        } else {
            document.getElementById('update_total_shipping').style.display = 'none';
        }
    }
    document.getElementById('update_total_code').onchange = function(){handleShipping();};
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
