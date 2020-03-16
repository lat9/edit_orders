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

$orders_statuses = array();
$orders_status_array = array();
$order_by_field = ($sniffer->field_exists(TABLE_ORDERS_STATUS, 'sort_order')) ? 'sort_order' : 'orders_status_id';
$orders_status_query = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . (int)$_SESSION['languages_id'] . "
  ORDER BY $order_by_field ASC"
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

$action = (!empty($_GET['action']) ? $_GET['action'] : 'edit');
$eo->eoLog(PHP_EOL . date('Y-m-d H:i:s') . ", Edit Orders entered (" . EO_VERSION . ") action ($action)" . PHP_EOL . 'Enabled Order Totals: ' . MODULE_ORDER_TOTAL_INSTALLED, 1);
$zco_notifier->notify('EDIT_ORDERS_START_ACTION_PROCESSING');
switch ($action) {
    // Update Order
    case 'update_order':
        $comments = zen_db_prepare_input($_POST['comments']);
        $status = (int)$_POST['status'];
        if ($status < 1) {
            break;
        }

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

            // If the number is not already obfuscated, we use the same method
            // as the authorize.net module to obfuscate the entered CC number
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
                if (defined($carrier_constant) && constant($carrier_constant) == 'True' && !empty($track)) {
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
        if ($check_status->fields['orders_status'] != $status || !empty($track_id) || !empty($comments)) {
            $customer_notified = '0';
            if (isset($_POST['notify']) && $_POST['notify'] == '1') {
                $notify_comments = '';
                if (isset($_POST['notify_comments']) && ($_POST['notify_comments'] == 'on')) {
                    if (!empty($comments)) {
                        $notify_comments = EMAIL_TEXT_COMMENTS_UPDATE . $comments . PHP_EOL . PHP_EOL;
                    }
                    // BEGIN TY TRACKER 2 - EMAIL TRACKING INFORMATION
                    if (!empty($track_id)) {
                        $notify_comments = EMAIL_TEXT_COMMENTS_TRACKING_UPDATE . PHP_EOL . PHP_EOL;
                        $comment = EMAIL_TEXT_COMMENTS_TRACKING_UPDATE;
                    }
                    foreach ($track_id as $id => $track) {
                        if (!empty($track) && constant('CARRIER_STATUS_' . $id) == 'True') {
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
                $customer_notified = -1;
            }

            $sql_data_array = array(
                'orders_id' => (int)$oID,
                'orders_status_id' => $status,
                'date_added' => 'now()',
                'customer_notified' => $customer_notified,
                'comments' => $comments,
            );

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
                $order->totals = array();
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

            foreach ($_POST['update_products'] as $orders_products_id => $product_update) {
                $product_update['qty'] = (float)$product_update['qty'];
                $product_update['name'] = $product_update['name'];

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
                        if (EO_PRODUCT_PRICE_CALC_METHOD == 'Auto' || EO_PRODUCT_PRICE_CALC_METHOD == 'AutoSpecials' || (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose' && $_POST['payment_calc_method'] != 3)) {
                            $price_calc_method = EO_MESSAGE_PRICING_AUTO;
                            if (EO_PRODUCT_PRICE_CALC_METHOD == 'AutoSpecials' || (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose' && $_POST['payment_calc_method'] == 1)) {
                                $price_calc_method = EO_MESSAGE_PRICING_AUTOSPECIALS;
                            }
                            $new_product = array_merge($product_update, $new_product);
                        } else {
                            $price_calc_method = EO_MESSAGE_PRICING_MANUAL;
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
                $eo->eoRecordStatusHistory($oID, EO_MESSAGE_ORDER_UPDATED . $price_calc_method);
                
                // -----
                // Need to force update the tax field if the tax is zero.
                // This runs after the shipping tax is added by the above update
                //
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

            foreach ($_POST['update_total'] as $order_total) {
                $order_total['value'] = (float)$order_total['value'];
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
            $order = $eo->getOrderInfo($action);
           
            // -----
            // Initialize the shipping cost, tax-rate and tax-value.
            //
            $eo->eoInitializeShipping($oID, $action);
            
            // Check qty field
            $add_max = zen_get_products_quantity_order_max($add_product_products_id);
            if ($add_product_quantity > $add_max && $add_max != 0) {
                $add_product_quantity = $add_max;
                $messageStack->add_session(WARNING_ORDER_QTY_OVER_MAX, 'warning');
            }

            // Retrieve the information for the new product
            $attributes = (isset($_POST['id'])) ? zen_db_prepare_input($_POST['id']) : array();
            $new_product = eo_get_new_product(
                $add_product_products_id,
                $add_product_quantity,
                false,
                $attributes,
                isset($_POST['applyspecialstoprice'])
            );

            // Add the product to the order
            $eo->eoLog(PHP_EOL . 'Product Being Added:' . PHP_EOL . $eo->eoFormatArray($new_product) . PHP_EOL);
            eo_add_product_to_order($oID, $new_product);

            // Update Subtotal and Pricing
            eo_update_order_subtotal($oID, $new_product);

            // -----
            // Record any previously-recorded coupon for the order into the session, for
            // use by the ot_coupon's calculations.
            //
            $eo->eoSetCouponForOrder($oID);

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

            eo_update_database_order_totals($oID);
            
            unset($_SESSION['cc_id']);

            $eo->eoLog(
                PHP_EOL . 'Final Products in Order:' . PHP_EOL . $eo->eoFormatArray($order->products) . PHP_EOL .
                $eo->eoFormatOrderTotalsForLog($order, 'Final Order Totals:') .
                'Final Tax (total): ' . $order->info['tax'] . PHP_EOL .
                'Final Tax Groups:' . PHP_EOL . $eo->eoFormatArray($order->info['tax_groups']) . PHP_EOL
            );
            $zco_notifier->notify('EDIT_ORDERS_PRODUCT_ADDED', $order);
            
            $comments = sprintf(EO_MESSAGE_PRODUCT_ADDED, (string)$new_product['qty'], $new_product['name']);
            if (isset($new_product['attributes'])) {
                $attribs_added = '';
                foreach ($new_product['attributes'] as $current_attribute) {
                    $attribs_added .= zen_get_option_name_language($current_attribute['option_id'], $_SESSION['languages_id']) . ': ' . $current_attribute['value'] . ', ';
                }
                $attribs_added = substr($attribs_added, 0, -2);  //-Strip trailing ', '
                $comments .= sprintf(EO_MESSAGE_ATTRIBS_ADDED, $attribs_added);
            }
            $eo->eoRecordStatusHistory($oID, $comments);
            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
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

if ($action == 'edit' || ($action == 'update_order' && empty($allow_update))) {
    $action = 'edit';
    
    $order = $eo->getOrderInfo($action);
   
    // -----
    // Initialize the shipping cost, tax-rate and tax-value.
    //
    $eo->eoInitializeShipping($oID, $action);
    
    if (!$eo->eoOrderIsVirtual($order) &&
           ( !is_array($order->customer['country']) || !isset($order->customer['country']['id']) ||
             !is_array($order->billing['country']) || !isset($order->billing['country']['id']) ||
             !is_array($order->delivery['country']) || !isset($order->delivery['country']['id']) )) {
        $messageStack->add(WARNING_ADDRESS_COUNTRY_NOT_FOUND, 'warning');
    }
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta charset="<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/edit_orders.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script src="includes/menu.js"></script>
<script src="includes/general.js"></script>
<script>
  <!--
function init()
{
    cssjsmenu('navbar');
    if (document.getElementById) {
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
    require DIR_WS_INCLUDES . 'header.php';
?>
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
if (!defined('EDIT_ORDERS_USE_NUMERIC_FIELDS')) define('EDIT_ORDERS_USE_NUMERIC_FIELDS', '1');
if (EDIT_ORDERS_USE_NUMERIC_FIELDS != '1') {
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
if ($action == 'edit') {
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
    $next_button .= PHP_EOL;
// END - Add Super Orders Order Navigation Functionality
?>
<!-- body //-->
<table class="eo-table">
    <tr>
<!-- body_text //-->
        <td class="w100 v-top"><table class="eo-table">
            <tr>
                <td class="w100 a-c">
<?php
    echo $prev_button . '&nbsp;&nbsp;' . SELECT_ORDER_LIST . '&nbsp;&nbsp;';
    echo zen_draw_form('input_oid', FILENAME_ORDERS, 'action=edit', 'get', '', true) . zen_draw_input_field('oID', '', 'size="6"') . '</form>';
    echo '&nbsp;&nbsp;' . $next_button . '<br />';
?>
                </td>
            </tr>

            <tr>
                <td class="w100"><table class="w100">
                    <tr>
                        <td class="pageHeading"><?php echo HEADING_TITLE; ?> #<?php echo $oID; ?></td>
                        <td class="pageHeading a-r"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                        <td class="pageHeading a-r">
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
                        <td><table class="w100" id="c-form">
<?php
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

    // -----
    // Starting with EO v4.5.0, a store can indicate the display-order for the order-related addresses.  Rather
    // than including all that code here, we'll use a separate 'module' to display the Customer-Shipping-Billing vs.
    // Customer-Billing-Shipping version.
    //
    $module_name = (EO_ADDRESSES_DISPLAY_ORDER == 'CBS') ? 'eo_addresses_cbs.php' : 'eo_addresses_csb.php';
    require DIR_WS_MODULES . 'edit_orders/' . $module_name;
    
    // -----
    // Give a watching observer the chance to inject some additional, per-address-type, information.
    //
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
<?php
    $max_telephone_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_telephone') . '"';
    $max_email_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_email_address') . '"';
    
    // -----
    // Give a watching observer the opportunity to supply additional contact-information for the order.
    //
    // The $additional_contact_info (supplied as the notification's 2nd parameter), if supplied, is a
    // numerically-indexed array of arrays containing each label and associated content, e.g.:
    //
    // $additional_contact_info[] = array('label' => LABEL_TEXT, 'content' => $field_content);
    //
    $additional_contact_info = array();
    $zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_CONTACT_INFORMATION', $order, $additional_contact_info);
?>
                    <tr>
                        <td><table class="eo-pad">
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
                                <td class="main"><input name="update_customer_telephone" size="15" value="<?php echo zen_db_output($order->customer['telephone']); ?>" <?php echo $max_telephone_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
                                <td class="main"><input name="update_customer_email_address" size="35" value="<?php echo zen_db_output($order->customer['email_address']); ?>" <?php echo $max_email_length; ?>></td>
                            </tr>
<?php
    if (is_array($additional_contact_info) && count($additional_contact_info) != 0) {
        foreach ($additional_contact_info as $contact_info) {
            if (!empty($contact_info['label']) && !empty($contact_info['content'])) {
?>
                            <tr>
                                <td class="main eo-label"><?php echo $contact_info['label']; ?></td>
                                <td class="main"><?php echo $contact_info['content']; ?></td>
                            </tr>
<?php
            }
        }
    }
?>
                        </table></td>
                    </tr>
<!-- End Phone/Email Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Payment Block -->
<?php
    $max_payment_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'payment_method') . '"';
?>
                    <tr>
                        <td><table class="eo-pad">
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_PAYMENT_METHOD; ?></td>
                                <td class="main"><input name="update_info_payment_method" size="20" value="<?php echo zen_db_output($order->info['payment_method']); ?>" <?php echo $max_payment_length; ?>> <?php echo ($order->info['payment_method'] != 'Credit Card') ? ENTRY_UPDATE_TO_CC : ENTRY_UPDATE_TO_CK; ?></td>
                            </tr>
<?php 
    if (!empty($order->info['cc_type']) || !empty($order->info['cc_owner']) || $order->info['payment_method'] == "Credit Card" || !empty($order->info['cc_number'])) {
        $max_type_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_type') . '"';
        $max_owner_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_owner') . '"';
        $max_number_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_number') . '"';
        $max_expires_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_expires') . '"';
?>
<!-- Begin Credit Card Info Block -->
                            <tr>
                                <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
                                <td class="main"><input name="update_info_cc_type" size="10" value="<?php echo zen_db_output($order->info['cc_type']); ?>" <?php echo $max_type_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
                                <td class="main"><input name="update_info_cc_owner" size="20" value="<?php echo zen_db_output($order->info['cc_owner']); ?>" <?php echo $max_owner_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
                                <td class="main"><input name="update_info_cc_number" size="20" value="<?php echo zen_db_output($order->info['cc_number']); ?>" <?php echo $max_number_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
                                <td class="main"><input name="update_info_cc_expires" size="4" value="<?php echo zen_db_output($order->info['cc_expires']); ?>" <?php echo $max_expires_length; ?>></td>
                            </tr>
<!-- End Credit Card Info Block -->
<?php 
    }

    // -----
    // NOTE: No maximum lengths provided for these non-standard fields, since there's no way to know what database table
    // the information is stored in!
    //
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
                                <td class="main"><?php echo zen_db_output($order->info['account_name']); ?></td>
                            </tr>
<?php
        }
        if (isset($order->info['account_number'])) {
?>
                            <tr>
                                <td class="main"><?php echo ENTRY_ACCOUNT_NUMBER; ?></td>
                                <td class="main"><?php echo zen_db_output($order->info['account_number']); ?></td>
                            </tr>
<?php
        }
        if (isset($order->info['po_number'])) {
?>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_PURCHASE_ORDER_NUMBER; ?></strong></td>
                                <td class="main"><?php echo zen_db_output($order->info['po_number']); ?></td>
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
        $choices = array(
            array('id' => 1, 'text' => PAYMENT_CALC_AUTOSPECIALS),
            array('id' => 2, 'text' => PAYMENT_CALC_AUTO),
            array('id' => 3, 'text' => PAYMENT_CALC_MANUAL)
        );
        switch (EO_PRODUCT_PRICE_CALC_DEFAULT) {
            case 'AutoSpecials':
                $default = 1;
                break;
            case 'Auto':
                $default = 2;
                break;
            default:
                $default = 3;
                break;
        }
        if (isset($_SESSION['eo_price_calculations']) && $_SESSION['eo_price_calculations'] >= 1 && $_SESSION['eo_price_calculations'] <= 3) {
            $default = $_SESSION['eo_price_calculations'];
        }
        $payment_calc_choice = '<b>' . PAYMENT_CALC_METHOD . '</b> ' . zen_draw_pull_down_menu('payment_calc_method', $choices, $default);
    } else {
        switch (EO_PRODUCT_PRICE_CALC_METHOD) {
            case 'AutoSpecials':
                $payment_calc_choice = PRODUCT_PRICES_CALC_AUTOSPECIALS;
                break;
            case 'Auto':
                $payment_calc_choice = PRODUCT_PRICES_CALC_AUTO;
                break;
            default:
                $payment_calc_choice = PRODUCT_PRICES_CALC_MANUAL;
                break;
        }
    }

    $additional_inputs = '';
    $zco_notifier->notify('EDIT_ORDERS_FORM_ADDITIONAL_INPUTS', $order, $additional_inputs);
    echo zen_image_submit('button_update.gif', IMAGE_UPDATE, 'name="update_button"') . "&nbsp;$reset_totals_block&nbsp;$payment_calc_choice$additional_inputs";
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
                        <td><table class="eo-table" id="eo-prods">
                            <tr class="dataTableHeadingRow">
<?php
    // -----
    // To add more columns at the beginning of the order's products' table, a
    // watching observer can provide an associative array in the form:
    //
    // $extra_headings = array(
    //     array(
    //       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
    //       'text' => $value
    //     ),
    // );
    //
    // Observer note:  Be sure to check that the $p2/$extra_headings value is specifically (bool)false before initializing, since
    // multiple observers might be injecting content!
    //
    $extra_headings = false;
    $zco_notifier->notify('EDIT_ORDERS_PRODUCTS_HEADING_1', array(), $extra_headings);
    if (is_array($extra_headings)) {
        foreach ($extra_headings as $heading_info) {
            $align = '';
            if (isset($heading_info['align'])) {
                switch ($heading_info['align']) {
                    case 'center':
                        $align = ' a-c';
                        break;
                    case 'right':
                        $align = ' a-r';
                        break;
                    default:
                        $align = '';
                        break;
                }
            }
?>
                <td class="dataTableHeadingContent<?php echo $align; ?>"><strong><?php echo $heading_info['text']; ?></td>
<?php
        }
    }
?>
                                <td class="dataTableHeadingContent a-c" colspan="3"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_TAX; ?></td>
<?php
    // -----
    // Starting with v4.4.0, show both the net and gross unit prices when the store is configured to display prices with tax.
    //
    if (DISPLAY_PRICE_WITH_TAX == 'true') {
?>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_UNIT_PRICE_NET; ?></td>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_UNIT_PRICE_GROSS; ?></td>
<?php
    } else {
?>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_UNIT_PRICE; ?></td>
<?php
    }
?>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_TOTAL_PRICE; ?></td>
                            </tr>
<!-- Begin Products Listings Block -->
<?php
    // -----
    // Initialize (outside of the loop, for performance) the attributes for the various product-related
    // input fields.
    //
    $name_parms = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '" class="eo-name"';
    $model_parms = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '" class="eo-name"';
    
    // -----
    // Loop through each of the products in the order.
    //
    $orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);
    for ($i = 0, $i2 = count($order->products); $i < $i2; $i++) {
        $orders_products_id = $orders_products_id_mapping[$i];
        $eo->eoLog (
            PHP_EOL . '============================================================' .
            PHP_EOL . '= Creating display of Order Product #' . $orders_products_id .
            PHP_EOL . '============================================================' .
            PHP_EOL . 'Product Details:' .
            PHP_EOL . $eo->eoFormatArray($order->products[$i]) . PHP_EOL
        ); 
?>
                            <tr class="dataTableRow v-top">
<?php
    // -----
    // To add more columns at the beginning of the order's products' table, a
    // watching observer can provide an associative array in the form:
    //
    // $extra_data = array(
    //     array(
    //       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
    //       'text' => $value
    //     ),
    // );
    //
    // Observer note:  Be sure to check that the $p2/$extra_data value is specifically (bool)false before initializing, since
    // multiple observers might be injecting content!
    //
    $extra_data = false;
    $product_info = $order->products[$i];
    $product_info['orders_products_id'] = $orders_products_id;
    $zco_notifier->notify('EDIT_ORDERS_PRODUCTS_DATA_1', $product_info, $extra_data);
    if (is_array($extra_data)) {
        foreach ($extra_data as $data) {
            $align = '';
            if (isset($data['align'])) {
                switch ($data['align']) {
                    case 'center':
                        $align = ' a-c';
                        break;
                    case 'right':
                        $align = ' a-r';
                        break;
                    default:
                        $align = '';
                        break;
                }
            }
?>
                <td class="dataTableContent<?php echo $align; ?>"><strong><?php echo $data['text']; ?></td>
<?php
        }
    }
?>
                                <td class="dataTableContent a-c"><input class="eo-qty" name="update_products[<?php echo $orders_products_id; ?>][qty]" value="<?php echo zen_db_prepare_input($order->products[$i]['qty']); ?>" <?php echo $value_parms; ?> /></td>
                                <td>&nbsp;X&nbsp;</td>
                                <td class="dataTableContent"><input name="update_products[<?php echo $orders_products_id; ?>][name]" value="<?php echo zen_db_output($order->products[$i]['name']); ?>" <?php echo $name_parms; ?> />
<?php
        if (isset($order->products[$i]['attributes']) && count($order->products[$i]['attributes']) > 0) { 
?>
                                    <br/><nobr><small>&nbsp;<i><?php echo TEXT_ATTRIBUTES_ONE_TIME_CHARGE; ?>
                                    <input name="update_products[<?php echo $orders_products_id; ?>][onetime_charges]" value="<?php echo zen_db_prepare_input($order->products[$i]['onetime_charges']); ?>" <?php echo $value_parms; ?> />&nbsp;&nbsp;&nbsp;&nbsp;</i></small></nobr><br/>
<?php
            $selected_attributes_id_mapping = eo_get_orders_products_options_id_mappings($oID, $orders_products_id);
            $attrs = eo_get_product_attributes_options($order->products[$i]['id']);

            $optionID = array_keys($attrs);
            for ($j = 0, $j2 = count($attrs); $j < $j2; $j++) {
                $option_id = $optionID[$j];
                $optionInfo = $attrs[$option_id];
                
                // -----
                // If an option for the product wasn't selected (or provided, in the case of TEXT
                // attributes) previously, there's nothing to be selected for its to-be-displayed
                // value.
                //
                $orders_products_attributes_id = (!array_key_exists($option_id, $selected_attributes_id_mapping)) ? array() : $selected_attributes_id_mapping[$option_id];
                
                $option_type = $optionInfo['type'];
                $option_type_hidden_field = zen_draw_hidden_field("update_products[$orders_products_id][attr][$option_id][type]", $option_type);
                $option_name = $optionInfo['name'];

                $eo->eoLog (
                    PHP_EOL . 'Options ID #' . $option_id . PHP_EOL .
                    'Product Attribute: ' . PHP_EOL . $eo->eoFormatArray($orders_products_attributes_id) . PHP_EOL .
                    'Options Info:' . PHP_EOL . $eo->eoFormatArray($optionInfo)
                );

                switch ($option_type) {
                    case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
                    case PRODUCTS_OPTIONS_TYPE_RADIO:
                    case PRODUCTS_OPTIONS_TYPE_SELECT:
                    case PRODUCTS_OPTIONS_TYPE_SELECT_SBA:
                    case PRODUCTS_OPTIONS_TYPE_IMAGE_SWATCH:
                        echo "<label class=\"attribsSelect\" for=\"opid-$orders_products_id-oid-$option_id\">$option_name</label>";
                        $products_options_array = array();
                        $selected_attribute = null;
                        foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                            if (!empty($orders_products_attributes_id) && eo_is_selected_product_attribute_id($orders_products_attributes_id[0], $attributeId)) {
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
                            "update_products[$orders_products_id][attr][$option_id][value]", 
                            $products_options_array,
                            $selected_attribute, 
                            "id=\"opid-$orders_products_id-oid-$option_id\""
                        ) . "<br />\n";
                        echo $option_type_hidden_field;
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                        // First we need to see which items are checked.
                        // This also handles correctly forwarding $id_map.
                        $checked = array();
                        foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                            for ($k = 0, $k2 = count($orders_products_attributes_id); $k < $k2; $k++) {
                                if (eo_is_selected_product_attribute_id($orders_products_attributes_id[$k], $attributeId)) {
                                    $checked[$attributeId] = $orders_products_attributes_id[$k];
                                }
                            }
                        }

                        // Now display the options
                        echo '<div class="attribsCheckboxGroup"><div class="attribsCheckboxName">' . $option_name . '</div>';
                        foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                            $option_html_id = "opid-$orders_products_id-oid-$option_id-$attributeId";
                            echo zen_draw_checkbox_field(
                                "update_products[$orders_products_id][attr][$option_id][value][$attributeId]",
                                $attributeId, 
                                isset($checked[$attributeId]),
                                null, 
                                "id=\"$option_html_id\""
                            ) . "<label class=\"attribsCheckbox\" for=\"$option_html_id\">$attributeValue</label><br />" . PHP_EOL;
                        }
                        echo $option_type_hidden_field . '</div>';
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_TEXT:
                        $text = null;
                        if (!empty($orders_products_attributes_id)) {
                            $text = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_key_first($optionInfo['options']));
                        }
                        if ($text === null) {
                            $text = '';
                        }
                        $text = zen_db_output($text);
                        $option_html_id = "opid-$orders_products_id-oid-$option_id";
                        $option_input_name = "update_products[$orders_products_id][attr][$option_id][value]";
                        $option_rows = $optionInfo['rows'];
                        $option_cols = $optionInfo['size'];
                        echo "<label class=\"attribsInput\" for=\"$option_html_id\">$option_name</label>";
                        if ($optionInfo['rows'] > 1 ) {
                            echo "<textarea class=\"attribsTextarea\" name=\"$option_input_name\" rows=\"$option_rows\" cols=\"$option_cols\" id=\"$option_html_id\">$text</textarea>" . PHP_EOL;
                        } else {
                            echo "<input type=\"text\" name=\"$option_input_name\" size=\"$option_cols\" maxlength=\"$option_cols\" value=\"$text\" id=\"$option_html_id\" /><br />" . PHP_EOL;
                        }
                        echo $option_type_hidden_field;
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_FILE:
                        $optionValue = '';
                        if (!empty($orders_products_attributes_id)) {
                            $optionValue = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_key_first($optionInfo['options']));
                        }
                        echo "<span class=\"attribsFile\">$option_name: " . (!empty($optionValue) ? $optionValue : TEXT_ATTRIBUTES_UPLOAD_NONE) . '</span><br />';
                        if (!empty($optionValue)) {
                            echo zen_draw_hidden_field("update_products[$orders_products_id][attr][$option_id][value]", $optionValue);
                            echo $option_type_hidden_field;
                        }
                        break;
                        
                    case PRODUCTS_OPTIONS_TYPE_READONLY:
                    default:
                        $optionValue = array_shift($optionInfo['options']);
                        echo '<input type="hidden" name="update_products[' .
                            $orders_products_id . '][attr][' . $optionID[$j] . '][value]" value="' .
                            $optionValue . '" /><span class="attribsRO">' .
                            $optionInfo['name'] . ': ' . $optionValue . '</span><br />';
                        echo $option_type_hidden_field;
                        break;
                }
            }
            unset($optionID, $optionInfo, $products_options_array, $selected_attribute, $attributeId, $attributeValue, $optionValue, $text, $checked);
        } 
        
        // -----
        // Starting with EO v4.4.0, both the net and gross prices are displayed when the store displays prices with tax.
        //
        if (DISPLAY_PRICE_WITH_TAX == 'true') {
            $final_price = $order->products[$i]['final_price'];
            $onetime_charges = $order->products[$i]['onetime_charges'];
        } else {
            $final_price = $eo->eoRoundCurrencyValue($order->products[$i]['final_price']);
            $onetime_charges = $eo->eoRoundCurrencyValue($order->products[$i]['onetime_charges']);
        }
        $data_index = " data-opi=\"$orders_products_id\"";
?>
                                </td>
                                <td class="dataTableContent"><input name="update_products[<?php echo $orders_products_id; ?>][model]" value="<?php echo $order->products[$i]['model']; ?>" <?php echo $model_parms; ?> /></td>
                                <td class="dataTableContent a-r"><input class="amount p-t" name="update_products[<?php echo $orders_products_id; ?>][tax]" value="<?php echo zen_display_tax_value($order->products[$i]['tax']); ?>"<?php echo $data_index . ' ' . $tax_parms; ?> />&nbsp;%</td>
                                <td class="dataTableContent a-r"><input class="amount p-n" name="update_products[<?php echo $orders_products_id; ?>][final_price]" value="<?php echo $final_price; ?>"<?php echo $data_index . ' ' . $value_parms; ?> /></td>
<?php

        if (DISPLAY_PRICE_WITH_TAX == 'true') {
            $gross_price = zen_add_tax($final_price, $order->products[$i]['tax']);
?>
                                <td class="dataTableContent a-r"><input class="amount p-g" name="update_products[<?php echo $orders_products_id; ?>][gross]" value="<?php echo $gross_price; ?>"<?php echo $data_index . ' ' . $value_parms; ?> /></td>
<?php
        }
?>
                                <td class="dataTableContent a-r"><?php echo $currencies->format($final_price * $order->products[$i]['qty'] + $onetime_charges, true, $order->info['currency'], $order->info['currency_value']); ?></td>
                            </tr>
<?php
    } 
?>
<!-- End Products Listings Block -->

<!-- Begin Order Total Block -->
                            <tr>
<?php
    $eo_href_link = zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('oID', 'action')) . "oID=$oID&amp;action=add_prdct");
    $eo_add_product_button = zen_image_button('button_add_product.gif', TEXT_ADD_NEW_PRODUCT);
    $eo_add_button_link = '<a href="' . $eo_href_link . '">' . $eo_add_product_button . '</a>';
    
    // -----
    // Give a watching observer the chance to identify additional order-totals that should be considered display-only.
    //
    // The observer returns a comma-separated string of order-total module names, e.g. 'ot_balance_due, ot_payment_received'
    // that, if found in the order, should be displayed but not enabled as inputs.
    //
    // Observer note: Other observers might have previously added THEIR display-only fields, so check to see
    // if the $display_only_totals_list (i.e. $p2) is an empty string before APPENDING your updates.  If the
    // value is not '', then be sure to add a leading ', ' to your display-only list!
    //
    $display_only_totals_list = '';
    $zco_notifier->notify('EDIT_ORDERS_DISPLAY_ONLY_TOTALS', '', $display_only_totals_list);
    $display_only_totals = array();
    if (!empty($display_only_totals_list)) {
        $eo->eoLog('Display-only totals identified: ' . json_encode($display_only_totals_list));
        $display_only_totals = explode(',', str_replace(' ', '', (string)$display_only_totals_list));
    }
    
    // -----
    // The number of columns displayed in this section depends on whether/not the store displays prices
    // with tax.  If so, both the net- and gross-prices are displayed; otherwise, simply the net.
    //
    $columns = ((DISPLAY_PRICE_WITH_TAX == 'true') ? 7 : 6) - 2;

    // Iterate over the order totals.
    for ($i = 0, $index = 0, $n = count($order->totals); $i < $n; $i++) {
        $update_total = "update_total[$index]";
        $update_total_code = $update_total . '[code]';
        $update_total_title = $update_total . '[title]';
        $update_total_value = $update_total . '[value]';
        
        $index_update_needed = true;
?>
                            <tr>
                                <td class="dataTableContent" colspan="3"><?php echo ($i == 0) ? $eo_add_button_link : '&nbsp;'; ?></td>
<?php
        $total = $order->totals[$i];
        $trimmed_title = strip_tags(trim($total['title']));
        
        $order_total_info = eo_get_order_total_by_order((int)$oID, $total['class']);
        $details = array_shift($order_total_info);
        $total_class = (in_array($total['class'], $display_only_totals)) ? 'display-only' : $total['class'];
        switch ($total_class) {
            case 'ot_purchaseorder':
                $index_update_needed = false;
                break;

            // Automatically generated fields, those should never be included
            case 'ot_subtotal':
            case 'ot_total':
            case 'ot_tax':
            case 'ot_local_sales_taxes':
            case 'display-only':
                $index_update_needed = false;
?>
                                <td colspan="<?php echo $columns - 2; ?>">&nbsp;</td>
                                <td class="main a-r eo-label"><?php echo $total['title']; ?></td>
                                <td class="main a-r eo-label"><?php echo $total['text']; ?></td>
<?php
                break;

            // Include these in the update but do not allow them to be changed
            case 'ot_group_pricing':
            case 'ot_cod_fee':
            case 'ot_loworderfee':
?>
                                <td colspan="<?php echo $columns - 2; ?>"><?php echo zen_draw_hidden_field($update_total_code, $total['class']); ?></td>
                                <td class="main a-r"><?php echo strip_tags($total['title']) . zen_draw_hidden_field($update_total_title, $trimmed_title); ?></td>
                                <td class="main a-r"><?php echo $total['text'] . zen_draw_hidden_field($update_total_value, $details['value']); ?></td>
<?php
                break;

            // Allow changing the title / text, but not the value. Typically used
            // for order total modules which handle the value based upon another condition
            case 'ot_coupon': 
?>
                                <td colspan="<?php echo $columns - 2; ?>"><?php echo zen_draw_hidden_field($update_total_code, $total['class']); ?></td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_title, $trimmed_title, 'class="amount eo-entry"'); ?></td>
                                <td class="main a-r"><?php echo $total['text'] . zen_draw_hidden_field($update_total_value, $details['value']); ?></td>
<?php
                break;

            case 'ot_shipping':
                $shipping_tax_rate = $eo->eoGetShippingTaxRate($order);
                $shipping_title_max = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'shipping_method') . '"';
?>
                                <td class="a-r"><?php echo zen_draw_hidden_field($update_total_code, $total['class']) . zen_draw_pull_down_menu($update_total . '[shipping_module]', eo_get_available_shipping_modules(), $order->info['shipping_module_code']) . '&nbsp;&nbsp;' . zen_draw_input_field($update_total_title, $trimmed_title, 'class="amount eo-entry" ' . $shipping_title_max); ?></td>
                                
                                <td class="a-r"><?php echo zen_draw_input_field('shipping_tax', (string)$shipping_tax_rate, 'class="amount" id="s-t"' . $input_tax_parms, false, $input_field_type); ?>&nbsp;%</td>
<?php
                if (DISPLAY_PRICE_WITH_TAX == 'true') {
                    $shipping_net = $details['value'] / (1 + ($shipping_tax_rate / 100));
?>
                                <td class="a-r"><?php echo zen_draw_input_field($update_total_value, (string)$shipping_net, 'class="amount" id="s-n"' . $input_value_parms, false, $input_field_type); ?></td>
<?php
                    $update_total_value = 'shipping_gross';
                }
?>
                                <td>&nbsp;</td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_value, $details['value'], 'class="amount" id="s-g"' . $input_value_parms, false, $input_field_type); ?></td>
<?php
                break;

            case 'ot_gv':
            case 'ot_voucher': 
?>
                                <td colspan="<?php echo $columns - 2; ?>"><?php echo zen_draw_hidden_field($update_total_code, $total['class']); ?></td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_title, $trimmed_title, 'class="amount eo-entry"'); ?></td>
                                <td class="smallText a-r">
<?php                 
                if ($details['value'] > 0) {
                    $details['value'] *= -1;
                }
                echo zen_draw_input_field($update_total_value, $details['value'], 'class="amount" step="any"', false, $input_field_type);
?>
                                </td>
<?php
                break;

            default: 
?>
                                <td colspan="<?php echo $columns - 2; ?>"><?php echo zen_draw_hidden_field($update_total_code, $total['class']); ?></td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_title, $trimmed_title, 'class="amount eo-entry"'); ?></td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_value, $details['value'], 'class="amount"'); ?></td>
<?php
                break;
        } 
?>
                            </tr>
<?php
        if ($index_update_needed) {
            $index++;
        }
    }
    
    $additional_totals_displayed = false;
    if (count(eo_get_available_order_totals_class_values($oID)) > 0) {
        $additional_totals_displayed = true;
?>
                            <tr>
                                <td colspan="<?php echo $columns; ?>">&nbsp;</td>
                                <td class="smallText a-r"><?php echo TEXT_ADD_ORDER_TOTAL . zen_draw_pull_down_menu($update_total_code, eo_get_available_order_totals_class_values($oID), '', 'id="update_total_code"'); ?></td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_title, '', 'class="amount eo-entry"'); ?></td>
                                <td class="smallText a-r"><?php echo zen_draw_input_field($update_total_value, '', 'class="amount" step="any"', false, $input_field_type); ?></td>
                            </tr>
                            <tr>
                                <td colspan="<?php echo $columns + 3; ?>" class="smallText a-l" id="update_total_shipping" style="display: none"><?php echo TEXT_CHOOSE_SHIPPING_MODULE . zen_draw_pull_down_menu('update_total[' . $index . '][shipping_module]', eo_get_available_shipping_modules()); ?></td>
                            </tr>
<?php
    }
    unset($i, $index, $n, $total, $details); 
