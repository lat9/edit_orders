<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
//-Last modified 20210304-lat9 Edit Orders v4.6.0
//
// -----
// Prior to EO v4.6.0, this code was in-line in the main /admin/edit_orders.php script.  Now required by
// that script in global context for its 'update_order' action.
//
$comments = zen_db_prepare_input($_POST['comments']);
$status = (int)$_POST['status'];
if ($status < 1) {
    return;
}

$order_updated = false;
$sql_data_array = [
    'customers_name' => $_POST['update_customer_name'],
    'customers_company' => $_POST['update_customer_company'],
    'customers_street_address' => $_POST['update_customer_street_address'],
    'customers_suburb' => $_POST['update_customer_suburb'],
    'customers_city' => $_POST['update_customer_city'],
    'customers_state' => $_POST['update_customer_state'],
    'customers_postcode' => $_POST['update_customer_postcode'],
    'customers_country' => $_POST['update_customer_country'],
    'customers_telephone' => $_POST['update_customer_telephone'],
    'customers_email_address' => $_POST['update_customer_email_address'],
    'last_modified' => 'now()',

    'billing_name' => $_POST['update_billing_name'],
    'billing_company' => $_POST['update_billing_company'],
    'billing_street_address' => $_POST['update_billing_street_address'],
    'billing_suburb' => $_POST['update_billing_suburb'],
    'billing_city' => $_POST['update_billing_city'],
    'billing_state' => $_POST['update_billing_state'],
    'billing_postcode' => $_POST['update_billing_postcode'],
    'billing_country' => $_POST['update_billing_country'],

    'delivery_name' => $_POST['update_delivery_name'],
    'delivery_company' => $_POST['update_delivery_company'],
    'delivery_street_address' => $_POST['update_delivery_street_address'],
    'delivery_suburb' => $_POST['update_delivery_suburb'],
    'delivery_city' => $_POST['update_delivery_city'],
    'delivery_state' => $_POST['update_delivery_state'],
    'delivery_postcode' => $_POST['update_delivery_postcode'],
    'delivery_country' => $_POST['update_delivery_country'],
    'payment_method' => $_POST['update_info_payment_method'],
    'cc_type' => (isset($_POST['update_info_cc_type'])) ? $_POST['update_info_cc_type'] : '',
    'cc_owner' => (isset($_POST['update_info_cc_owner'])) ? $_POST['update_info_cc_owner'] : '',
    'cc_expires' => (isset($_POST['update_info_cc_expires'])) ? $_POST['update_info_cc_expires'] : '',
    'order_tax' => 0
];

// If the country was passed as an id, change it to the country name for
// storing in the database. This is done in case a country is removed in
// the future, so the country name is still associated with the order.
if (ctype_digit($sql_data_array['customers_country'])) {
    $sql_data_array['customers_country'] = zen_get_country_name((int)$sql_data_array['customers_country']);
}
if (ctype_digit($sql_data_array['billing_country'])) {
    $sql_data_array['billing_country'] = zen_get_country_name((int)$sql_data_array['billing_country']);
}
if (ctype_digit($sql_data_array['delivery_country'])) {
    $sql_data_array['delivery_country'] = zen_get_country_name((int)$sql_data_array['delivery_country']);
}

// For PA-DSS Compliance, we no longer store the Credit Card number in
// the database. While inconvenient, this saves us in the event of an audit.
if (isset($_POST['update_info_cc_number'])) {
    $update_info_cc_number = zen_db_prepare_input($_POST['update_info_cc_number']);

    // If the number is not already obfuscated, we use the same method
    // as the authorize.net module to obfuscate the entered CC number
    if (ctype_digit($update_info_cc_number)) {
        $update_info_cc_number = str_pad(substr($_POST['update_info_cc_number'], -4), strlen($_POST['update_info_cc_number']), "X", STR_PAD_LEFT);
    }

    $sql_data_array['cc_number'] = $update_info_cc_number;
    unset($_POST['update_info_cc_number']);
}

