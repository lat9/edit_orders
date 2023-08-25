<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019-2021, Vinos de Frutas Tropicales.
//
//-Last modified 20210321-lat9 Edit Orders v4.6.0
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
?>
<div class="row" id="c-form">
    <div class="col-sm-4">
<?php
// -----
// Set variables for common address-format display.
//
$address_icon = 'icon_customers.png';
$address_label = ENTRY_CUSTOMER;
$address_name = 'customer';
$address_fields = $order->customer;
$address_notifier = 'EDIT_ORDERS_ADDL_CUSTOMER_ADDRESS_ROWS';
require DIR_WS_MODULES . 'edit_orders/eo_common_address_format.php';
?>
    </div>

    <div class="col-sm-4">
<?php
// -----
// Set variables for common address-format display, based on the site's preferential order.
//
if (EO_ADDRESSES_DISPLAY_ORDER === 'CBS') {
    $address_icon = 'icon_billing.png';
    $address_label = ENTRY_BILLING_ADDRESS;
    $address_name = 'billing';
    $address_fields = $order->billing;
    $address_notifier = 'EDIT_ORDERS_ADDL_BILLING_ADDRESS_ROWS';
} else {
    $address_icon = 'icon_shipping.png';
    $address_label = ENTRY_SHIPPING_ADDRESS;
    $address_name = 'delivery';
    $address_fields = $order->delivery;
    $address_notifier = 'EDIT_ORDERS_ADDL_SHIPPING_ADDRESS_ROWS';
}
require DIR_WS_MODULES . 'edit_orders/eo_common_address_format.php';
?>
    </div>

    <div class="col-sm-4">
<?php
// -----
// Set variables for common address-format display, based on the site's preferential order.
//
if (EO_ADDRESSES_DISPLAY_ORDER === 'CBS') {
    $address_icon = 'icon_shipping.png';
    $address_label = ENTRY_SHIPPING_ADDRESS;
    $address_name = 'delivery';
    $address_fields = $order->delivery;
    $address_notifier = 'EDIT_ORDERS_ADDL_SHIPPING_ADDRESS_ROWS';
} else {
    $address_icon = 'icon_billing.png';
    $address_label = ENTRY_BILLING_ADDRESS;
    $address_name = 'billing';
    $address_fields = $order->billing;
    $address_notifier = 'EDIT_ORDERS_ADDL_BILLING_ADDRESS_ROWS';
}
require DIR_WS_MODULES . 'edit_orders/eo_common_address_format.php';
?>
    </div>
</div>