?>
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
                        <td class="main"><table id="osh" border="1" cellspacing="0" cellpadding="5">
<?php 
    // -----
    // Gather the order's status-history records, sorting based on the configuration setting added in v4.4.0.
    //
    $osh_order_by = (EO_STATUS_HISTORY_DISPLAY_ORDER == 'Desc') ? "date_added DESC, orders_status_history_id DESC" : "date_added ASC, orders_status_history_id ASC";
    $orders_history = $db->Execute(
        "SELECT *
           FROM " . TABLE_ORDERS_STATUS_HISTORY . "
          WHERE orders_id = $oID
          ORDER BY $osh_order_by"
    );

    if (defined('TY_TRACKER') && TY_TRACKER == 'True') { 
?>
                            <tr class="dataTableHeadingRow v-top">
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_DATE_ADDED; ?></strong></td>
                                <td class="dataTableHeadingContent smallText a-c"><strong><?php echo TABLE_HEADING_CUSTOMER_NOTIFIED; ?></strong></td>
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_STATUS; ?></strong></td>
<!-- TY TRACKER 4 BEGIN, DISPLAY TRACKING ID IN COMMENTS TABLE -->
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_TRACKING_ID; ?></strong></td>
<!-- END TY TRACKER 4 END, DISPLAY TRACKING ID IN COMMENTS TABLE -->
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
                            </tr>
<?php
        if ($orders_history->RecordCount() > 0) {
            while (!$orders_history->EOF) {
                $icon_image = eo_display_customers_notifications_icon($orders_history->fields['customer_notified']);
?>
                            <tr class="dataTableHeadingRow v-top">
                                <td class="smallText"><?php echo zen_datetime_short($orders_history->fields['date_added']); ?></td>
                                <td class="smallText a-c"><?php echo $icon_image; ?></td>
                                <td class="smallText"><?php echo $orders_status_array[$orders_history->fields['orders_status_id']]; ?></td>
<?php
                $display_track_id = '&nbsp;';
                for ($ty = 1; $ty < 6; $ty++) {
                    $ty_field_name = "track_id$ty";
                    if (!empty($orders_history->fields[$ty_field_name])) {
                        $track_id = nl2br(zen_output_string_protected($orders_history->fields[$ty_field_name]));
                        $display_track_id .= (constant("CARRIER_NAME_$ty") . ': <a href="' . constant("CARRIER_LINK_$ty") . $track_id . ' target="_blank">' . $track_id . '</a>&nbsp;');
                    }
                }
?>
                                <td class="smallText"><?php echo $display_track_id; ?></td>
                                <td class="smallText"><?php echo nl2br(zen_db_output($orders_history->fields['comments'])); ?></td>
                            </tr>
<?php
                $orders_history->MoveNext();
            }
        } else {
?>
                            <tr>
                                <td class="smallText" colspan="5"><?php echo TEXT_NO_ORDER_HISTORY; ?></td>
                            </tr>
<?php
        }
    } else {
        if ($orders_history->EOF) {
?>
                    <tr>
                        <td class="smallText no-osh"><?php echo TEXT_NO_ORDER_HISTORY; ?></td>
                    </tr>
<?php
        } else {
            // -----
            // Initialize the table describing the "standard" table elements to display, then issue a notification
            // that allows a watching observer to manipulate the table to re-arrange the order of each row's
            // display and/or insert additional display fields.  The table's columns (left-to-right) will be displayed
            // in the order specified in this table (top-to-bottom).
            //
            // Each table element is an associative array (keyed on the field name in the orders_status_history table),
            // containing an array with the following recognized elements:
            //
            // title ................ (Required) The title to be displayed in the table header for the data column.  Note that the
            //                        'title' can be blank, indicating that no title is associated with the database field and that
            //                        the field is not displayed within the overall status table.
            // show_function ........ (Optional) Identifies the name of the function to be called to display the database value.  The
            //                        function takes either 1 (the database field value) or 2 (the database field value, then the field
            //                        name), depending on the value of the 'include_field_name' field.
            //                        If the element is not supplied, the value present in the database is displayed.
            // include_field_name ... (Optional) If a 'show_function' is identified and this element is (bool)true, then the 'show_function'
            //                        takes two parameters, as identified above.
            // align ................ (Optional) Identifies the alignment to be applied when rendering the element in the table, one of:
            //                        center, right or left (the default).
            //
            $table_elements = array(
                'date_added' => array(
                    'title' => TABLE_HEADING_DATE_ADDED,
                    'show_function' => 'zen_datetime_short',
                    'include_field_name' => false
                ),
                'customer_notified' => array(
                    'title' => TABLE_HEADING_CUSTOMER_NOTIFIED,
                    'show_function' => 'eo_display_customers_notifications_icon',
                    'align' => 'center',
                    'include_field_name' => false
                ),
                'orders_status_id' => array(
                    'title' => TABLE_HEADING_STATUS,
                    'show_function' => 'built-in'
                ),
                'comments' => array(
                    'title' => TABLE_HEADING_COMMENTS,
                    'show_function' => 'built-in'
                ),
            );
            
            // -----
            // If the orders_status_history::updated_by field exists, add the display of that element to the table.
            //
            if (isset($orders_history->fields['updated_by'])) {
                $table_elements['updated_by'] = array(
                    'title' => TABLE_HEADING_UPDATED_BY,
                    'align' => 'center',
                    'show_function' => 'built-in'
                );
            }
            $zco_notifier->notify('EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT', $oID, $table_elements);
            if (!is_array($table_elements) || count($table_elements) == 0) {
                trigger_error('Non-array value returned from EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT: ' . json_encode($table_elements), E_USER_ERROR);
                exit();
            }
            
            $eo->eoLog('Preparing to display status history: ' . $eo->eoFormatArray($table_elements));
            
            // -----
            // Create the table's header, based on the current table-elements ...
            //
?>
                    <tr class="dataTableHeadingRow v-top">
<?php
            foreach ($table_elements as $field_name => $field_values) {
                if (empty($field_values['title'])) {
                    continue;
                }
                
                $align_class = '';
                if (isset($field_values['align'])) {
                    switch ($field_values['align']) {
                        case 'right':
                            $align_class = ' a-r';
                            break;
                        case 'center':
                            $align_class = ' a-c';
                            break;
                        default:
                            $align_class = ' a-l';
                            break;
                    }
                }
                $table_elements[$field_name]['align_class'] = $align_class;
?>
                        <td class="dataTableHeadingContent smallText<?php echo $align_class; ?>"><?php echo $field_values['title']; ?></td>
<?php
            }
?>
                    </tr>
<?php
            // -----
            // Loop through each of the order's history records, displaying the columns as
            // identified in the current table elements.
            //
            while (!$orders_history->EOF) {
?>
                    <tr class="v-top">
<?php
                foreach ($table_elements as $field_name => $field_values) {
                    // -----
                    // If the current field name is not present in the orders_status_history
                    // table, there's nothing to do.
                    //
                    if (!array_key_exists($field_name, $orders_history->fields)) {
                        continue;
                    }
                    
                    // -----
                    // Grab the current field's value to improve readability.
                    //
                    $field_value = $orders_history->fields[$field_name];
                    
                    // -----
                    // No show_function?  Then just output the associated field value.
                    //
                    if (empty($field_values['show_function'])) {
                        $display_value = $field_value;
                    } else {
                        $show_function = $field_values['show_function'];
                        
                        // -----
                        // Built-in function?  Make sure it's supported and then provide the output for the
                        // current field.
                        //
                        if ($show_function == 'built-in') {
                            switch ($field_name) {
                                case 'orders_status_id':
                                    $display_value = $orders_status_array[$field_value];
                                    break;
                                case 'comments':
                                    $display_value = nl2br(zen_db_output($field_value));
                                    break;
                                case 'updated_by':
                                    $display_value = $field_value;
                                    break;
                                default:
                                    trigger_error("Unknown field ($field_name) for built-in function display.", E_USER_ERROR);
                                    exit();
                                    break;
                            }
                        // -----
                        // Otherwise, it's a 'specified' show_function.  Make sure it exists and then pass either one or
                        // two arguments, depending on the table's configuration.
                        //
                        } else {
                            $show_function = $field_values['show_function'];
                            if (!function_exists($show_function)) {
                                trigger_error("Function ($show_function) to display '$field_name' does not exist.", E_USER_ERROR);
                                exit();
                            }
                            if (!empty($field_values['include_field_name']) && $field_values['include_field_name'] === true) {
                                $display_value = $show_function($field_value, $field_name);
                            } else {
                                $display_value = $show_function($field_value);
                            }
                        }
                    }
                    
                    // -----
                    // Output the current field's display-value if there's an associated header-column.
                    //
                    if (!empty($field_values['title'])) {
?>
                        <td class="smallText<?php echo $field_values['align_class']; ?>"><?php echo $display_value; ?></td>
<?php
                    }
                }
?>
                    </tr>
<?php
                $orders_history->MoveNext();
            }
        }
    } 