// -----
// Give any listening observer the opportunity to make modifications to the SQL data associated
// with the updated order and/or disallow the update.
//
// If the observer disallows the update (by setting the 3rd parameter to (bool)false), it's the observer's responsibility 
// to issue a message to the current admin to let them know why the update was denied.
//
// Note that (currently) any updates made to the order will be lost!
//
$allow_update = true;
$zco_notifier->notify('EDIT_ORDERS_PRE_UPDATE_ORDER', $oID, $sql_data_array, $allow_update);
if ($allow_update === false) {
    $eo->eoLog("Update disallowed by observer.");
    return;
}
zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = $oID LIMIT 1");

// -----
// Update the order's status-history (and send emails, if requested) via the common
// Zen Cart function.
//
$email_include_message = (isset($_POST['notify_comments']) && $_POST['notify_comments'] == 'on');
$customer_notified = isset($_POST['notify']) ? (int)$_POST['notify'] : 0;

$status_updated = zen_update_orders_history($oID, $comments, null, $status, $customer_notified, $email_include_message);
$order_updated = ($status_updated > 0);

// -----
// Load the order's current details (noting that any address-related updates were performed
// by previous processing.
//
$order = $eo->getOrderInfo($action);
$eo->eoLog (
    PHP_EOL . 'Order Subtotal: ' . $order->info['subtotal'] . PHP_EOL .
    $eo->eoFormatOrderTotalsForLog($order) .
    'Order Tax (total): ' . $order->info['tax'] . PHP_EOL .
    'Order Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
);

// -----
// Give an observer the opportunity to make modifications to the updated products (or totals) in
// the update request.
//
$zco_notifier->notify('EDIT_ORDERS_PRE_UPDATE_PRODUCTS', $oID);

