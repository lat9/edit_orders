<?php
// -----
// Admin-level initialization script for the Edit Orders plugin for Zen Cart, by lat9.
// Copyright (C) 2018-2024, Vinos de Frutas Tropicales.
//
// Last updated: v5.0.0
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

global $PHP_SELF;

// -----
// Now loaded prior to language-loading to identify the current page
// as 'edit_orders' for EO's AJAX processing, so that the associated language
// constants will be pulled in for EO during its AJAX processing.
//
if ($PHP_SELF === 'ajax.php' && ($_GET['act'] ?? '') === 'ajaxEditOrdersAdmin') {
    $PHP_SELF = 'edit_orders.php';
    return;
}
if ($PHP_SELF === 'keepalive.php' || $PHP_SELF === FILENAME_EDIT_ORDERS . '.php') {
    return;
}

// -----
// If a previous 'run' of EO has saved a pre-existing currency into the session, restore
// that value at this point.
//
if (isset($_SESSION['eo_saved_currency'])) {
    if ($_SESSION['eo_saved_currency'] === false) {
        unset($_SESSION['currency']);
    } else {
        $_SESSION['currency'] = $_SESSION['eo_saved_currency'];
    }
    unset($_SESSION['eo_saved_currency']);
}

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
