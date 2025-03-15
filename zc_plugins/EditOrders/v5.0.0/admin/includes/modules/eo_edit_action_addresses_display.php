<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019-2025, Vinos de Frutas Tropicales.
//
// Last modified v5.0.0
//
// This module is loaded in global scope by /admin/edit_orders.php.
//
?>
<div id="addr-form" class="row">
    <div class="col-sm-12 col-lg-4">
<?php
// -----
// Set variables for common address-format display.
//
$address_icon = 'fa-solid fa fa-user';
$address_label = ENTRY_CUSTOMER;
$address_name = 'customer';
$address_fields = $order->customer;
$address_notifier = 'NOTIFY_EO_ADDL_CUSTOMER_ADDRESS_ROWS';
require 'eo_common_address_format.php';
?>
    </div>
<?php
// -----
// Set variables for common address-format display, based on the site's preferential order.
//
if (EO_ADDRESSES_DISPLAY_ORDER === 'CBS') {
    $address_icon = 'fa-regular fa fa-credit-card';
    $address_label = ENTRY_BILLING_ADDRESS;
    $address_name = 'billing';
    $address_fields = $order->billing;
    $address_notifier = 'NOTIFY_EO_ADDL_BILLING_ADDRESS_ROWS';
    $shipping_block_id = '';
} else {
    $address_icon = 'fa-solid fa fa-truck';
    $address_label = ENTRY_SHIPPING_ADDRESS;
    $address_name = 'delivery';
    $address_fields = $order->delivery;
    $address_notifier = 'NOTIFY_EO_ADDL_SHIPPING_ADDRESS_ROWS';
    $shipping_block_id = 'id="eo-shipping-address"';
}
?>
    <div <?= $shipping_block_id ?> class="col-sm-12 col-lg-4">
        <?php require 'eo_common_address_format.php'; ?>
    </div>
<?php
// -----
// Set variables for common address-format display, based on the site's preferential order.
//
if (EO_ADDRESSES_DISPLAY_ORDER === 'CBS') {
    $address_icon = 'fa-solid fa fa-truck';
    $address_label = ENTRY_SHIPPING_ADDRESS;
    $address_name = 'delivery';
    $address_fields = $order->delivery;
    $address_notifier = 'NOTIFY_EO_ADDL_SHIPPING_ADDRESS_ROWS';
    $shipping_block_id = 'id="eo-shipping-address"';
} else {
    $address_icon = 'fa-regular fa fa-credit-card';
    $address_label = ENTRY_BILLING_ADDRESS;
    $address_name = 'billing';
    $address_fields = $order->billing;
    $address_notifier = 'NOTIFY_EO_ADDL_BILLING_ADDRESS_ROWS';
    $shipping_block_id = '';
}
?>
    <div <?= $shipping_block_id ?> class="col-sm-12 col-lg-4">
        <?php require 'eo_common_address_format.php'; ?>
    </div>
</div>