// Handle updating products and attributes as needed
if (isset($_POST['update_products'])) {
    // -----
    // Sometimes, EO needs a reset to its order-totals processing, especially in the presence
    // of order-totals that make tax-related changes (like group_pricing).  If the admin has ticked the "Reset" box,
    // clear out all the tax and total-related values and also reset the order-totals currently 
    // applied to start afresh.
    //
    if (isset($_POST['reset_totals'])) {
        $order->info['tax'] = $order->info['shipping_tax'] = $order->info['shipping_cost'] = $order->info['total'] = $order->info['subtotal'] = 0;
        $order->totals = [];
        foreach ($order->info['tax_groups'] as $key => $value) {
            $order->info['tax_groups'][$key] = 0;
        }
    }
    
    // -----
    // Initialize the shipping cost, tax-rate and tax-value.
    //
    $eo->eoInitializeShipping($oID, $action);

    $_POST['update_products'] = zen_db_prepare_input($_POST['update_products']);

    $eo->eoLog (
        PHP_EOL . 'Requested Products:' . PHP_EOL . $eo->eoFormatArray($_POST['update_products']) . PHP_EOL .
        'Products in Original Order: ' . PHP_EOL . $eo->eoFormatArray($order->products)
    );

    // -----
    // Determine the 'base' price-calculation method to be used, setting the message to be
    // written to the order's status-history once the order's products have been processed.
    //
    if (EO_PRODUCT_PRICE_CALC_METHOD == 'Auto' || EO_PRODUCT_PRICE_CALC_METHOD == 'AutoSpecials' || (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose' && $_POST['payment_calc_method'] != 3)) {
        $price_calc_method_message = EO_MESSAGE_PRICING_AUTO;
        if (EO_PRODUCT_PRICE_CALC_METHOD == 'AutoSpecials' || (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose' && $_POST['payment_calc_method'] == 1)) {
            $price_calc_method_message = EO_MESSAGE_PRICING_AUTOSPECIALS;
        }
        $price_calc_method = 'auto';
    } else {
        $price_calc_method_message = EO_MESSAGE_PRICING_MANUAL;
        $price_calc_method = 'manual';
    }

    foreach ($_POST['update_products'] as $orders_products_id => $product_update) {
        $product_update['qty'] = floatval($product_update['qty']);
        $product_update['name'] = $product_update['name'];
        if (empty($product_update['tax'])) {
            $product_update['tax'] = 0;
        }
        if (empty($product_update['final_price'])) {
            $product_update['final_price'] = 0;
        }

        $rowID = -1;
        $orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);
        for ($i = 0, $n = count($orders_products_id_mapping); $i < $n; $i++) {
            if ($orders_products_id == $orders_products_id_mapping[$i]) {
                $rowID = $i;
                break;
            }
        }
        unset($orders_products_id_mapping, $i, $n);

        $eo->eoLog (
            PHP_EOL . 'Order Product ID: ' . $orders_products_id . ' Row ID: ' . $rowID . PHP_EOL .
            'Product in Request: ' . PHP_EOL . $eo->eoFormatArray($product_update)
        );
                
        // Only update if there is an existing item in the order
        if ($rowID >= 0) {
            // Grab the old product + attributes
            $old_product = $order->products[$rowID];

            $eo->eoLog (
                PHP_EOL . 'Old Product:' . PHP_EOL . $eo->eoFormatArray($old_product) . PHP_EOL .
                'Old Order Subtotal: ' . $order->info['subtotal'] . PHP_EOL .
                $eo->eoFormatOrderTotalsForLog($order, 'Old Order Totals: ') .
                'Old Tax (total): ' . $order->info['tax'] . PHP_EOL .
                'Old Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
            );
            // Remove the product from the order
            eo_remove_product_from_order($oID, $orders_products_id);

            // Update Subtotal and Pricing, only if not resetting totals.
            if (empty($_POST['reset_totals'])) {
                eo_update_order_subtotal($oID, $old_product, false);
            }

            $eo->eoLog (
                PHP_EOL . 'Removed Product Order Subtotal: ' . $order->info['subtotal'] . PHP_EOL .
                $eo->eoFormatOrderTotalsForLog($order, 'Removed Product Order Totals: ') . 
                'Removed Product Tax (total): ' . $order->info['tax'] . PHP_EOL .
                'Removed Product Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
            );
            if ($product_update['qty'] > 0) {

                // Retrieve the information for the new product
                $attrs = (isset($product_update['attr'])) ? $product_update['attr'] : '';
                unset($product_update['attr']);
                $new_product = eo_get_new_product(
                    $old_product['id'],
                    $product_update['qty'],
                    $product_update['tax'],
                    $attrs,
                    (EO_PRODUCT_PRICE_CALC_METHOD == 'AutoSpecials' || (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose' && $_POST['payment_calc_method'] == 1))
                );
                unset($attrs);
                
                // Handle the case where the product was deleted
                // from the store. This should probably never be done.
                // Removing the product will cause issues with links
                // on invoices (order history) and will not allow the
                // price(s) or tax(es) to be recalculated by Zen Cart.
                if (!isset($new_product['price'])) {
                    $new_product['price'] = $old_product['price'];
                    $new_product['tax'] = $old_product['tax'];
                    if ($new_product['tax'] > 0) {
                        // Should match what is set by eo_get_product_taxes()
                        // When no description is present in the database but
                        // a tax rate exists on a product.
                        $new_product['tax_description'] = TEXT_UNKNOWN_TAX_RATE . ' (' . zen_display_tax_value($new_product['tax']) . '%)';
                    }

                    $new_product['products_discount_type'] = $old_product['products_discount_type'];
                    $new_product['products_discount_type_from'] = $old_product['products_discount_type_from'];
                    $new_product['products_priced_by_attribute'] = $old_product['products_priced_by_attribute'];
                    $new_product['product_is_free'] = $old_product['product_is_free'];
                }

                // -----
                // Depending on the product-price calculation method, either the values entered
                // or the pricing just calculated "rule".
                //
                if ($price_calc_method == 'auto') {
                    $new_product = array_merge($product_update, $new_product);
                } else {
                    $new_product = array_merge($new_product, $product_update);
                }
                
                // -----
                // If the admin has an option to "Choose" the pricing calculation method, save the
                // current selection in the session so that it's maintained during their processing.
                //
                if (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose') {
                    $_SESSION['eo_price_calculations'] = $_POST['payment_calc_method'];
                }

                // Add the product to the order
                eo_add_product_to_order($oID, $new_product);

                // Update Subtotal and Pricing
                eo_update_order_subtotal($oID, $new_product);

                $eo->eoLog (
                    PHP_EOL . $price_calc_method . PHP_EOL .
                    'Added Product:' . PHP_EOL . $eo->eoFormatArray($new_product) . PHP_EOL .
                    'Added Product Order Subtotal: ' . $order->info['subtotal'] . PHP_EOL .
                     $eo->eoFormatOrderTotalsForLog($order, 'Added Product Order Totals:') .
                    'Added Product Tax (total): ' . $order->info['tax'] . PHP_EOL .
                    'Added Product Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
                );
            }
            $order_updated = true;
        }
    }
    
    // -----
    // If the order's been updated ...
    //
    if ($order_updated) {
        // -----
        // Add an orders-status-history record, identifying that an update was performed.
        //
        $eo->eoRecordStatusHistory($oID, EO_MESSAGE_ORDER_UPDATED . $price_calc_method_message);
        
        // -----
        // Need to force update the tax field if the tax is zero.
        // This runs after the shipping tax is added by the above update
        //
        $decimals = $currencies->get_decimal_places($_SESSION['currency']);
        if (zen_round($order->info['tax'], $decimals) == 0) {
            if (!isset($_POST['update_total'])) {
                $_POST['update_total'] = [];
            }
            $_POST['update_total'][] = [
                'code' => 'ot_tax',
                'title' => '',
                'value' => 0,
            ];
        }
    }

    // -----
    // Fix-up the order's product-based taxes by summing all values present in the order's
    // tax-groups prior to running the order-totals.  Any addition for shipping will be added
    // later.
    //
    if (isset($order->info['tax_groups']) && is_array($order->info['tax_groups'])) {
        $order->info['tax'] = 0;
        foreach ($order->info['tax_groups'] as $tax_class => $tax_value) {
            $order->info['tax'] += $tax_value;
        }
    }
    $eo->eoLog (
        PHP_EOL . 'Updated Products in Order:' . PHP_EOL . $eo->eoFormatArray($order->products) . PHP_EOL .
        $eo->eoFormatOrderTotalsForLog($order, 'Updated Products Order Totals:') .
        'Updated Products Tax (total): ' . $order->info['tax'] . PHP_EOL .
        'Updated Products Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups']) . PHP_EOL
    );
}

// Update order totals (or delete if no title / value)
if (isset($_POST['update_total'])) {
    $eo->eoLog (
        PHP_EOL . '============================================================' .
        PHP_EOL . '= Processing Requested Updates to Order Totals' .
        PHP_EOL . '============================================================' .
        PHP_EOL . PHP_EOL .
        'Requested Order Totals:' . PHP_EOL . $eo->eoFormatArray($_POST['update_total']) . PHP_EOL .
         $eo->eoFormatOrderTotalsForLog($order, 'Starting Order Totals:') .
        'Starting Tax (total): ' . $order->info['tax'] . PHP_EOL .
        'Starting Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
    );

    foreach ($_POST['update_total'] as $order_total) {
        $order_total['value'] = floatval($order_total['value']);
        $order_total['text'] = $eo->eoFormatCurrencyValue($order_total['value']);
        $order_total['sort_order'] = $eo->eoGetOrderTotalSortOrder($order_total['code']);

        // TODO Special processing for some modules
        if (zen_not_null($order_total['title']) && $order_total['title'] != ':') {
            switch ($order_total['code']) {
                case 'ot_shipping':
                    $order->info['shipping_method'] = $order_total['title'];
                    $order->info['shipping_module_code'] = $order_total['shipping_module'];
                    break;
                case 'ot_tax':
                    if (count($order->products) == 0) {
                        $order_total['title'] = '';
                        $order_total['value'] = 0;
                    }
                    $order->info['tax'] = $order_total['value'];
                    break;
                case 'ot_gv':
                    if ($order_total['value'] < 0) {
                        $order_total['value'] = $order_total['value'] * -1;
                    }
                    $order_total['text'] = $eo->eoFormatCurrencyValue($order_total['value']);
                    $_SESSION['cot_gv'] = $order_total['value'];
                    break;
                case 'ot_voucher':
                    if ($order_total['value'] < 0) {
                        $order_total['value'] = $order_total['value'] * -1;
                    }
                    $order_total['text'] = $eo->eoFormatCurrencyValue($order_total['value']);
                    $_SESSION['cot_voucher'] = $order_total['value'];
                    break;
                case 'ot_coupon':
                    // Default to using the title from the module
                    $coupon = rtrim($order_total['title'], ': ');
                    $order_total['title'] = (isset($GLOBALS['ot_coupon'])) ? $GLOBALS['ot_coupon']->title : '';

                    // Look for correctly formatted title
                    preg_match('/([^:]+):([^:]+)/', $coupon, $matches);
                    if (count($matches) > 2) {
                        $order_total['title'] = trim($matches[1]);
                        $coupon = $matches[2];
                    }
                    $coupon = trim($coupon);
                    $cc_id = $db->Execute(
                        "SELECT coupon_id, coupon_type
                           FROM ". TABLE_COUPONS . "
                          WHERE coupon_code = '$coupon'
                          LIMIT 1"
                    );

                    if (!$cc_id->EOF) {
                        $_SESSION['cc_id'] = $cc_id->fields['coupon_id'];
                    } else {
                        $coupon = '';
                        $messageStack->add_session(WARNING_ORDER_COUPON_BAD, 'warning');
                        $order_total['title'] = '';
                        $order_total['value'] = 0;
                    }
                    $db->Execute(
                        "UPDATE " . TABLE_ORDERS . "
                            SET coupon_code = '$coupon'
                          WHERE orders_id = $oID
                          LIMIT 1"
                    );
                    break;
                default:
                    break;
            }
        }

        $found = false;
        foreach ($order->totals as $key => $total) {
            if ($total['class'] == $order_total['code']) {
                // Update the information in the order
                $order->totals[$key]['title'] = $order_total['title'];
                $order->totals[$key]['value'] = $order_total['value'];
                $order->totals[$key]['text'] = $order_total['text'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $order->totals[] = [
                'class' => $order_total['code'],
                'title' => $order_total['title'],
                'value' => $order_total['value'],
                'text' => $order_total['text'],
                'sort_order' => $order_total['sort_order']
            ];
        }

        // Always update the database (allows delete)
        eo_update_database_order_total($oID, $order_total);
    }

    // Update the order's order-totals
    eo_update_database_order_totals($oID);
    
    // -----
    // Update the product's weight, too.
    //
    $db->Execute(
        "UPDATE " . TABLE_ORDERS . "
            SET order_weight = " . $_SESSION['cart']->show_weight() . "
          WHERE orders_id = $oID
          LIMIT 1"
    );

    $eo->eoLog (
        $eo->eoFormatOrderTotalsForLog($order) . PHP_EOL .
        'Updated Tax (total): ' . $order->info['tax'] . PHP_EOL .
        'Updated Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
    );

    // Unset some session variables after updating the order totals
    unset($_SESSION['cot_gv'], $_SESSION['cot_voucher'], $_SESSION['cc_id']);
    $order_updated = true;
}

if ($order_updated) {
    $messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
    $zco_notifier->notify('EDIT_ORDER_ORDER_UPDATED_SUCCESS', $oID);
} else {
    $messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
}

$eo->eoLog (
    PHP_EOL . '============================================================' .
    PHP_EOL . '= Done Processing Requested Updates to the Order' .
    PHP_EOL . '============================================================' .
    PHP_EOL . PHP_EOL .
    'Final Subtotal: ' . $order->info['subtotal'] . PHP_EOL .
     $eo->eoFormatOrderTotalsForLog($order, 'Final Totals:') . 
    'Final Tax (total): ' . $order->info['tax'] . PHP_EOL .
    'Final Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups'])
);
$zco_notifier->notify('EDIT_ORDERS_ORDER_UPDATED', $order);
