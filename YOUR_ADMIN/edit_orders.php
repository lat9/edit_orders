<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+

require 'includes/application_top.php';

// Check for commonly broken attribute related items
eo_checks_and_warnings();

// Start the currencies code
if (!class_exists('currencies')) {
    require DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php';
}
$currencies = new currencies();

// Use the normal order class instead of the admin one
include DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';

$oID = (int)zen_db_prepare_input($_GET['oID']);

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

$orders_statuses = array();
$orders_status_array = array();
$orders_status_query = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . (int)$_SESSION['languages_id'] . "
  ORDER BY orders_status_id"
);
while (!$orders_status_query->EOF) {
    $status_id = $orders_status_query->fields['orders_status_id'];
    $status_name = $orders_status_query->fields['orders_status_name'];
    $orders_statuses[] = array(
        'id' => $status_id,
        'text' => "$status_name [$status_id]"
    );
    $orders_status_array[$status_id] = $status_name;
    
    $orders_status_query->MoveNext();
}

$action = (isset($_GET['action']) ? $_GET['action'] : 'edit');

if (zen_not_null($action)) {
    $eo->eoLog(PHP_EOL . date('Y-m-d H:i:s') . ", Edit Orders entered (". EO_VERSION . ") action ($action)" . PHP_EOL . 'Enabled Order Totals: ' . MODULE_ORDER_TOTAL_INSTALLED, 1);

    switch ($action) {
        // Update Order
        case 'update_order':
            $comments = zen_db_prepare_input($_POST['comments']);
            $status = (int)zen_db_prepare_input($_POST['status']);
            if ($status < 1) break;

            $order_updated = false;
            $sql_data_array = array(
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
            );

            // If the country was passed as an id, change it to the country name for
            // storing in the database. This is done in case a country is removed in
            // the future, so the country name is still associated with the order.
            if (is_numeric($sql_data_array['customers_country'])) {
                $sql_data_array['customers_country'] = zen_get_country_name((int)$sql_data_array['customers_country']);
            }
            if (is_numeric($sql_data_array['billing_country'])) {
                $sql_data_array['billing_country'] = zen_get_country_name((int)$sql_data_array['billing_country']);
            }
            if (is_numeric($sql_data_array['delivery_country'])) {
                $sql_data_array['delivery_country'] = zen_get_country_name((int)$sql_data_array['delivery_country']);
            }

            // For PA-DSS Compliance, we no longer store the Credit Card number in
            // the database. While inconvenient, this saves us in the event of an audit.
            if (isset($_POST['update_info_cc_number'])) {
                $update_info_cc_number = zen_db_prepare_input($_POST['update_info_cc_number']);

                // If the number is not already obscufated, we use the same method
                // as the authorize.net module to obscufate the entered CC number
                if (is_numeric($update_info_cc_number)) {
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
                $action = 'edit';
                break;
            }
            zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = $oID LIMIT 1");

            // BEGIN TY TRACKER 1 - READ FROM POST
            $track_id = array();
            if (defined('TY_TRACKER') && TY_TRACKER == 'True') {
                $track_id = zen_db_prepare_input($_POST['track_id']);
                $ty_changed = false;
                foreach ($track_id as $id => $track) {
                    $carrier_constant = "CARRIER_STATUS_$id";
                    if (defined($carrier_constant) && constant($carrier_constant) == 'True' && zen_not_null($track)) {
                        $ty_changed = true;
                    }
                }
                if (!$ty_changed) {
                    $track_id = array();
                }
            }
            // END TY TRACKER 1 - READ FROM POST
            $check_status = $db->Execute(
                "SELECT customers_name, customers_email_address, orders_status, date_purchased 
                   FROM " . TABLE_ORDERS . "
                  WHERE orders_id = $oID
                  LIMIT 1"
            );

            // Begin - Update Status History & Email Customer if Necessary
            if ($check_status->fields['orders_status'] != $status || zen_not_null($track_id) || zen_not_null($comments)) {
                $customer_notified = '0';
                if (isset($_POST['notify']) && $_POST['notify'] == '1') {
                    $notify_comments = '';
                    if (isset($_POST['notify_comments']) && ($_POST['notify_comments'] == 'on')) {
                        if (zen_not_null($comments)) {
                            $notify_comments = EMAIL_TEXT_COMMENTS_UPDATE . $comments . PHP_EOL . PHP_EOL;
                        }
                        // BEGIN TY TRACKER 2 - EMAIL TRACKING INFORMATION
                        if (zen_not_null($track_id)) {
                            $notify_comments = EMAIL_TEXT_COMMENTS_TRACKING_UPDATE . PHP_EOL . PHP_EOL;
                            $comment = EMAIL_TEXT_COMMENTS_TRACKING_UPDATE;
                        }
                        foreach ($track_id as $id => $track) {
                            if (zen_not_null($track) && constant('CARRIER_STATUS_' . $id) == 'True') {
                                $notify_comments .= "Your " . constant('CARRIER_NAME_' . $id) . " Tracking ID is " . $track . " \n<br /><a href=" . constant('CARRIER_LINK_' . $id) . $track . ">Click here</a> to track your package. \n<br />If the above link does not work, copy the following URL address and paste it into your Web browser. \n<br />" . constant('CARRIER_LINK_' . $id) . $track . "\n\n<br /><br />It may take up to 24 hours for the tracking information to appear on the website." . "\n<br />";
                            }
                        }
                        unset($id, $track);
                        // END TY TRACKER 32 - EMAIL TRACKING INFORMATION
                    }
                    //send emails
                    $account_history_info_link = zen_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL');
                    $date_purchased = zen_date_long($check_status->fields['date_purchased']);
                    $order_status_label = sprintf(EMAIL_TEXT_STATUS_LABEL, $orders_status_array[$status]);
                    $message =
                        STORE_NAME . ' ' . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . PHP_EOL . PHP_EOL .
                        EMAIL_TEXT_INVOICE_URL . ' ' . $account_history_info_link . PHP_EOL . PHP_EOL .
                        EMAIL_TEXT_DATE_ORDERED . ' ' . $date_purchased . PHP_EOL . PHP_EOL .
                        strip_tags($notify_comments) .
                        EMAIL_TEXT_STATUS_UPDATED . $order_status_label .
                        EMAIL_TEXT_STATUS_PLEASE_REPLY;

                    $html_msg['EMAIL_CUSTOMERS_NAME'] = $check_status->fields['customers_name'];
                    $html_msg['EMAIL_TEXT_ORDER_NUMBER'] = EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID;
                    $html_msg['EMAIL_TEXT_INVOICE_URL']  = '<a href="' . $account_history_info_link .'">' . str_replace(':', '', EMAIL_TEXT_INVOICE_URL) . '</a>';
                    $html_msg['EMAIL_TEXT_DATE_ORDERED'] = EMAIL_TEXT_DATE_ORDERED . ' ' . $date_purchased;
                    $html_msg['EMAIL_TEXT_STATUS_COMMENTS'] = nl2br($notify_comments);
                    $html_msg['EMAIL_TEXT_STATUS_UPDATED'] = str_replace("\n", '', EMAIL_TEXT_STATUS_UPDATED);
                    $html_msg['EMAIL_TEXT_STATUS_LABEL'] = str_replace("\n", '', $order_status_label);
                    $html_msg['EMAIL_TEXT_NEW_STATUS'] = $orders_status_array[$status];
                    $html_msg['EMAIL_TEXT_STATUS_PLEASE_REPLY'] = str_replace("\n",'', EMAIL_TEXT_STATUS_PLEASE_REPLY);
                    $html_msg['EMAIL_PAYPAL_TRANSID'] = '';

                    zen_mail(
                        $check_status->fields['customers_name'], 
                        $check_status->fields['customers_email_address'], 
                        EMAIL_TEXT_SUBJECT . ' #' . $oID, 
                        $message, 
                        STORE_NAME, 
                        EMAIL_FROM, 
                        $html_msg, 
                        'order_status'
                    );
                    $customer_notified = '1';

                    // PayPal Trans ID, if any
                    $sql = 
                        "SELECT txn_id, parent_txn_id 
                           FROM " . TABLE_PAYPAL . " 
                          WHERE order_id = :orderID 
                       ORDER BY last_modified DESC, date_added DESC, parent_txn_id DESC, paypal_ipn_id DESC ";
                    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
                    $result = $db->Execute($sql);
                    if (!$result->EOF) {
                        $message .= PHP_EOL . PHP_EOL . ' PayPal Trans ID: ' . $result->fields['txn_id'];
                        $html_msg['EMAIL_PAYPAL_TRANSID'] = $result->fields['txn_id'];
                    }

                    //send extra emails
                    if (SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO_STATUS == '1' and SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO != '') {
                        zen_mail(
                            '', 
                            SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO, 
                            SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO_SUBJECT . ' ' . EMAIL_TEXT_SUBJECT . ' #' . $oID, 
                            $message, 
                            STORE_NAME, 
                            EMAIL_FROM, 
                            $html_msg, 
                            'order_status_extra'
                        );
                    }
                } elseif (isset($_POST['notify']) && $_POST['notify'] == '-1') {
                    // hide comment
                    $customer_notified = '-1';
                }
    //-bof-20160330-lat9-Don't over-prepare input (results in \n\n instead of two line-feeds).
                $sql_data_array = array(
                    'orders_id' => (int)$oID,
                    'orders_status_id' => $status,
                    'date_added' => 'now()',
                    'customer_notified' => $customer_notified,
                    'comments' => $comments,
                );
    //-eof-20160330-lat9
                // BEGIN TY TRACKER 3 - INCLUDE DATABASE FIELDS IN STATUS UPDATE
                foreach ($track_id as $id => $track) {
                    $sql_data_array['track_id' . $id] = zen_db_input($track);
                }
                unset($id, $track);
                // END TY TRACKER 3 - INCLUDE DATABASE FIELDS IN STATUS UPDATE
                zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

                $sql_data_array = array(
                    'orders_status' => zen_db_input($status),
                    'last_modified' => 'now()'
                );
                zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id=$oID LIMIT 1");
                unset($sql_data_array);
                $order_updated = true;
            }
            // End - Update Status History & Email Customer if Necessary

            // Load the order details.
            $order = $eo->getOrderInfo();

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
                    $order->info['tax'] = $order->info['shipping_tax'] = $order->info['shipping_cost'] = $order->info['total'] = 0;
                    $order->totals = array();
                }
                
                $_POST['update_products'] = zen_db_prepare_input($_POST['update_products']);

                $eo->eoLog (
                    PHP_EOL . 'Requested Products:' . PHP_EOL . $eo->eoFormatArray($_POST['update_products']) . PHP_EOL .
                    'Products in Original Order: ' . PHP_EOL . $eo->eoFormatArray($order->products)
                );

                foreach ($_POST['update_products'] as $orders_products_id => $product_update) {
                    $product_update['qty'] = (float)$product_update['qty'];
                    $product_update['name'] = $product_update['name'];

                    $rowID = -1;
                    $orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);
                    for ($i=0; $i<sizeof($orders_products_id_mapping); $i++) {
                        if ($orders_products_id == $orders_products_id_mapping[$i]) {
                            $rowID = $i;
                            break;
                        }
                    }
                    unset($orders_products_id_mapping, $i);

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

                        // Update Subtotal and Pricing
                        eo_update_order_subtotal($oID, $old_product, false);

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
                                false
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
                            if (EO_PRODUCT_PRICE_CALC_METHOD == 'Auto' || (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose' && !isset($_POST['payment_calc_manual']))) {
                                $price_calc_method = 'Pricing was automatically calculated.';
                                $new_product = array_merge($product_update, $new_product);
                            } else {
                                $price_calc_method = 'Pricing, as entered, was used.';
                                $new_product = array_merge($new_product, $product_update);
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
                // Reset order if updated
                if ($order_updated) {
                    // Need to force update the tax field if the tax is zero
                    // This runs after the shipping tax is added by the above update
                    $decimals = $currencies->get_decimal_places($_SESSION['currency']);
                    if (zen_round($order->info['tax'], $decimals) == 0) {
                        if (!isset($_POST['update_total'])) {
                            $_POST['update_total'] = array();
                        }
                        $_POST['update_total'][] = array(
                            'code' => 'ot_tax',
                            'title' => '',
                            'value' => 0,
                        );
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

                $default_sort = 0;
                foreach ($_POST['update_total'] as $order_total) {
                    $default_sort++;
                    $order_total['value'] = (float)$order_total['value'];
                    $order_total['text'] = $eo->eoFormatCurrencyValue($order_total['value']);
                    if (isset($GLOBALS[$order_total['code']]) && is_object($GLOBALS[$order_total['code']])) {
                    $order_total['sort_order'] = $GLOBALS[$order_total['code']]->sort_order;
                        $default_sort = $order_total['sort_order'];
                    } else {
                        $order_total['sort_order'] = $default_sort;
                    }

                    // TODO Special processing for some modules
                    if (zen_not_null($order_total['title']) && $order_total['title'] != ':') {
                        switch ($order_total['code']) {
                            case 'ot_shipping':
                                $order->info['shipping_cost'] = $order_total['value'];
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
                                $order_total['title'] = $GLOBALS[$order_total['code']]->title;

                                // Look for correctly formatted title
                                preg_match('/([^:]+):([^:]+)/', $coupon, $matches);
                                if (count($matches) > 2) {
                                    $order_total['title'] = trim($matches[1]);
                                    $coupon = $matches[2];
                                }
                                $cc_id = $db->Execute(
                                    'SELECT coupon_id FROM `' . TABLE_COUPONS . '` ' .
                                    'WHERE coupon_code=\'' . trim($coupon) . '\''
                                );
                                unset($matches, $coupon);

                                if(!$cc_id->EOF) {
                                    $_SESSION['cc_id'] = $cc_id->fields['coupon_id'];
                                } else {
                                    $messageStack->add_session(WARNING_ORDER_COUPON_BAD, 'warning');
                                    $order_total['title'] = '';
                                    $order_total['value'] = 0;
                                }
                                unset($cc_id);
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
                        $order->totals[] = array(
                            'class' => $order_total['code'],
                            'title' => $order_total['title'],
                            'value' => $order_total['value'],
                            'text' => $order_total['text'],
                            'sort_order' => $order_total['sort_order']
                        );
                    }

                    // Always update the database (allows delete)
                    eo_update_database_order_total($oID, $order_total);
                }

                // Update the order's order-totals
                eo_update_database_order_totals($oID);

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

            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit', 'NONSSL'));
            break;

        case 'add_prdct':
            if (!zen_not_null($step)) {
                $step = 1;
            }
            $eo->eoLog(
                PHP_EOL . '============================================================' .
                PHP_EOL . '= Adding a new product to the order (step ' . $step . ')' .
                PHP_EOL . '============================================================' .
                PHP_EOL . PHP_EOL
            );
            if ($step == 5) {
                $zco_notifier->notify('EDIT_ORDERS_START_ADD_PRODUCT', $oID);

                // Get Order Info
                $order = $eo->getOrderInfo();

                // Check qty field
                $add_max = zen_get_products_quantity_order_max($add_product_products_id);
                if ($add_product_quantity > $add_max && $add_max != 0) {
                    $add_product_quantity = $add_max;
                    $messageStack->add_session(WARNING_ORDER_QTY_OVER_MAX, 'warning');
                }

                // Retrieve the information for the new product
                $new_product = eo_get_new_product(
                    $add_product_products_id,
                    $add_product_quantity,
                    false,
                    zen_db_prepare_input($_POST['id']),
                    isset($_POST['applyspecialstoprice'])
                );

                // Add the product to the order
                $eo->eoLog(PHP_EOL . 'Product Being Added:' . PHP_EOL . $eo->eoFormatArray($new_product) . PHP_EOL);
                eo_add_product_to_order($oID, $new_product);

                // Update Subtotal and Pricing
                eo_update_order_subtotal($oID, $new_product);

                // Save the changes
                eo_update_database_order_totals($oID);
                $order = $eo->getOrderInfo();

                // Remove the low order and/or cod fees (will automatically repopulate if needed)
                foreach ($order->totals as $key => $total) {
                    if ($total['class'] == 'ot_loworderfee' || $total['class'] == 'ot_cod_fee') {
                        // Update the information in the order
                        $total['title'] = '';
                        $total['value'] = 0;
                        $total['code'] = $total['class'];

                        eo_update_database_order_total($oID, $total);
                        unset($order->totals[$key]);
                        break;
                    }
                }

                // Requires $GLOBALS['order'] to be reset and populated
                $order = $eo->getOrderInfo();
                eo_update_database_order_totals($oID);

                $eo->eoLog(
                    PHP_EOL . 'Final Products in Order:' . PHP_EOL . $eo->eoFormatArray($order->products) . PHP_EOL .
                    $eo->eoFormatOrderTotalsForLog($order, 'Final Order Totals:') .
                    'Final Tax (total): ' . $order->info['tax'] . PHP_EOL .
                    'Final Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups']) . PHP_EOL
                );
                $zco_notifier->notify('EDIT_ORDERS_PRODUCT_ADDED', $order);
                zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
            }
            break;
            
        default:
            break; 
    }
}

$order_exists = false;
if ($action == 'edit' && isset($_GET['oID'])) {
    $orders_query = $db->Execute(
        "SELECT orders_id FROM " . TABLE_ORDERS . " 
          WHERE orders_id = $oID
          LIMIT 1"
    );
    $order_exists = true;
    if ($orders_query->EOF) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    } else {
        $order = $eo->getOrderInfo();
        if (!$eo->eoOrderIsVirtual($order) &&
               ( !is_array($order->customer['country']) || !array_key_exists('id', $order->customer['country']) ||
                 !is_array($order->billing['country']) || !array_key_exists('id', $order->billing['country']) ||
                 !is_array($order->delivery['country']) || !array_key_exists('id', $order->delivery['country']) )) {
            $messageStack->add(WARNING_ADDRESS_COUNTRY_NOT_FOUND, 'warning');
        }
    }
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/edit_orders.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script type="text/javascript" src="includes/menu.js"></script>
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onload="init();">
<!-- header //-->
<div class="header-area">
<?php
    require(DIR_WS_INCLUDES . 'header.php');
?>
</div>
<!-- header_eof //-->
<?php
if ($action == 'edit' && $order_exists) {
    if ($order->info['payment_module_code']) {
        if (file_exists(DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php')) {
            require DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php';
            require DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/payment/' . $order->info['payment_module_code'] . '.php';
            $module = new $order->info['payment_module_code'];
        }
    }
// BEGIN - Add Super Orders Order Navigation Functionality
    $get_prev = $db->Execute(
        "SELECT orders_id 
           FROM " . TABLE_ORDERS . " 
          WHERE orders_id < $oID
       ORDER BY orders_id DESC 
          LIMIT 1"
    );
    if (!$get_prev->EOF) {
        $prev_oid = $get_prev->fields['orders_id'];
        $prev_button = '<input class="normal_button button" type="button" value="<<< ' . $prev_oid . '" onclick="window.location.href=\'' . zen_href_link(FILENAME_ORDERS, 'oID=' . $prev_oid . '&action=edit') . '\'">';
    } else {
        $prev_button = '<input class="normal_button button" type="button" value="' . BUTTON_TO_LIST . '" onclick="window.location.href=\'' . zen_href_link(FILENAME_ORDERS) . '\'">';
    }
    $prev_button .= PHP_EOL;

    $get_next = $db->Execute(
        "SELECT orders_id 
           FROM " . TABLE_ORDERS . " 
          WHERE orders_id > $oID
       ORDER BY orders_id ASC 
          LIMIT 1"
    );
    if (!$get_next->EOF) {
        $next_oid = $get_next->fields['orders_id'];
        $next_button = '<input class="normal_button button" type="button" value="' . $next_oid . ' >>>" onclick="window.location.href=\'' . zen_href_link(FILENAME_ORDERS, 'oID=' . $next_oid . '&action=edit') . '\'">';
    } else {
        $next_button = '<input class="normal_button button" type="button" value="' . BUTTON_TO_LIST . '" onclick="window.location.href=\'' . zen_href_link(FILENAME_ORDERS) . '\'">';
    }
    $next_button .= PHP_EOL
// END - Add Super Orders Order Navigation Functionality
?>
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
    <tr>
<!-- body_text //-->
        <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
            <tr>
                <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
<!-- BEGIN - Add Super Orders Order Navigation Functionality -->
                        <td class="pageHeading"> &nbsp; </td>
                        <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                        <td class="main" valign="middle"> &nbsp; </td>
                        <td align="center"><table border="0" cellspacing="3" cellpadding="0">
                            <tr>
                                <td class="main" align="center" valign="bottom"><?php echo $prev_button; ?></td>
                                <td class="smallText" align="center" valign="bottom">
<?php
                                    echo SELECT_ORDER_LIST . '<br />';
                                    echo zen_draw_form('input_oid', FILENAME_ORDERS, '', 'get', '', true);
                                    echo zen_draw_input_field('oID', '', 'size="6"');
                                    echo zen_draw_hidden_field('action', 'edit');
                                    echo '</form>';
?>
                                </td>
                                <td class="main" align="center" valign="bottom"><?php echo $next_button; ?></td>
                            </tr>
                        </table></td>
<!-- END - Add Super Orders Order Navigation Functionality -->
                        <td class="pageHeading" align="right"> &nbsp; </td>
                    </tr>
                </table></td>
            </tr>

            <tr>
                <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="pageHeading"><?php echo HEADING_TITLE; ?> #<?php echo $oID; ?></td>
                        <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                        <td class="pageHeading" align="right">
                            <a href="<?php echo zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('action'))); ?>"><?php echo zen_image_button('button_back.gif', IMAGE_BACK); ?></a>
                            <a href="<?php echo zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('oID', 'action')) . "oID=$oID&amp;action=edit"); ?>"><?php echo zen_image_button('button_details.gif', IMAGE_ORDER_DETAILS); ?></a>
                        </td>
                    </tr>
                </table></td>
            </tr>


<!-- Begin Addresses Block -->
            <tr>
                <td><?php echo zen_draw_form('edit_order', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action','paycc')) . 'action=update_order'); ?><table width="100%" border="0">
                    <tr>
                        <td><table width="100%" border="0">
                            <tr>
                                <td>&nbsp;</td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER; ?></strong></td>
                                <td>&nbsp;</td>
                                <td valign="top"><strong><?php echo ENTRY_BILLING_ADDRESS; ?></strong></td>
                                <td>&nbsp;</td>
                                <td valign="top"><strong><?php echo ENTRY_SHIPPING_ADDRESS; ?></strong></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td valign="top"><?php echo zen_image(DIR_WS_IMAGES . 'icon_customers.png', ENTRY_CUSTOMER); ?></td>
                                <td>&nbsp;</td>
                                <td valign="top"><?php echo zen_image(DIR_WS_IMAGES . 'icon_billing.png', ENTRY_BILLING_ADDRESS); ?></td>
                                <td>&nbsp;</td>
                                <td valign="top"><?php echo zen_image(DIR_WS_IMAGES . 'icon_shipping.png', ENTRY_SHIPPING_ADDRESS); ?></td>
                            </tr>

                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_name" size="45" value="<?php echo zen_html_quotes($order->customer['name']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_name" size="45" value="<?php echo zen_html_quotes($order->billing['name']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_name" size="45" value="<?php echo zen_html_quotes($order->delivery['name']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_company" size="45" value="<?php echo zen_html_quotes($order->customer['company']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_company" size="45" value="<?php echo zen_html_quotes($order->billing['company']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_company" size="45" value="<?php echo zen_html_quotes($order->delivery['company']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_street_address" size="45" value="<?php echo zen_html_quotes($order->customer['street_address']); ?>"></td>
                                <td valign="top"><strong> <?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_street_address" size="45" value="<?php echo zen_html_quotes($order->billing['street_address']); ?>"></td>
                                <td valign="top"><strong> <?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_street_address" size="45" value="<?php echo zen_html_quotes($order->delivery['street_address']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_suburb" size="45" value="<?php echo zen_html_quotes($order->customer['suburb']); ?>"></td>
                                <td valign="top"><strong> <?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_suburb" size="45" value="<?php echo zen_html_quotes($order->billing['suburb']); ?>"></td>
                                <td valign="top"><strong> <?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_suburb" size="45" value="<?php echo zen_html_quotes($order->delivery['suburb']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_city" size="45" value="<?php echo zen_html_quotes($order->customer['city']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_city" size="45" value="<?php echo zen_html_quotes($order->billing['city']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_city" size="45" value="<?php echo zen_html_quotes($order->delivery['city']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_state" size="45" value="<?php echo zen_html_quotes($order->customer['state']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_state" size="45" value="<?php echo zen_html_quotes($order->billing['state']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_state" size="45" value="<?php echo zen_html_quotes($order->delivery['state']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_customer_postcode" size="45" value="<?php echo zen_html_quotes($order->customer['postcode']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_billing_postcode" size="45" value="<?php echo zen_html_quotes($order->billing['postcode']); ?>"></td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</strong></td>
                                <td valign="top"><input name="update_delivery_postcode" size="45" value="<?php echo zen_html_quotes($order->delivery['postcode']); ?>"></td>
                            </tr>
                            <tr>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</strong></td>
                                <td valign="top">
<?php
    if (is_array($order->customer['country']) && isset($order->customer['country']['id'])) {
        echo zen_get_country_list('update_customer_country', $order->customer['country']['id']);
    } else {
        echo '<input name="update_customer_country" size="45" value="' . zen_html_quotes($order->customer['country']) . '">';
    } 
?>
                                </td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</strong></td>
                                <td valign="top">
<?php
    if (is_array($order->billing['country']) && isset($order->billing['country']['id'])) {
        echo zen_get_country_list('update_billing_country', $order->billing['country']['id']);
    } else {
        echo '<input name="update_billing_country" size="45" value="' . zen_html_quotes($order->billing['country']) . '">';
    } 
?>
                                </td>
                                <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</strong></td>
                                <td valign="top">
<?php
    if(is_array($order->delivery['country']) && array_key_exists('id', $order->delivery['country'])) {
        echo zen_get_country_list('update_delivery_country', $order->delivery['country']['id']);
    } else {
        echo '<input name="update_delivery_country" size="45" value="' . zen_html_quotes($order->delivery['country']) . '">';
    } 
?>
                                </td>
                            </tr>
<?php
    $additional_rows = '';
    $zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_ADDRESS_ROWS', $order, $additional_rows);
    echo $additional_rows;
?>

                        </table></td>
                    </tr>

<!-- End Addresses Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Phone/Email Block -->
                    <tr>
                        <td><table border="0" cellspacing="0" cellpadding="2">
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_TELEPHONE_NUMBER; ?></strong></td>
                                <td class="main"><input name="update_customer_telephone" size="15" value="<?php echo zen_html_quotes($order->customer['telephone']); ?>"></td>
                            </tr>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_EMAIL_ADDRESS; ?></strong></td>
                                <td class="main"><input name="update_customer_email_address" size="35" value="<?php echo zen_html_quotes($order->customer['email_address']); ?>"></td>
                            </tr>
                        </table></td>
                    </tr>
<!-- End Phone/Email Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Payment Block -->
                    <tr>
                        <td><table border="0" cellspacing="0" cellpadding="2">
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_PAYMENT_METHOD; ?></strong></td>
                                <td class="main"><input name="update_info_payment_method" size="20" value="<?php echo zen_html_quotes($order->info['payment_method']); ?>"><?php echo ($order->info['payment_method'] != 'Credit Card') ? ENTRY_UPDATE_TO_CC : ENTRY_UPDATE_TO_CK; ?></td>
                            </tr>
<?php 
    if (!empty($order->info['cc_type']) || !empty($order->info['cc_owner']) || $order->info['payment_method'] == "Credit Card" || !empty($order->info['cc_number'])) { 
?>
<!-- Begin Credit Card Info Block -->
                            <tr>
                                <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                            </tr>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_TYPE; ?></strong></td>
                                <td class="main"><input name="update_info_cc_type" size="10" value="<?php echo zen_html_quotes($order->info['cc_type']); ?>"></td>
                            </tr>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_OWNER; ?></strong></td>
                                <td class="main"><input name="update_info_cc_owner" size="20" value="<?php echo zen_html_quotes($order->info['cc_owner']); ?>"></td>
                            </tr>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></strong></td>
                                <td class="main"><input name="update_info_cc_number" size="20" value="<?php echo zen_html_quotes($order->info['cc_number']); ?>"></td>
                            </tr>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></strong></td>
                                <td class="main"><input name="update_info_cc_expires" size="4" value="<?php echo zen_html_quotes($order->info['cc_expires']); ?>"></td>
                            </tr>
<!-- End Credit Card Info Block -->
<?php 
    }

    if (isset($order->info['account_name']) || isset($order->info['account_number']) || isset($order->info['po_number'])) {
?>
                            <tr>
                                <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                            </tr>
<?php
        if (isset($order->info['account_name'])) {
?>
                            <tr>
                                <td class="main"><?php echo ENTRY_ACCOUNT_NAME; ?></td>
                                <td class="main"><?php echo zen_html_quotes($order->info['account_name']); ?></td>
                            </tr>
<?php
        }
        if (isset($order->info['account_number'])) {
?>
                            <tr>
                                <td class="main"><?php echo ENTRY_ACCOUNT_NUMBER; ?></td>
                                <td class="main"><?php echo zen_html_quotes($order->info['account_number']); ?></td>
                            </tr>
<?php
        }
        if (isset($order->info['po_number'])) {
?>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_PURCHASE_ORDER_NUMBER; ?></strong></td>
                                <td class="main"><?php echo zen_html_quotes($order->info['po_number']); ?></td>
                            </tr>
<?php
        }
    }
?>
                        </table></td>
                    </tr>
                    <tr>
                        <td valign="top">
<?php 
//-bof-20180323-lat9-GitHub#75, Multiple product-price calculation methods.
    $reset_totals_block = '<b>' . RESET_TOTALS . '</b>' . zen_draw_checkbox_field('reset_totals', '', (EO_TOTAL_RESET_DEFAULT == 'on'));
    $payment_calc_choice = '';
    if (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose') {
        $payment_calc_choice = '<b>' . PAYMENT_CALC_MANUAL . '</b>' . zen_draw_checkbox_field('payment_calc_manual', '', (EO_PRODUCT_PRICE_CALC_DEFAULT == 'Manual'));
    } elseif (EO_PRODUCT_PRICE_CALC_METHOD == 'Manual') {
        $payment_calc_choice = PRODUCT_PRICES_CALC_MANUAL;
    } else {
        $payment_calc_choice = PRODUCT_PRICES_CALC_AUTO;
    }
    echo zen_image_submit('button_update.gif', IMAGE_UPDATE, 'name="update_button"') . "&nbsp;$reset_totals_block&nbsp;$payment_calc_choice"; 
//-eof-20180323-lat9
?>
                        </td>
                    </tr>
<!-- End Payment Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Products Listing Block -->
                    <tr>
                        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
                            <tr class="dataTableHeadingRow">
                                <td class="dataTableHeadingContent" colspan="2" width="35%"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                                <td class="dataTableHeadingContent" width="35%"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
                                <td class="dataTableHeadingContent" align="right" width="10%"><?php echo TABLE_HEADING_TAX; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                                <td class="dataTableHeadingContent" align="right" width="10%"><?php echo TABLE_HEADING_UNIT_PRICE; ?></td>
                                <td class="dataTableHeadingContent" align="right" width="10%"><?php echo TABLE_HEADING_TOTAL_PRICE; ?></td>
                            </tr>
<!-- Begin Products Listings Block -->
<?php
    $orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);
    for ($i=0; $i<sizeof($order->products); $i++) {
        $orders_products_id = $orders_products_id_mapping[$i];
        $eo->eoLog (
            PHP_EOL . '============================================================' .
            PHP_EOL . '= Creating display of Order Product #' . $orders_products_id .
            PHP_EOL . '============================================================' .
            PHP_EOL . 'Product Details:' .
            PHP_EOL . $eo->eoFormatArray($order->products[$i]) . PHP_EOL
        ); 
?>
                            <tr class="dataTableRow">
                                <td class="dataTableContent" valign="top" align="left"><input name="update_products[<?php echo $orders_products_id; ?>][qty]" size="2" value="<?php echo zen_db_prepare_input($order->products[$i]['qty']); ?>" />&nbsp;&nbsp;&nbsp;&nbsp; X</td>
                                <td class="dataTableContent" valign="top" align="left"><input name="update_products[<?php echo $orders_products_id; ?>][name]" size="55" value="<?php echo zen_html_quotes($order->products[$i]['name']); ?>" />
<?php
        if (isset($order->products[$i]['attributes']) && count($order->products[$i]['attributes']) > 0) { 
?>
                                    <br/><nobr><small>&nbsp;<i><?php echo TEXT_ATTRIBUTES_ONE_TIME_CHARGE; ?>
                                    <input name="update_products[<?php echo $orders_products_id; ?>][onetime_charges]" size="8" value="<?php echo zen_db_prepare_input($order->products[$i]['onetime_charges']); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;</i></small></nobr><br/>
<?php
            $selected_attributes_id_mapping = eo_get_orders_products_options_id_mappings($oID, $orders_products_id);
            $attrs = eo_get_product_attributes_options($order->products[$i]['id']);
            $optionID = array_keys($attrs);
            for ($j=0; $j<sizeof($attrs); $j++) {
                $optionInfo = $attrs[$optionID[$j]];
                $orders_products_attributes_id = $selected_attributes_id_mapping[$optionID[$j]];

                $eo->eoLog (
                    PHP_EOL . 'Options ID #' . $optionID[$j] . PHP_EOL .
                    'Product Attribute: ' . PHP_EOL . $eo->eoFormatArray($orders_products_attributes_id) . PHP_EOL .
                    'Options Info:' . PHP_EOL . $eo->eoFormatArray($optionInfo)
                );

                switch($optionInfo['type']) {
                    case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
                    case PRODUCTS_OPTIONS_TYPE_RADIO:
                    case PRODUCTS_OPTIONS_TYPE_SELECT:
                    case PRODUCTS_OPTIONS_TYPE_SELECT_SBA:
                        echo '<label class="attribsSelect" for="opid-' .
                            $orders_products_id . '-oid-' . $optionID[$j] .
                            '">' . $optionInfo['name'] . '</label>';
                        $products_options_array = array();
                        $selected_attribute = null;
                        foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                            if (eo_is_selected_product_attribute_id($orders_products_attributes_id[0], $attributeId)) {
                                $selected_attribute = $attributeId;
                            }
                            $products_options_array[] = array(
                                'id' => $attributeId,
                                'text' => $attributeValue
                            );
                        }
                        if ($selected_attribute === null) {
                            $selected_attribute = $products_options_array[0]['id'];
                        }

                        echo zen_draw_pull_down_menu(
                            'update_products[' . $orders_products_id . '][attr][' .
                            $optionID[$j] . '][value]', $products_options_array,
                            $selected_attribute, 'id="opid-' . $orders_products_id .
                            '-oid-' . $optionID[$j] . '"'
                        ) . "<br />\n";
                        echo zen_draw_hidden_field(
                            'update_products[' . $orders_products_id . '][attr][' .
                            $optionID[$j] . '][type]', $optionInfo['type']
                        );
                        unset($products_options_array, $selected_attribute, $attributeId, $attributeValue);
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                        // First we need to see which items are checked.
                        // This also handles correctly forwarding $id_map.
                        $checked = array();
                        foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                            for ($k=0;$k<sizeof($orders_products_attributes_id);$k++) {
                                if (eo_is_selected_product_attribute_id($orders_products_attributes_id[$k], $attributeId)) {
                                    $checked[$attributeId] = $orders_products_attributes_id[$k];
                                }
                            }
                        }

                        // Now display the options
                        echo '<div class="attribsCheckboxGroup"><div class="attribsCheckboxName">' . $optionInfo['name'] . '</div>';
                        foreach($optionInfo['options'] as $attributeId => $attributeValue) {
                            echo zen_draw_checkbox_field(
                                'update_products[' . $orders_products_id . '][attr][' .
                                $optionID[$j] . '][value][' . $attributeId . ']',
                                $attributeId, array_key_exists($attributeId, $checked),
                                null, 'id="opid-' . $orders_products_id . '-oid-' .
                                $optionID[$j] .    '-' . $attributeId . '"'
                            ) . '<label class="attribsCheckbox" for="opid-' .
                                $orders_products_id . '-oid-' .
                                $optionID[$j] .    '-' . $attributeId . '">' .
                            $attributeValue . '</label><br />' . "\n";
                        }
                        echo zen_draw_hidden_field(
                            'update_products[' . $orders_products_id . '][attr][' .
                            $optionID[$j] . '][type]', $optionInfo['type']
                        ) . '</div>';
                        unset($checked, $attributeId, $attributeValue);
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_TEXT:
                        $text = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_shift(array_keys($optionInfo['options'])));
                        if ($text === null) {
                            $text = '';
                        }
                        $text = zen_html_quotes($text);
                        echo '<label class="attribsInput" for="opid-' .
                            $orders_products_id . '-oid-' . $optionID[$j] .
                            '">' . $optionInfo['name'] . '</label>';
                        if ($optionInfo['rows'] > 1 ) {
                            echo '<textarea class="attribsTextarea" name="update_products[' .
                                $orders_products_id . '][attr][' . $optionID[$j] .
                                '][value]" rows="' . $optionInfo['rows'] .
                                '" cols="' . $optionInfo['size'] . '" id="opid-' .
                                $orders_products_id . '-oid-' . $optionID[$j] .
                                '" >' . $text . '</textarea>' . "\n";
                        } else {
                            echo '<input type="text" name="update_products[' .
                                $orders_products_id . '][attr][' . $optionID[$j] .
                                '][value]" size="' . $optionInfo['size'] . '" maxlength="' .
                                $optionInfo['size'] . '" value="' . $text .
                                '" id="opid-' . $orders_products_id . '-oid-' .
                                $optionID[$j] .    '" /><br />' . "\n";
                        }
                        echo zen_draw_hidden_field(
                            'update_products[' . $orders_products_id . '][attr][' .
                            $optionID[$j] . '][type]', $optionInfo['type']
                        );
                        unset($text);
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_FILE:
                        $value = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_shift(array_keys($optionInfo['options'])));
                        echo '<span class="attribsFile">' . $optionInfo['name'] .
                            ': ' . (zen_not_null($value) ? $value : TEXT_ATTRIBUTES_UPLOAD_NONE) .
                            '</span><br />';
                        if (zen_not_null($value)) {
                            echo zen_draw_hidden_field(
                                'update_products[' . $orders_products_id . '][attr][' .
                                $optionID[$j] . '][value]', $value
                            );
                            echo zen_draw_hidden_field(
                                'update_products[' . $orders_products_id . '][attr][' .
                                $optionID[$j] . '][type]', $optionInfo['type']
                            );
                        }
                        unset($value);
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_READONLY:
                    default:
                        $optionValue = array_shift($optionInfo['options']);
                        echo '<input type="hidden" name="update_products[' .
                            $orders_products_id . '][attr][' . $optionID[$j] . '][value]" value="' .
                            $optionValue . '" /><span class="attribsRO">' .
                            $optionInfo['name'] . ': ' . $optionValue . '</span><br />';
                        echo zen_draw_hidden_field(
                            'update_products[' . $orders_products_id . '][attr][' .
                            $optionID[$j] . '][type]', $optionInfo['type']
                        );
                        unset($optionValue);
                        break;
                }
            }
            unset($optionID, $optionInfo);
        } 
?>
                                </td>
                                <td class="dataTableContent" valign="top"><input name="update_products[<?php echo $orders_products_id; ?>][model]" size="55" value="<?php echo $order->products[$i]['model']; ?>" /></td>
                                <td class="dataTableContent" align="right" valign="top"><input class="amount" name="update_products[<?php echo $orders_products_id; ?>][tax]" size="3" value="<?php echo zen_display_tax_value($order->products[$i]['tax']); ?>" />&nbsp;%</td>
                                <td class="dataTableContent" align="right" valign="top"><input class="amount" name="update_products[<?php echo $orders_products_id; ?>][final_price]" size="5" value="<?php echo number_format($order->products[$i]['final_price'], 2, '.', ''); ?>" /></td>
                                <td class="dataTableContent" align="right" valign="top"><?php echo $currencies->format($order->products[$i]['final_price'] * $order->products[$i]['qty'] + $order->products[$i]['onetime_charges'], true, $order->info['currency'], $order->info['currency_value']); ?></td>
                            </tr>
<?php
    } 
?>
<!-- End Products Listings Block -->

<!-- Begin Order Total Block -->
                            <tr>
                                <td align="right" colspan="6"><table border="0" cellspacing="0" cellpadding="2" width="100%">
                                    <tr>
                                        <td valign="top"><br /><?php echo '<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('oID', 'action')) . 'oID=' . $oID . '&amp;action=add_prdct') . '">' . zen_image_button('button_add_product.gif', TEXT_ADD_NEW_PRODUCT) . '</a>'; ?></td>
                                        <td align="right"><table border="0" cellspacing="0" cellpadding="2">
<?php
    // Iterate over the order totals.
    for ($i=0, $index=0, $n=count($order->totals); $i<$n; $i++, $index++) { 
?>
                                            <tr>
<?php
        $total = $order->totals[$i];
        $order_total_info = eo_get_order_total_by_order((int)$oID, $total['class']);
        $details = array_shift ($order_total_info);
        switch($total['class']) {
            // Automatically generated fields, those should never be included
            case 'ot_subtotal':
            case 'ot_total':
            case 'ot_tax':
            case 'ot_local_sales_taxes': 
?>
                                                <td align="right">&nbsp;</td>
                                                <td class="main" align="right"><strong><?php echo $total['title']; ?></strong></td>
                                                <td class="main" align="right"><strong><?php echo $total['text']; ?></strong></td>
<?php
                $index--;
                break;

            // Include these in the update but do not allow them to be changed
            case 'ot_group_pricing':
            case 'ot_cod_fee':
            case 'ot_loworderfee': 
?>
                                                <td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
                                                <td align="right" class="main"><?php echo strip_tags($total['title']) . zen_draw_hidden_field('update_total[' . $index . '][title]', strip_tags($total['title'])); ?></td>
                                                <td align="right" class="main"><?php echo $total['text'] . zen_draw_hidden_field('update_total[' . $index . '][value]', $details['value']); ?></td>
<?php
                break;

            // Allow changing the title / text, but not the value. Typically used
            // for order total modules which handle the value based upon another condition
            case 'ot_coupon': 
?>
                                                <td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?></td>
                                                <td align="right" class="main"><?php echo $total['text'] . zen_draw_hidden_field('update_total[' . $index . '][value]', $details['value']); ?></td>
<?php
                break;

            case 'ot_shipping': 
?>
                                                <td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']) . zen_draw_pull_down_menu('update_total[' . $index . '][shipping_module]', eo_get_available_shipping_modules(), $order->info['shipping_module_code']); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][value]', $details['value'], 'class="amount" size="6"'); ?></td>
<?php
                break;

            case 'ot_gv':
            case 'ot_voucher': 
?>
                                                <td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?></td>
                                                <td align="right" class="smallText">
<?php                 
                if ($details['value'] > 0) {
                    $details['value'] *= -1;
                }
                echo '<input class="amount" size="6" name="update_total[' . $index . '][value]" value="' . $details['value'], '" />'; 
?>
                                                </td>
<?php
                break;

            default: 
?>
                                                <td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][value]', $details['value'], 'class="amount" size="6"'); ?></td>
<?php
                break;
        } 
?>
                                            </tr>
<?php
    }
    
    if (count(eo_get_available_order_totals_class_values($oID)) > 0) { 
?>
                                            <tr>
                                                <td align="right" class="smallText"><?php echo TEXT_ADD_ORDER_TOTAL . zen_draw_pull_down_menu('update_total[' . $index . '][code]', eo_get_available_order_totals_class_values($oID), '', 'id="update_total_code"'); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][title]', '', 'class="amount" style="width: 100%"'); ?></td>
                                                <td align="right" class="smallText"><?php echo zen_draw_input_field('update_total[' . $index . '][value]', '', 'class="amount" size="6"'); ?></td>
                                            </tr>
                                            <tr>
                                                <td align="left" colspan="3" class="smallText" id="update_total_shipping" style="display: none"><?php echo TEXT_CHOOSE_SHIPPING_MODULE . zen_draw_pull_down_menu('update_total[' . $index . '][shipping_module]', eo_get_available_shipping_modules()); ?></td>
                                            </tr>
<?php
    }
    unset($i, $index, $n, $total, $details); 
?>
                                        </table></td>
                                    </tr>
                                </table></td>
                            </tr>
<!-- End Order Total Block -->

                        </table></td>
                    </tr>

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <tr>
                        <td class="main"><strong><?php echo zen_image(DIR_WS_IMAGES . 'icon_comment_add.png', TABLE_HEADING_STATUS_HISTORY) . '&nbsp;' . TABLE_HEADING_STATUS_HISTORY; ?></strong></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><table border="1" cellspacing="0" cellpadding="5" width="60%">
<?php if (defined ('TY_TRACKER') && TY_TRACKER == 'True') { ?>
          <tr class="dataTableHeadingRow">
            <td class="dataTableHeadingContent smallText" valign="top"  width="15%"><strong><?php echo TABLE_HEADING_DATE_ADDED; ?></strong></td>
            <td class="dataTableHeadingContent smallText" align="center" valign="top" width="12%"><strong><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></strong></td>
            <td class="dataTableHeadingContent smallText" valign="top" width="10%"><strong><?php echo TABLE_HEADING_STATUS; ?></strong></td>
<!-- TY TRACKER 4 BEGIN, DISPLAY TRACKING ID IN COMMENTS TABLE ------------------------------->
        <td class="dataTableHeadingContent smallText" valign="top" width="23%"><strong><?php echo TABLE_HEADING_TRACKING_ID; ?></strong></td>
<!-- END TY TRACKER 4 END, DISPLAY TRACKING ID IN COMMENTS TABLE ------------------------------------------------------------>
            <td class="dataTableHeadingContent smallText" valign="top" width="40%"><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
          </tr>
<?php
// TY TRACKER 5 BEGIN, INCLUDE DATABASE FIELDS ------------------------------
    $orders_history = $db->Execute("select orders_status_id, date_added, customer_notified, track_id1, track_id2, track_id3, track_id4, track_id5, comments
                                    from " . TABLE_ORDERS_STATUS_HISTORY . "
                                    where orders_id = '" . zen_db_input($oID) . "'
                                    order by date_added");
// END TY TRACKER 5 END, INCLUDE DATABASE FIELDS  -----------------------------------------------------------
    if ($orders_history->RecordCount() > 0) {
      while (!$orders_history->EOF) {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" valign="top">' . zen_datetime_short($orders_history->fields['date_added']) . '</td>' . "\n" .
             '            <td class="smallText" align="center">';
        if ($orders_history->fields['customer_notified'] == '1') {
          echo zen_image(DIR_WS_ICONS . 'tick.gif', TEXT_YES) . "</td>\n";
        } else if ($orders_history->fields['customer_notified'] == '-1') {
          echo zen_image(DIR_WS_ICONS . 'locked.gif', TEXT_HIDDEN) . "</td>\n";
        } else {
          echo zen_image(DIR_WS_ICONS . 'unlocked.gif', TEXT_VISIBLE) . "</td>\n";
        }
        echo '            <td class="smallText" valign="top">' . $orders_status_array[$orders_history->fields['orders_status_id']] . '</td>' . "\n";
// TY TRACKER 6 BEGIN, DEFINE TRACKING INFORMATION ON SUPER_ORDERS.PHP FILE ----------------
        $display_track_id = '&nbsp;';
    $display_track_id .= (empty($orders_history->fields['track_id1']) ? '' : CARRIER_NAME_1 . ": <a href=" . CARRIER_LINK_1 . nl2br(zen_output_string_protected($orders_history->fields['track_id1'])) . ' target="_blank">' . nl2br(zen_output_string_protected($orders_history->fields['track_id1'])) . "</a>&nbsp;" );
    $display_track_id .= (empty($orders_history->fields['track_id2']) ? '' : CARRIER_NAME_2 . ": <a href=" . CARRIER_LINK_2 . nl2br(zen_output_string_protected($orders_history->fields['track_id2'])) . ' target="_blank">' . nl2br(zen_output_string_protected($orders_history->fields['track_id2'])) . "</a>&nbsp;" );
    $display_track_id .= (empty($orders_history->fields['track_id3']) ? '' : CARRIER_NAME_3 . ": <a href=" . CARRIER_LINK_3 . nl2br(zen_output_string_protected($orders_history->fields['track_id3'])) . ' target="_blank">' . nl2br(zen_output_string_protected($orders_history->fields['track_id3'])) . "</a>&nbsp;" );
    $display_track_id .= (empty($orders_history->fields['track_id4']) ? '' : CARRIER_NAME_4 . ": <a href=" . CARRIER_LINK_4 . nl2br(zen_output_string_protected($orders_history->fields['track_id4'])) . ' target="_blank">' . nl2br(zen_output_string_protected($orders_history->fields['track_id4'])) . "</a>&nbsp;" );
    $display_track_id .= (empty($orders_history->fields['track_id5']) ? '' : CARRIER_NAME_5 . ": <a href=" . CARRIER_LINK_5 . nl2br(zen_output_string_protected($orders_history->fields['track_id5'])) . ' target="_blank">' . nl2br(zen_output_string_protected($orders_history->fields['track_id5'])) . "</a>&nbsp;" );
        echo '            <td class="smallText" align="left" valign="top">' . $display_track_id . '</td>' . "\n";
// END TY TRACKER 65 END, DEFINE TRACKING INFORMATION ON SUPER_ORDERS.PHP FILE -------------------------------------------------------------------

        echo '            <td class="smallText" valign="top">' . nl2br(zen_html_quotes($orders_history->fields['comments'])) . '&nbsp;</td>' . "\n" .
             '          </tr>' . "\n";
        $orders_history->MoveNext();
      }
    } else {
        echo '          <tr>' . "\n" .
             '            <td class="smallText" colspan="5">' . TEXT_NO_ORDER_HISTORY . '</td>' . "\n" .
             '          </tr>' . "\n";
    }
?>
<?php } else { ?>

                    <tr class="dataTableHeadingRow">
                        <td class="dataTableHeadingContent smallText" valign="top"  width="21%"><strong><?php echo TABLE_HEADING_DATE_ADDED; ?></strong></td>
                        <td class="dataTableHeadingContent smallText" align="center" valign="top" width="18%"><strong><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></strong></td>
                        <td class="dataTableHeadingContent smallText" valign="top" width="17%"><strong><?php echo TABLE_HEADING_STATUS; ?></strong></td>
                        <td class="dataTableHeadingContent smallText" valign="top" width="44%"><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
                    </tr>
<?php
        $orders_history = $db->Execute(
            "SELECT orders_status_id, date_added, customer_notified, comments
               FROM " . TABLE_ORDERS_STATUS_HISTORY . "
              WHERE orders_id = $oID
           ORDER BY date_added"
        );
        if (!$orders_history->EOF) {
            while (!$orders_history->EOF) {
                switch ($orders_history->fields['customer_notified']) {
                    case '1':
                        $status_icon = 'tick.gif';
                        $icon_alt_text = TEXT_YES;
                        break;
                     case '-1':
                        $status_icon = 'locked.gif';
                        $icon_alt_text = TEXT_HIDDEN;
                        break;
                      default:
                        $status_icon = 'unlocked.gif';
                        $icon_alt_text = TEXT_VISIBLE;
                        break;
                }
                $icon_image = zen_image(DIR_WS_ICONS . $status_icon, $icon_alt_text);
?>
                    <tr>
                        <td class="smallText" valign="top"><?php echo zen_datetime_short($orders_history->fields['date_added']); ?></td>
                        <td class="smallText" align="center"><?php echo $icon_image; ?></td>
                        <td class="smallText" valign="top"><?php echo $orders_status_array[$orders_history->fields['orders_status_id']]; ?></td>
                        <td class="smallText" valign="top"><?php echo nl2br(zen_db_output($orders_history->fields['comments'])); ?>&nbsp;</td>
                    </tr>
<?php
                $orders_history->MoveNext();
            }
        } else {
?>
                    <tr>
                        <td class="smallText colspan="4"><?php echo TEXT_NO_ORDER_HISTORY; ?></td>
                    </tr>
<?php
        }
    } 
?>
                        </table></td>
                    </tr>

                    <tr>
                        <td class="main"><br><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
                    </tr>
                    
                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><?php echo zen_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
                    </tr>

<!-- TY TRACKER 7 BEGIN, ENTER TRACKING INFORMATION -->
<?php if (defined ('TY_TRACKER') && TY_TRACKER == 'True') { ?>
    <tr>
        <td class="main">
            <table border="0" cellpadding="3" cellspacing="0">
                <tr>
                    <td class="main"><strong><?php echo zen_image(DIR_WS_IMAGES . 'icon_track_add.png', ENTRY_ADD_TRACK) . '&nbsp;' . ENTRY_ADD_TRACK; ?></strong></td>
                </tr>
                <tr valign="top">
                    <td width="400">
                        <table border="1" cellpadding="3" cellspacing="0" width="100%">
                            <tr class="dataTableHeadingRow">
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_CARRIER_NAME; ?></strong></td>
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_TRACKING_ID; ?></strong></td>
                            </tr>
                            <?php for($i=1;$i<=5;$i++) {
                                if(constant('CARRIER_STATUS_' . $i) == 'True') { ?>
                            <tr>
                            <td><?php echo constant('CARRIER_NAME_' . $i); ?></td><td valign="top"><?php echo zen_draw_input_field('track_id[' . $i . ']', '', 'size="50"'); ?></td>
                            </tr>
                            <?php } } ?>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
<?php } ?>
<!-- TY TRACKER 7 END, ENTER TRACKING INFORMATION -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><strong><?php echo ENTRY_CURRENT_STATUS; ?><?php echo $orders_status_array[$orders_history->fields['orders_status_id']] ;?></strong></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><strong><?php echo ENTRY_STATUS; ?></strong> <?php echo zen_draw_pull_down_menu('status', $orders_statuses, $orders_history->fields['orders_status_id']); ?></td>
                    </tr>
                    
                    <tr>
                        <td><table border="0" cellspacing="0" cellpadding="2">
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_NOTIFY_CUSTOMER; ?></strong> [<?php echo zen_draw_radio_field('notify', '1', true) . '-' . TEXT_EMAIL . ' ' . zen_draw_radio_field('notify', '0', FALSE) . '-' . TEXT_NOEMAIL . ' ' . zen_draw_radio_field('notify', '-1', FALSE) . '-' . TEXT_HIDE; ?>]&nbsp;&nbsp;&nbsp;</td>
                                <td class="main"><strong><?php echo ENTRY_NOTIFY_COMMENTS; ?></strong> <?php echo zen_draw_checkbox_field('notify_comments', '', true); ?></td>
                            </tr>
                        </table></td>
                    </tr>

                    <tr>
                        <td valign="top"><?php echo zen_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
                    </tr>
                </table></form></td>
            </tr>
        </table></td>
    </tr>
</table>
<?php
}

if ($action == "add_prdct") { 
    $order_parms = zen_get_all_get_params(array('oID', 'action', 'resend')) . "oID=$oID&amp;action=edit";
?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
    <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE_ADD_PRODUCT; ?> #<?php echo $oID; ?></td>
                <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                <td class="pageHeading" align="right">
                    <a href="<?php echo zen_href_link(FILENAME_EDIT_ORDERS, $order_parms); ?>"><?php echo zen_image_button('button_back.gif', IMAGE_EDIT); ?></a>
                    <a href="<?php echo zen_href_link(FILENAME_ORDERS, $order_parms); ?>"><?php echo zen_image_button('button_details.gif', IMAGE_ORDER_DETAILS); ?></a>
                </td>
            </tr>
        </table></td>
    </tr>

<?php
    // Set Defaults
    if (!isset($add_product_categories_id)) {
        $add_product_categories_id = .5;
    }

    if (!isset($add_product_products_id)) {
        $add_product_products_id = 0;
    }

    // Step 1: Choose Category
    if ($add_product_categories_id == .5) {
        // Handle initial population of categories
        $categoriesarr = zen_get_category_tree();
        $catcount = count($categoriesarr);
        $texttempcat1 = $categoriesarr[0]['text'];
        $idtempcat1 = $categoriesarr[0]['id'];
        $catcount++;
        for ($i=1; $i<$catcount; $i++) {
            $texttempcat2 = $categoriesarr[$i]['text'];
            $idtempcat2 = $categoriesarr[$i]['id'];
            $categoriesarr[$i]['id'] = $idtempcat1;
            $categoriesarr[$i]['text'] = $texttempcat1;
            $texttempcat1 = $texttempcat2;
            $idtempcat1 = $idtempcat2;
        }


        $categoriesarr[0]['text'] = "Choose Category";
        $categoriesarr[0]['id'] = .5;


        $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', $categoriesarr, $current_category_id, 'onChange="this.form.submit();"');
        $categoryselectoutput = str_replace('<option value="0" SELECTED>','<option value="0">',$categoryselectoutput);
        $categoryselectoutput = str_replace('<option value=".5">','<option value=".5" SELECTED>',$categoryselectoutput);
    } else {

        // Add the category selection. Selecting a category will override the search
        $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', zen_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
    }
?> 
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow">
                <td class="dataTableContent" align="right"><strong><?php echo ADDPRODUCT_TEXT_STEP1; ?></strong></td>
                <td class="dataTableContent" valign="top">
<?php               
    echo 
        ' ' . $categoryselectoutput . 
        ' OR ' .
        HEADING_TITLE_SEARCH_DETAIL . ' ' . 
        zen_draw_input_field('search', (isset($_POST['search']) && $add_product_categories_id <= 1) ? $_POST['search'] : '', 'onclick="this.form.add_product_categories_id.value=0;"') . zen_hide_session_id().
        zen_draw_hidden_field('step', '2');
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
            
    <tr>
        <td>&nbsp;</td>
    </tr>
<?php
    // Step 2: Choose Product
    if ($step > 1 && ($add_product_categories_id != .5 || zen_not_null($_POST['search']))) {
        $query =
            "SELECT p.products_id, p.products_model, pd.products_name, p.products_status
               FROM " . TABLE_PRODUCTS . " p
                    INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd
                        ON pd.products_id = p.products_id
                       AND pd.language_id = " . (int)$_SESSION['languages_id'];

        if ($add_product_categories_id >= 1) {
            $query .=
                " LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
                    ON ptc.products_id = p.products_id
                 WHERE ptc.categories_id=" . (int)$add_product_categories_id . "
                 ORDER BY p.products_id";
        } elseif (zen_not_null($_POST['search'])) {
            // Handle case where a product search was entered
            $keywords = zen_db_input(zen_db_prepare_input($_POST['search']));

            $query .=
                " WHERE (pd.products_name LIKE '%$keywords%'
                    OR pd.products_description LIKE '%$keywords%'
                    OR p.products_id = " . (int)$keywords . "
                    OR p.products_model LIKE '%$keywords%')
              ORDER BY p.products_id";
        }
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow">
                <td class="dataTableContent" align="right"><strong><?php echo ADDPRODUCT_TEXT_STEP2; ?></strong></td>
                <td class="dataTableContent" valign="top">
                    <select name="add_product_products_id" onchange="this.form.submit();">
<?php
        $ProductOptions = '<option value="0">' .  ADDPRODUCT_TEXT_SELECT_PRODUCT . '</option>' . PHP_EOL;
        $result = $db->Execute($query);
        while (!$result->EOF) {
            $ProductOptions .= 
                '<option value="' . $result->fields['products_id'] . '">' . 
                    $result->fields['products_name'] .
                    ' [' . $result->fields['products_model'] . '] ' . ($result->fields['products_status'] == 0 ? ' (OOS)' : '') . 
                '</option>' . PHP_EOL;
            $result->MoveNext();
        }
        $ProductOptions = str_replace(
            'value="' . $add_product_products_id . '"',
            'value="' . $add_product_products_id . '" selected',
            $ProductOptions
        );
        echo $ProductOptions;
        unset($ProductOptions);
?>
                    </select>
<?php
        echo 
            zen_draw_hidden_field('add_product_categories_id', $add_product_categories_id) .
            zen_draw_hidden_field('search', $_POST['search']) .
            zen_draw_hidden_field('step', 3);
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
<?php
    }

    // Step 3: Choose Options
    if ($step > 2 && $add_product_products_id > 0) {
        // Skip to Step 4 if no Options
        if (!zen_has_product_attributes($add_product_products_id)) {
            $step = 4;
?>
    <tr class="dataTableRow">
        <td class="dataTableContent"><strong><?php echo ADDPRODUCT_TEXT_STEP3; ?></strong> <i><?php echo ADDPRODUCT_TEXT_OPTIONS_NOTEXIST; ?></i></td>
    </tr>
<?php
        } else {
            $attrs = eo_get_product_attributes_options($add_product_products_id);
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow">
                <td class="dataTableContent" align="right" valign="top"><strong><?php echo ADDPRODUCT_TEXT_STEP3; ?></strong></td>
                <td class="dataTableContent" valign="top">
<?php
            foreach ($attrs as $optionID => $optionInfo) {
                $option_name = $optionInfo['name'];
                $attrib_id = "attrib-$optionID";
                switch ($optionInfo['type']) {
                    case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
                    case PRODUCTS_OPTIONS_TYPE_RADIO:
                    case PRODUCTS_OPTIONS_TYPE_SELECT:       
?>
                    <label class="attribsSelect" for="<?php echo $attrib_id; ?>"><?php echo $option_name; ?></label>
<?php
                        $products_options_array = array();
                        foreach($optionInfo['options'] as $attributeId => $attributeValue) {
                            $products_options_array[] = array(
                                'id' => $attributeId,
                                'text' => $attributeValue
                            );
                        }
                        $selected_attribute = $products_options_array[0]['id'];
                        if (isset($_POST['id'][$optionID])) {
                            $selected_attribute = $_POST['id'][$optionID]['value'];
                        }
                        echo zen_draw_pull_down_menu('id[' . $optionID . '][value]', $products_options_array, $selected_attribute, 'id="' . $attrib_id . '"') . '<br />' . PHP_EOL;
                        unset($products_options_array, $selected_attribute, $attributeId, $attributeValue);
                        echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
?>
                    <div class="attribsCheckboxGroup">
                        <div class="attribsCheckboxName"><?php echo $option_name; ?></div>
<?php
                        foreach($optionInfo['options'] as $attributeId => $attributeValue) {
                            $checked = isset($_POST['id'][$optionID]['value'][$attributeId]);
                            echo zen_draw_checkbox_field('id[' . $optionID . '][value][' . $attributeId . ']', $attributeId, $checked, null, 'id="' . $attrib_id . '-' . $attributeId . '"') . '<label class="attribsCheckbox" for="' . $attrib_id . '-' . $attributeId . '">' . $attributeValue . '</label><br />' . PHP_EOL;
                        }
                        unset($checked, $attributeId, $attributeValue);
                        echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
?>
                    </div>
<?php
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_TEXT:
                        $text = (isset($_POST['id'][$optionID]['value']) ? $_POST['id'][$optionID]['value'] : '');
                        $text = zen_html_quotes($text);
?>
                    <label class="attribsInput" for="<?php echo $attrib_id; ?>"><?php echo $option_name; ?></label>
<?php
                        $field_name = 'id[' . $optionID . '][value]';
                        $field_size = $optionInfo['size'];
                        if ($optionInfo['rows'] > 1 ) {
                            echo zen_draw_textarea_field($field_name, 'hard', $field_size, $optionInfo['rows'], $text, 'class="attribsTextarea" id="' . $attrib_id . '"') . '<br />' . PHP_EOL;
                        } else {
                            echo zen_draw_input_field($field_name, $text, 'size="' . $field_size . '" maxlength="' . $field_size . '" id="' . $attrib_id . '"') . '<br />' . PHP_EOL;
                        }
                        echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_FILE:
?>
                    <span class="attribsFile"><?php echo $option_name . ': FILE UPLOAD NOT SUPPORTED'; ?></span><br />
<?php
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_READONLY:
                    default:
?>
                    <span class="attribsRO"><?php echo $option_name . ': ' . $optionValue; ?></span><br />
<?php
                        $optionValue = array_shift($optionInfo['options']);
                        echo 
                            zen_draw_hidden_field('id[' . $optionID . '][value]', $optionValue) .
                            zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']) . PHP_EOL;
                        unset($optionValue);
                        break;
                }
            }
?>
                </td>
                <td class="dataTableContent" align="center" valign="bottom">
                    <input type="submit" value="<?php echo ADDPRODUCT_TEXT_OPTIONS_CONFIRM; ?>" />
<?php
            echo zen_draw_hidden_field('add_product_categories_id', $add_product_categories_id) .
                zen_draw_hidden_field('add_product_products_id', $add_product_products_id) .
                zen_draw_hidden_field('search', $_POST['search']) .
		zen_draw_hidden_field('step', '4');
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
<?php
        }
?>
    <tr>
        <td>&nbsp;</td>
    </tr>
<?php
    }

    // Step 4: Confirm
    if ($step > 3) {
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow">
                <td class="dataTableContent" align="right"><strong><?php echo ADDPRODUCT_TEXT_STEP4; ?></strong></td>
                <td class="dataTableContent" valign="top"><?php echo ADDPRODUCT_TEXT_CONFIRM_QUANTITY . 
                    zen_draw_input_field('add_product_quantity', 1, 'size="2"') .
                    '&nbsp;&nbsp;&nbsp;&nbsp;' .
                    zen_draw_checkbox_field('applyspecialstoprice', '1', true) . ADDPRODUCT_SPECIALS_SALES_PRICE; ?></td>
                 <td class="dataTableContent" align="center">
                    <input type="submit" value="<?php echo ADDPRODUCT_TEXT_CONFIRM_ADDNOW; ?>" />
<?php
        if (isset($_POST['id'])) {
            foreach ($_POST['id'] as $id => $value) {
                if (is_array($value)) {
                    foreach ($value as $id2 => $value2) {
                        if (is_array($value2)) {
                            foreach ($value2 as $id3 => $value3) {
                                echo zen_draw_hidden_field('id[' . $id . '][' . $id2 . '][' . $id3 . ']', zen_html_quotes($value3));
                            }
                        } else {
                            echo zen_draw_hidden_field('id[' . $id . '][' . $id2 . ']', zen_html_quotes($value2));
                        }
                    }
                } else {
                    echo zen_draw_hidden_field('id[' . $id . ']', zen_html_quotes($value));
                }
            }
        }
        echo zen_draw_hidden_field('add_product_categories_id', $add_product_categories_id) .
            zen_draw_hidden_field('add_product_products_id', $add_product_products_id) .
            zen_draw_hidden_field('step', '5');
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
<?php
    }
?>
</table>
<?php
}
?>
<!-- body_text_eof //-->
<script type="text/javascript">
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
