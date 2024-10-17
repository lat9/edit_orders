<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019-2024, Vinos de Frutas Tropicales.
//
// Last modified v5.0.0
//
// This module is loaded in global scope by /admin/edit_orders.php.
//
// -----
// Gather the maximum database field-length for each of the address-related fields in the
// order, noting that the ASSUMPTION is made that each of the customer/billing/delivery fields
// are of equal length!
//
$max_name_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_name') . '"';
$max_company_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_company') . '"';
$max_street_address_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_street_address') . '"';
$max_suburb_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_suburb') . '"';
$max_city_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_city') . '"';
$max_state_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_state') . '"';
$max_postcode_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_postcode') . '"';
$max_country_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_country') . '"';
$max_telephone_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_telephone') . '"';
$max_email_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_email_address') . '"';
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

    <div class="col-sm-12 col-lg-4">
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
} else {
    $address_icon = 'fa-solid fa fa-truck';
    $address_label = ENTRY_SHIPPING_ADDRESS;
    $address_name = 'delivery';
    $address_fields = $order->delivery;
    $address_notifier = 'NOTIFY_EO_ADDL_SHIPPING_ADDRESS_ROWS';
}
require 'eo_common_address_format.php';
?>
    </div>

    <div class="col-sm-12 col-lg-4">
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
} else {
    $address_icon = 'fa-regular fa fa-credit-card';
    $address_label = ENTRY_BILLING_ADDRESS;
    $address_name = 'billing';
    $address_fields = $order->billing;
    $address_notifier = 'NOTIFY_EO_ADDL_BILLING_ADDRESS_ROWS';
}
require 'eo_common_address_format.php';
?>
    </div>
</div>
