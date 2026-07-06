<?php
// -----
// Admin-level initialization script for the Edit Orders plugin for Zen Cart, by lat9.
// Copyright (C) 2018-2026, Vinos de Frutas Tropicales.
//
// Last updated: v5.0.3
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
$eo_page_check = basename($PHP_SELF ?? '');

if ($eo_page_check === 'ajax.php' && ($_GET['act'] ?? '') === 'ajaxEditOrdersAdmin') {
    $PHP_SELF = FILENAME_EDIT_ORDERS . '.php';
    return;
}

if ($eo_page_check === 'keepalive.php' || $eo_page_check === FILENAME_EDIT_ORDERS . '.php') {
    return;
}

// Do not let background/login/session-check requests wipe an active Edit Orders session.
if (isset($_SESSION['eoChanges']) && in_array($eo_page_check, ['login.php', 'mailbeez.php'], true)) {
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
    $_SESSION['shipping_tax_amount'],
    $_SESSION['valid_to_checkout'],
);