?>
                        </table></td>
                    </tr>

                    <tr>
                        <td class="main"><br /><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
                    </tr>
                    
                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><?php echo zen_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
                    </tr>
<?php
    // -----
    // Give an observer the opportunity to add additional content to the status-history form.
    //
    // The additional-content array is numerically-indexed and provides the HTML to be included.
    //
    $additional_osh_content = array();
    $zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_OSH_CONTENT', $order, $additional_osh_content);
    if (is_array($additional_osh_content) && count($additional_osh_content) != 0) {
        foreach ($additional_osh_content as $osh_content) {
?>
                    <tr>
                        <td class="main"><?php echo $osh_content; ?></td>
                    </tr>
<?php
        }
    }
?>
<!-- TY TRACKER 7 BEGIN, ENTER TRACKING INFORMATION -->
<?php 
    if (defined('TY_TRACKER') && TY_TRACKER == 'True') { 
?>
    <tr>
        <td class="main">
            <table>
                <tr>
                    <td class="main"><strong><?php echo zen_image(DIR_WS_IMAGES . 'icon_track_add.png', ENTRY_ADD_TRACK) . '&nbsp;' . ENTRY_ADD_TRACK; ?></strong></td>
                </tr>
                <tr class="v-top">
                    <td>
                        <table class="w100">
                            <tr class="dataTableHeadingRow">
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_CARRIER_NAME; ?></strong></td>
                                <td class="dataTableHeadingContent smallText"><strong><?php echo TABLE_HEADING_TRACKING_ID; ?></strong></td>
                            </tr>
                            <?php for($i=1;$i<=5;$i++) {
                                if (constant('CARRIER_STATUS_' . $i) == 'True') { ?>
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
<?php 
    } 
?>
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
<?php
    // -----
    // Determine the default setting for the customer notification, based on the configuration
    // setting added in v4.4.0.
    //
    switch (EO_CUSTOMER_NOTIFICATION_DEFAULT) {
        case 'Hidden':
            $email_default = false;
            $noemail_default = false;
            $hidden_default = true;
            break;
        case 'No Email':
            $email_default = false;
            $noemail_default = true;
            $hidden_default = false;
            break;
        default:
            $email_default = true;
            $noemail_default = false;
            $hidden_default = false;
            break;
    }
?>
                    <tr>
                        <td><table>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_NOTIFY_CUSTOMER; ?></strong> [<?php echo zen_draw_radio_field('notify', '1', $email_default) . '-' . TEXT_EMAIL . ' ' . zen_draw_radio_field('notify', '0', $noemail_default) . '-' . TEXT_NOEMAIL . ' ' . zen_draw_radio_field('notify', '-1', $hidden_default) . '-' . TEXT_HIDE; ?>]&nbsp;&nbsp;&nbsp;</td>
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
<table class="eo-table">
    <tr>
        <td width="100%"><table class="w100">
            <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE_ADD_PRODUCT; ?> #<?php echo $oID; ?></td>
                <td class="pageHeading a-r"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                <td class="pageHeading a-r">
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
        array_unshift($categoriesarr, array('id' => 0.5, 'text' => ADDPRODUCT_CHOOSE_CATEGORY));

        $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', $categoriesarr, 0.5, 'onchange="this.form.submit();"');
    } else {
        // Add the category selection. Selecting a category will override the search
        $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', zen_get_category_tree(), $current_category_id, 'onchange="this.form.submit();"');
    }
?> 
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP1; ?></td>
                <td class="dataTableContent">
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
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP2; ?></td>
                <td class="dataTableContent">
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
    <tr class="dataTableRow v-top">
        <td class="dataTableContent eo-label"><?php echo ADDPRODUCT_TEXT_STEP3; ?> <i><?php echo ADDPRODUCT_TEXT_OPTIONS_NOTEXIST; ?></i></td>
    </tr>
<?php
        } else {
            $attrs = eo_get_product_attributes_options($add_product_products_id);
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP3; ?></td>
                <td class="dataTableContent">
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
                        foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
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
                        $text = zen_db_output($text);
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
                <td class="dataTableContent a-c">
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
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP4; ?></td>
                <td class="dataTableContent"><?php echo ADDPRODUCT_TEXT_CONFIRM_QUANTITY . 
                    zen_draw_input_field('add_product_quantity', 1, 'class="eo-qty"' . $input_value_parms, true, $input_field_type) .
                    '&nbsp;&nbsp;&nbsp;&nbsp;' .
                    zen_draw_checkbox_field('applyspecialstoprice', '1', true) . ADDPRODUCT_SPECIALS_SALES_PRICE; ?></td>
                 <td class="dataTableContent a-c">
                    <input type="submit" value="<?php echo ADDPRODUCT_TEXT_CONFIRM_ADDNOW; ?>" />
<?php
        if (isset($_POST['id'])) {
            foreach ($_POST['id'] as $id => $value) {
                if (is_array($value)) {
                    foreach ($value as $id2 => $value2) {
                        if (is_array($value2)) {
                            foreach ($value2 as $id3 => $value3) {
                                echo zen_draw_hidden_field('id[' . $id . '][' . $id2 . '][' . $id3 . ']', zen_db_output($value3));
                            }
                        } else {
                            echo zen_draw_hidden_field('id[' . $id . '][' . $id2 . ']', zen_db_output($value2));
                        }
                    }
                } else {
                    echo zen_draw_hidden_field('id[' . $id . ']', zen_db_output($value));
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

// -----
// Include id-specific javascript only if the associated blocks have been rendered.
//
if ($additional_totals_displayed) {
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

if (DISPLAY_PRICE_WITH_TAX == 'true') {
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
