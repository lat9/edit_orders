<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
//-Last modified v4.7.0
//
require 'includes/application_top.php';

// -----
// If the to-be-edited order's ID isn't supplied, quietly redirect back to the
// admin's orders-listing, as this condition "shouldn't happen".
//
if (empty($_GET['oID'])) {
    zen_redirect(zen_href_link(FILENAME_ORDERS));
}
$oID = (int)$_GET['oID'];

// Check for commonly broken attribute related items
eo_checks_and_warnings();

// Start the currencies code
if (!class_exists('currencies')) {
    require DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php';
}
$currencies = new currencies();

// Use the normal order class instead of the admin one
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';

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
// Include and instantiate the editOrders class.
//
require DIR_WS_CLASSES . 'editOrders.php';
$eo = new editOrders($oID);

$orders_statuses = [];
$orders_status_array = [];
$order_by_field = ($sniffer->field_exists(TABLE_ORDERS_STATUS, 'sort_order')) ? 'sort_order' : 'orders_status_id';
$orders_status_query = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . (int)$_SESSION['languages_id'] . "
  ORDER BY $order_by_field ASC"
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

$action = $_GET['action'] ?? 'edit';
$eo->eoLog(PHP_EOL . date('Y-m-d H:i:s') . ", Edit Orders entered (" . EO_VERSION . ") action ($action)" . PHP_EOL . 'Enabled Order Totals: ' . MODULE_ORDER_TOTAL_INSTALLED, 1);
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
        $orders_query = $db->Execute(
            "SELECT orders_id FROM " . TABLE_ORDERS . " 
              WHERE orders_id = $oID
              LIMIT 1"
        );
        if ($orders_query->EOF) {
            $messageStack->add_session(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
            zen_redirect(zen_href_link(FILENAME_ORDERS));
        }
        break; 
}

if ($action === 'edit' || ($action === 'update_order' && empty($allow_update))) {
    $action = 'edit';

    $order = $eo->getOrderInfo($action);

    // -----
    // Initialize the shipping cost, tax-rate and tax-value.
    //
    $eo->eoInitializeShipping($oID, $action);

    if (!$eo->eoOrderIsVirtual($order) &&
           (!is_array($order->customer['country']) || !isset($order->customer['country']['id']) ||
            !is_array($order->billing['country']) || !isset($order->billing['country']['id']) ||
            !is_array($order->delivery['country']) || !isset($order->delivery['country']['id']))) {
        $messageStack->add(WARNING_ADDRESS_COUNTRY_NOT_FOUND, 'warning');
    }
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
    $input_value_parms = '';
    $input_tax_parms = '';
    $value_parms = '';
    $tax_parms = '';
    $input_field_type = 'text';
} else {
    $input_value_parms = ' min="0" step="any"';
    $input_tax_parms = ' min="0" max="100" step="any"';
    $value_parms = $input_value_parms . ' type="number"';
    $tax_parms = $input_tax_parms . ' type="number"';
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
    <!--
    handleShipping();
    function handleShipping() {
        if (document.getElementById('update_total_code') != undefined && document.getElementById('update_total_code').value == 'ot_shipping') {
            document.getElementById('update_total_shipping').style.display = 'table-cell';
        } else {
            document.getElementById('update_total_shipping').style.display = 'none';
        }
    }
    document.getElementById('update_total_code').onchange = function(){handleShipping();};
    // -->
</script>
<!-- body_eof //-->
<?php
}

if (DISPLAY_PRICE_WITH_TAX === 'true') {
?>
<script>
$(document).ready(function() {
    $('.p-n, .p-t').on('keyup', function(e) {
        var opi = $(this).attr('data-opi');
        updateProductGross(opi);
    });

    $('.p-g').on('keyup', function(e) {
        var opi = $(this).attr('data-opi');
        updateProductNet(opi);
    });

    function doRound(x, places)
    {
        return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
    }

    function getProductTaxRate(opi)
    {
        return getValidatedTaxRate($('input[name="update_products['+opi+'][tax]"]').val());
    }
    function getValidatedTaxRate(taxRate)
    {
        var regex = /(?:\d*\.\d{1,2}|\d+)$/;
        return (regex.test(taxRate)) ? taxRate : 0;
    }

    function updateProductGross(opi)
    {
        var taxRate = getProductTaxRate(opi);
        var gross = $('input[name="update_products['+opi+'][final_price]"]').val();

        if (taxRate > 0) {
            gross = gross * ((taxRate / 100) + 1);
        }
        $('input[name="update_products['+opi+'][gross]"]').val(doRound(gross, 4));
    }

    function updateProductNet(opi)
    {
        var taxRate = getProductTaxRate(opi);
        var net = $('input[name="update_products['+opi+'][gross]"]').val();

        if (taxRate > 0) {
            net = net / ((taxRate / 100) + 1);
        }
        $('input[name="update_products['+opi+'][final_price]"]').val(doRound(net, 4));
    }
    
    $('#s-t, #s-n').on('keyup', function(e) {
        updateShippingGross();
    });
    $('#s-g').on('keyup', function(e) {
        updateShippingNet();
    });

    function getShippingTaxRate()
    {
        return getValidatedTaxRate($('#s-t').val());
    }

    function updateShippingGross()
    {
        var taxRate = getShippingTaxRate();
        var gross = $('#s-n').val();
        if (taxRate > 0) {
            gross = gross * ((taxRate / 100) + 1);
        }
        $('#s-g').val(doRound(gross, 4));
    }

    function updateShippingNet()
    {
        var taxRate = getShippingTaxRate();
        var net = $('#s-g').val();
        if (taxRate > 0) {
            net = net / ((taxRate / 100) + 1);
        }
        $('#s-n').val(doRound(net, 4));
    }
});
</script>
<?php
}

// -----
// Give a watching observer the opportunity to identify additional .js files, present
// in the /admin/includes/javascript sub-directory, for inclusion in EO's display
// processing.
//
// The observer sets the $addl_js_files value passed to be a comma-separated list
// of file names to be included.
//
// Observer note:  Be sure to add a leading ', ' to any updates if, on receipt of the
// notification, the $addl_js_files (i.e. $p2) is not empty!
//
$addl_js_files = '';
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_JS', '', $addl_js_files);
if (!empty($addl_js_files)) {
    $js_files = explode(',', str_replace(' ', '', (string)$addl_js_files));
    foreach ($js_files as $js_filename) {
        if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9\.\-_]*$/', $js_filename)) {
            $eo->eoLog("Additional javascript file ($js_filename) not included, due to filename character mismatch.");
        } else {
            $js_file = DIR_WS_INCLUDES . 'javascript' . DIRECTORY_SEPARATOR . "$js_filename.js";
?>
<script src="<?php echo $js_file; ?>"></script>
<?php
        }
    }
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
