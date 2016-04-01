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

  global $db;

  require('includes/application_top.php');

  // Check for commonly broken attribute related items
  eo_checks_and_warnings();

  // Start the currencies code
  include_once(DIR_WS_INCLUDES . 'init_includes/init_currencies.php');

  // Use the normal order class instead of the admin one
  include(DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php');

  $oID = zen_db_prepare_input($_GET['oID']);
  $step = zen_db_prepare_input($_POST['step']);
  $add_product_categories_id = zen_db_prepare_input($_POST['add_product_categories_id']);
  $add_product_products_id = zen_db_prepare_input($_POST['add_product_products_id']);
  $add_product_quantity = zen_db_prepare_input($_POST['add_product_quantity']);

  $orders_statuses = array();
  $orders_status_array = array();
  $orders_status_query = $db->Execute("select orders_status_id, orders_status_name
  					from " . TABLE_ORDERS_STATUS . "
                                 where language_id = '" . (int)$_SESSION['languages_id'] . "' order by orders_status_id");
  while (!$orders_status_query->EOF) {
    $orders_statuses[] = array('id' => $orders_status_query->fields['orders_status_id'],
                               'text' => $orders_status_query->fields['orders_status_name'] . ' [' . $orders_status_query->fields['orders_status_id'] . ']');
    $orders_status_array[$orders_status_query->fields['orders_status_id']] = $orders_status_query->fields['orders_status_name'];
    $orders_status_query->MoveNext();
  }

  $action = (isset($_GET['action']) ? $_GET['action'] : 'edit');

  if (zen_not_null($action)) {
  	if(EO_DEBUG_ACTION_LEVEL > 0) eo_log(
  	  '============================================================' . PHP_EOL .
  	  '= Edit Orders (' . EO_VERSION . ') Action Log' . PHP_EOL .
  	  '============================================================' . PHP_EOL .
  	  'Order ID: ' . $oID . PHP_EOL .
  	  'Action Requested: ' . $action . PHP_EOL .
  	  'Enabled Order Totals: ' . str_replace('.php', '', MODULE_ORDER_TOTAL_INSTALLED) . PHP_EOL
  	);

    switch ($action) {

	// Update Order
	case 'update_order':
		$comments = zen_db_prepare_input($_POST['comments']);
		$status = (int)zen_db_prepare_input($_POST['status']);
		if ($status < 1) break;

		$order_updated = false;
		$sql_data_array = array(
			'customers_name' => zen_db_prepare_input($_POST['update_customer_name']),
			'customers_company' => zen_db_prepare_input($_POST['update_customer_company']),
			'customers_street_address' => zen_db_prepare_input($_POST['update_customer_street_address']),
			'customers_suburb' => zen_db_prepare_input($_POST['update_customer_suburb']),
			'customers_city' => zen_db_prepare_input($_POST['update_customer_city']),
			'customers_state' => zen_db_prepare_input($_POST['update_customer_state']),
			'customers_postcode' => zen_db_prepare_input($_POST['update_customer_postcode']),
			'customers_country' => zen_db_prepare_input($_POST['update_customer_country']),
			'customers_telephone' => zen_db_prepare_input($_POST['update_customer_telephone']),
			'customers_email_address' => zen_db_prepare_input($_POST['update_customer_email_address']),
			'last_modified' => 'now()',

			'billing_name' => zen_db_prepare_input($_POST['update_billing_name']),
			'billing_company' => zen_db_prepare_input($_POST['update_billing_company']),
			'billing_street_address' => zen_db_prepare_input($_POST['update_billing_street_address']),
			'billing_suburb' => zen_db_prepare_input($_POST['update_billing_suburb']),
			'billing_city' => zen_db_prepare_input($_POST['update_billing_city']),
			'billing_state' => zen_db_prepare_input($_POST['update_billing_state']),
			'billing_postcode' => zen_db_prepare_input($_POST['update_billing_postcode']),
			'billing_country' => zen_db_prepare_input($_POST['update_billing_country']),

			'delivery_name' => zen_db_prepare_input($_POST['update_delivery_name']),
			'delivery_company' => zen_db_prepare_input($_POST['update_delivery_company']),
			'delivery_street_address' => zen_db_prepare_input($_POST['update_delivery_street_address']),
			'delivery_suburb' => zen_db_prepare_input($_POST['update_delivery_suburb']),
			'delivery_city' => zen_db_prepare_input($_POST['update_delivery_city']),
			'delivery_state' => zen_db_prepare_input($_POST['update_delivery_state']),
			'delivery_postcode' => zen_db_prepare_input($_POST['update_delivery_postcode']),
			'delivery_country' => zen_db_prepare_input($_POST['update_delivery_country']),
			'payment_method' => zen_db_prepare_input($_POST['update_info_payment_method']),
			'cc_type' => zen_db_prepare_input($_POST['update_info_cc_type']),
			'cc_owner' => zen_db_prepare_input($_POST['update_info_cc_owner']),
			'cc_expires' => zen_db_prepare_input($_POST['update_info_cc_expires'])
		);

		// If the country was passed as an id, change it to the country name for
		// storing in the database. This is done in case a country is removed in
		// the future, so the country name is still associated with the order.
		if(is_numeric($sql_data_array['customers_country']))
			$sql_data_array['customers_country'] = zen_get_country_name((int)$sql_data_array['customers_country']);
		if(is_numeric($sql_data_array['billing_country']))
			$sql_data_array['billing_country'] = zen_get_country_name((int)$sql_data_array['billing_country']);
		if(is_numeric($sql_data_array['delivery_country']))
			$sql_data_array['delivery_country'] = zen_get_country_name((int)$sql_data_array['delivery_country']);

		// For PA-DSS Compliance, we no longer store the Credit Card number in
		// the database. While inconvenient, this saves us in the event of an audit.
		if(array_key_exists('update_info_cc_number', $_POST)) {
			$update_info_cc_number = zen_db_prepare_input($_POST['update_info_cc_number']);

			// If the number is not already obscufated, we use the same method
			// as the authorize.net module to obscufate the entered CC number
			if(is_numeric($update_info_cc_number))
				$update_info_cc_number = str_pad(substr($_POST['update_info_cc_number'], -4), strlen($_POST['update_info_cc_number']), "X", STR_PAD_LEFT);

			$sql_data_array['cc_number'] = $update_info_cc_number;
			unset($_POST['update_info_cc_number']);
		}

		zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = \'' . (int)$oID . '\'');

		// BEGIN TY TRACKER 1 - READ FROM POST
		$track_id = array();
		if(defined(TY_TRACKER) && TY_TRACKER == 'True') {
			$track_id = zen_db_prepare_input($_POST['track_id']);

			$ty_changed = false;
			foreach($track_id as $id => $track) {
				if(constant('CARRIER_STATUS_' . $id) == 'True' && zen_not_null($track)) {
					$ty_changed = true;
				}
			}
			if(!$ty_changed) $track_id = array();
		}
		// END TY TRACKER 1 - READ FROM POST
		$check_status = $db->Execute(
			'SELECT customers_name, customers_email_address, orders_status, ' .
			'date_purchased FROM `' . TABLE_ORDERS . '` WHERE orders_id = \'' . (int)$oID . '\''
		);

		// Begin - Update Status History & Email Customer if Necessary
		if(($check_status->fields['orders_status'] != $status) || zen_not_null($track_id) || zen_not_null($comments)) {
			$customer_notified = '0';
			if(isset($_POST['notify']) && ($_POST['notify'] == '1')) {

				$notify_comments = '';
				if (isset($_POST['notify_comments']) && ($_POST['notify_comments'] == 'on')) {
					if (zen_not_null($comments)) {
						$notify_comments = EMAIL_TEXT_COMMENTS_UPDATE . $comments . "\n\n";
					}
					// BEGIN TY TRACKER 2 - EMAIL TRACKING INFORMATION
					if (zen_not_null($track_id)) {
						$notify_comments = EMAIL_TEXT_COMMENTS_TRACKING_UPDATE . "\n\n";
						$comment = EMAIL_TEXT_COMMENTS_TRACKING_UPDATE;
					}
					foreach($track_id as $id => $track) {
						if(zen_not_null($track) && constant('CARRIER_STATUS_' . $id) == 'True') {
							$notify_comments .= "Your " . constant('CARRIER_NAME_' . $id) . " Tracking ID is " . $track . " \n<br /><a href=" . constant('CARRIER_LINK_' . $id) . $track . ">Click here</a> to track your package. \n<br />If the above link does not work, copy the following URL address and paste it into your Web browser. \n<br />" . constant('CARRIER_LINK_' . $id) . $track . "\n\n<br /><br />It may take up to 24 hours for the tracking information to appear on the website." . "\n<br />";
						}
					}
					unset($id); unset($track);
					// END TY TRACKER 32 - EMAIL TRACKING INFORMATION
				}
				//send emails
				$message =
				//<!-- Begin Edit Orders Modification (Minor formatting change) //-->
				STORE_NAME . " " . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n\n" .
				//<!-- End Edit Orders Modification (Minor formatting change) //-->
				EMAIL_TEXT_INVOICE_URL . ' ' . zen_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n\n" .
				EMAIL_TEXT_DATE_ORDERED . ' ' . zen_date_long($check_status->fields['date_purchased']) . "\n\n" .
				strip_tags($notify_comments) .
				EMAIL_TEXT_STATUS_UPDATED . sprintf(EMAIL_TEXT_STATUS_LABEL, $orders_status_array[$status] ) .
				EMAIL_TEXT_STATUS_PLEASE_REPLY;

				$html_msg['EMAIL_CUSTOMERS_NAME']    = $check_status->fields['customers_name'];
				$html_msg['EMAIL_TEXT_ORDER_NUMBER'] = EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID;
				$html_msg['EMAIL_TEXT_INVOICE_URL']  = '<a href="' . zen_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') .'">'.str_replace(':','',EMAIL_TEXT_INVOICE_URL).'</a>';
				$html_msg['EMAIL_TEXT_DATE_ORDERED'] = EMAIL_TEXT_DATE_ORDERED . ' ' . zen_date_long($check_status->fields['date_purchased']);
				$html_msg['EMAIL_TEXT_STATUS_COMMENTS'] = nl2br($notify_comments);
				$html_msg['EMAIL_TEXT_STATUS_UPDATED'] = str_replace('\n','', EMAIL_TEXT_STATUS_UPDATED);
				$html_msg['EMAIL_TEXT_STATUS_LABEL'] = str_replace('\n','', sprintf(EMAIL_TEXT_STATUS_LABEL, $orders_status_array[$status] ));
				$html_msg['EMAIL_TEXT_NEW_STATUS'] = $orders_status_array[$status];
				$html_msg['EMAIL_TEXT_STATUS_PLEASE_REPLY'] = str_replace('\n','', EMAIL_TEXT_STATUS_PLEASE_REPLY);
				$html_msg['EMAIL_PAYPAL_TRANSID'] = '';

				zen_mail($check_status->fields['customers_name'], $check_status->fields['customers_email_address'], EMAIL_TEXT_SUBJECT . ' #' . $oID, $message, STORE_NAME, EMAIL_FROM, $html_msg, 'order_status');
				$customer_notified = '1';

				// PayPal Trans ID, if any
				$sql = "select txn_id, parent_txn_id from " . TABLE_PAYPAL . " where order_id = :orderID order by last_modified DESC, date_added DESC, parent_txn_id DESC, paypal_ipn_id DESC ";
				$sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
				$result = $db->Execute($sql);
				if ($result->RecordCount() > 0) {
					$message .= "\n\n" . ' PayPal Trans ID: ' . $result->fields['txn_id'];
					$html_msg['EMAIL_PAYPAL_TRANSID'] = $result->fields['txn_id'];
				}

				//send extra emails
				if (SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO_STATUS == '1' and SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO != '') {
					zen_mail('', SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO, SEND_EXTRA_ORDERS_STATUS_ADMIN_EMAILS_TO_SUBJECT . ' ' . EMAIL_TEXT_SUBJECT . ' #' . $oID, $message, STORE_NAME, EMAIL_FROM, $html_msg, 'order_status_extra');
				}
			}
			else if (isset($_POST['notify']) && ($_POST['notify'] == '-1')) {
				// hide comment
				$customer_notified = '-1';
			}

			$sql_data_array = array(
				'orders_id' => (int)$oID,
				'orders_status_id' => zen_db_input($status),
				'date_added' => 'now()',
				'customer_notified' => zen_db_input($customer_notified),
				'comments' => zen_db_input($comments),
			);
			// BEGIN TY TRACKER 3 - INCLUDE DATABASE FIELDS IN STATUS UPDATE
			foreach($track_id as $id => $track) {
				$sql_data_array['track_id' . $id] = zen_db_input($track);
			}
			unset($id); unset($track);
			// END TY TRACKER 3 - INCLUDE DATABASE FIELDS IN STATUS UPDATE
			zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

			$sql_data_array = array(
				'orders_status' => zen_db_input($status),
				'last_modified' => 'now()'
			);
			zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = \'' . (int)$oID . '\'');
			unset($sql_data_array);
			$order_updated = true;
		}
		// End - Update Status History & Email Customer if Necessary

		// Load the order details.
		$GLOBALS['order'] = eo_get_order_by_id($oID);

		switch(true) {
			case EO_DEBUG_ACTION_LEVEL > 2:
				eo_log('Order Subtotal: ' . $GLOBALS['order']->info['subtotal']);
				eo_log('Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
			case EO_DEBUG_ACTION_LEVEL > 1:
				eo_log('Order Tax (total): ' . $GLOBALS['order']->info['tax']);
				eo_log('Order Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
			default:
		}

		// Handle updating products and attributes as needed
		if(array_key_exists('update_products', $_POST)) {
			$_POST['update_products'] = zen_db_prepare_input($_POST['update_products']);

			if(EO_DEBUG_ACTION_LEVEL > 0) eo_log(
				PHP_EOL . '============================================================' .
				PHP_EOL . '= Processing Requested Updates to Products' .
				PHP_EOL . '============================================================' .
				PHP_EOL . PHP_EOL .
				PHP_EOL . eo_log('Requested Products:' . PHP_EOL . var_export($_POST['update_products'], true) . PHP_EOL)
			);
			if(EO_DEBUG_ACTION_LEVEL > 3) eo_log('Products in Original Order: ' . PHP_EOL . var_export($GLOBALS['order']->products, true) . PHP_EOL);

			foreach($_POST['update_products'] as $orders_products_id => $product_update) {
				$product_update['qty'] = (float) $product_update['qty'];
				$product_update['name'] = $product_update['name'];

				$rowID = -1;
				$orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);
				for($i=0; $i<sizeof($orders_products_id_mapping); $i++) {
					if($orders_products_id == $orders_products_id_mapping[$i]) {
						$rowID = $i;
						break;
					}
    			}
    			unset($orders_products_id_mapping); unset($i);

    			if(EO_DEBUG_ACTION_LEVEL > 3) {
    				eo_log('Order Product ID: ' . $orders_products_id . ' Row ID: ' . $rowID);
    				eo_log('Product in Request: ' . PHP_EOL . var_export($product_update, true) . PHP_EOL);
    			}

    			// Only update if there is an existing item in the order
    			if($rowID >= 0) {
    				// Grab the old product + attributes
    				$old_product = $order->products[$rowID];

					switch(true) {
						case EO_DEBUG_ACTION_LEVEL > 3:
							eo_log('Old Product:' . PHP_EOL . var_export($old_product, true) . PHP_EOL);
						case EO_DEBUG_ACTION_LEVEL > 2:
							eo_log('Old Order Subtotal: ' . $GLOBALS['order']->info['subtotal']);
							eo_log('Old Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
						case EO_DEBUG_ACTION_LEVEL > 1:
							eo_log('Old Tax (total): ' . $GLOBALS['order']->info['tax']);
							eo_log('Old Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
						default:
					}
    				// Remove the product from the order
    				eo_remove_product_from_order((int)$oID, $orders_products_id);

    				// Update Subtotal and Pricing
    				eo_update_order_subtotal((int)$oID, $old_product, false);

    			    switch(true) {
    					case EO_DEBUG_ACTION_LEVEL > 2:
    						eo_log('Removed Product Order Subtotal: ' . $GLOBALS['order']->info['subtotal']);
    						eo_log('Removed Product Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
    					case EO_DEBUG_ACTION_LEVEL > 1:
    						eo_log('Removed Product Tax (total): ' . $GLOBALS['order']->info['tax']);
    						eo_log('Removed Product Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
    					default:
    				}

    				if($product_update['qty'] > 0) {

    					// Retrieve the information for the new product
    					$attrs = $product_update['attr'];
    					unset($product_update['attr']);
						$new_product = eo_get_new_product(
							$old_product['id'],
							$product_update['qty'],
							$attrs,
							false
						);
						unset($attrs);

						// Handle the case where the product was deleted
						// from the store. This should probably never be done.
						// Removing the product will cause issues with links
						// on invoices (order history) and will not allow the
						// price(s) or tax(es) to be recalculated by Zen Cart.
						if(!array_key_exists('price', $new_product)) {
							$new_product['price'] = $old_product['price'];
							$new_product['tax'] = $old_product['tax'];
							if($new_product['tax'] > 0) {
								// Should match what is set by eo_get_product_taxes()
								// When no description is present in the database but
								// a tax rate exists on a product.
								$new_product['tax_description'] = TEXT_UNKNOWN_TAX_RATE .
									' (' . zen_display_tax_value($new_product['tax']) . '%)';
							}

							$new_product['products_discount_type'] = $old_product['products_discount_type'];
							$new_product['products_discount_type_from'] = $old_product['products_discount_type_from'];
							$new_product['products_priced_by_attribute'] = $old_product['products_priced_by_attribute'];
							$new_product['product_is_free'] = $old_product['product_is_free'];
						}

						// Adjust the product information based upon the
						// data found in update_products
						$new_product = array_merge($new_product, $product_update);

	    				// Add the product to the order
	    				eo_add_product_to_order((int)$oID, $new_product);

	    				// Update Subtotal and Pricing
	    				eo_update_order_subtotal((int)$oID, $new_product);

	    				switch(true) {
	    					case EO_DEBUG_ACTION_LEVEL > 3:
	    						eo_log('Added Product:' . PHP_EOL . var_export($new_product, true) . PHP_EOL);
	    					case EO_DEBUG_ACTION_LEVEL > 2:
	    						eo_log('Added Product Order Subtotal: ' . $GLOBALS['order']->info['subtotal']);
	    						eo_log('Added Product Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
	    					case EO_DEBUG_ACTION_LEVEL > 1:
	    						eo_log('Added Product Tax (total): ' . $GLOBALS['order']->info['tax']);
	    						eo_log('Added Product Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
	    					default:
	    				}
    				}

    				$order_updated = true;
    			}
    		}
    		// Reset order if updated
    		if($order_updated) {
    			eo_update_database_order_totals($oID);

    			// Need to force update the tax field if the tax is zero
    			// This runs after the shipping tax is added by the above update
    			$decimals = $currencies->get_decimal_places($_SESSION['currency']);
    			if(zen_round($GLOBALS['order']->info['tax'], $decimals) == 0) {
    				if(!array_key_exists('update_total', $_POST)) $_POST['update_total'] = array();
    				$_POST['update_total'][] = array(
    					'code' => 'ot_tax',
    					'title' => '',
    					'value' => 0,
    				);
    			}

    			$GLOBALS['order'] = eo_get_order_by_id($oID);
    		}

			switch(true) {
				case EO_DEBUG_ACTION_LEVEL > 3:
	    			eo_log('Updated Products in Order:' . PHP_EOL . var_export($GLOBALS['order']->products, true) . PHP_EOL);
				case EO_DEBUG_ACTION_LEVEL > 2:
					eo_log('Updated Products Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
				case EO_DEBUG_ACTION_LEVEL > 1:
	 				eo_log('Updated Products Tax (total): ' . $GLOBALS['order']->info['tax']);
					eo_log('Updated Products Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
				default:
			}
		}

		// Update order totals (or delete if no title / value)
		if(array_key_exists('update_total', $_POST)) {
			if(EO_DEBUG_ACTION_LEVEL > 0) eo_log(
				PHP_EOL . '============================================================' .
				PHP_EOL . '= Processing Requested Updates to Order Totals' .
				PHP_EOL . '============================================================' .
				PHP_EOL . PHP_EOL .
				PHP_EOL . eo_log('Requested Order Totals:' . PHP_EOL . var_export($_POST['update_total'], true) . PHP_EOL)
			);
			switch(true) {
				case EO_DEBUG_ACTION_LEVEL > 2:
					eo_log('Starting Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
				case EO_DEBUG_ACTION_LEVEL > 1:
					eo_log('Starting Tax (total): ' . $GLOBALS['order']->info['tax']);
					eo_log('Starting Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
				default:
			}

			foreach($_POST['update_total'] as $order_total) {
				$order_total['text'] = $currencies->format($order_total['value'], true, $order->info['currency'], $order->info['currency_value']);
				$order_total['sort_order'] = $GLOBALS[$order_total['code']]->sort_order;

				// TODO Special processing for some modules
				if(zen_not_null($order_total['title']) && $order_total['title'] != ':') {
					switch($order_total['code']) {
						case 'ot_shipping':
							$GLOBALS['order']->info['shipping_cost'] = $order_total['value'];
							$GLOBALS['order']->info['shipping_module_code'] = $order_total['shipping_module'];
							break;
						case 'ot_tax':
							if(count($GLOBALS['order']->products) == 0) {
								$order_total['title'] = '';
								$order_total['value'] = 0;
							}
							$GLOBALS['order']->info['tax'] = $order_total['value'];
							break;
						case 'ot_loworderfee':
							// Always remove this entry, it will be automatically
							// Readded to the order if needed.
							$order_total['title'] = '';
							$order_total['value'] = 0;
							break;
						case 'ot_gv':
							if($order_total['value'] < 0) $order_total['value'] = $order_total['value'] * -1;
							$order_total['text'] = $currencies->format($order_total['value'], true, $order->info['currency'], $order->info['currency_value']);
							$_SESSION['cot_gv'] = $order_total['value'];
							break;
						case 'ot_voucher':
							if($order_total['value'] < 0) $order_total['value'] = $order_total['value'] * -1;
							$order_total['text'] = $currencies->format($order_total['value'], true, $order->info['currency'], $order->info['currency_value']);
							$_SESSION['cot_voucher'] = $order_total['value'];
							break;
						case 'ot_coupon':
							// Default to using the title from the module
							$coupon = rtrim($order_total['title'], ': ');
							$order_total['title'] = $GLOBALS[$order_total['code']]->title;

							// Look for correctly formated title
							preg_match('/([^:]+):([^:]+)/', $coupon, $matches);
							if(count($matches) > 2) {
								$order_total['title'] = trim($matches[1]);
								$coupon = $matches[2];
							}
							$cc_id = $db->Execute(
								'SELECT coupon_id FROM `' . TABLE_COUPONS . '` ' .
								'WHERE coupon_code=\'' . trim($coupon) . '\''
							);
							unset($matches, $coupon);

							if(!$cc_id->EOF) $_SESSION['cc_id'] = $cc_id->fields['coupon_id'];
							else {
								$messageStack->add_session(WARNING_ORDER_COUPON_BAD, 'warning');
								$order_total['title'] = '';
								$order_total['value'] = 0;
							}
							unset($cc_id);

							break;
						default:
					}
				}

				$found = false;
				foreach($GLOBALS['order']->totals as $key => $total) {
					if($total['class'] == $order_total['code']) {
						// Update the information in the order
						$GLOBALS['order']->totals[$key]['title'] = $order_total['title'];
						$GLOBALS['order']->totals[$key]['value'] = $order_total['value'];
						$GLOBALS['order']->totals[$key]['text'] = $order_total['text'];

						$found = true;
						break;
					}
				}

				if(!$found) {
					$GLOBALS['order']->totals[] = array(
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

			// Reset order and resave (fixes some edge cases)
			$GLOBALS['order'] = eo_get_order_by_id($oID);
			eo_update_database_order_totals($oID);

			switch(true) {
				case EO_DEBUG_ACTION_LEVEL > 2:
					eo_log('Updated Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
				case EO_DEBUG_ACTION_LEVEL > 1:
					eo_log('Updated Tax (total): ' . $GLOBALS['order']->info['tax']);
					eo_log('Updated Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
				default:
			}

			// Unset some session variables after updating the order totals
			if(array_key_exists('cot_gv', $_SESSION)) unset($_SESSION['cot_gv']);
			if(array_key_exists('cot_voucher', $_SESSION)) unset($_SESSION['cot_voucher']);
			if(array_key_exists('cc_id', $_SESSION)) unset($_SESSION['cc_id']);

			$order_updated = true;
		}

		if($order_updated) {
			$messageStack->add_session(SUCCESS_ORDER_UPDATED, 'success');
		}
		else {
			$messageStack->add_session(WARNING_ORDER_NOT_UPDATED, 'warning');
		}

		if(EO_DEBUG_ACTION_LEVEL > 0) eo_log(
			PHP_EOL . '============================================================' .
			PHP_EOL . '= Done Processing Requested Updates to the Order' .
			PHP_EOL . '============================================================' .
			PHP_EOL . PHP_EOL
		);
		switch(true) {
			case EO_DEBUG_ACTION_LEVEL > 2:
				eo_log('Final Subtotal: ' . $GLOBALS['order']->info['subtotal']);
				eo_log('Final Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
			case EO_DEBUG_ACTION_LEVEL > 1:
				eo_log('Final Tax (total): ' . $GLOBALS['order']->info['tax']);
				eo_log('Final Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
			default:
		}

		zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit', 'NONSSL'));
		break;

	case 'add_prdct':
		if(!zen_not_null($step)) $step = 1;
		if(EO_DEBUG_ACTION_LEVEL > 0) eo_log(
			PHP_EOL . '============================================================' .
			PHP_EOL . '= Adding a new product to the order (step ' . $step . ')' .
			PHP_EOL . '============================================================' .
			PHP_EOL . PHP_EOL
		);
		if($step == 5) {

			// Get Order Info
			$GLOBALS['order'] = eo_get_order_by_id($oID);

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
				zen_db_prepare_input($_POST['id']),
				isset($_POST['applyspecialstoprice'])
			);

			// Add the product to the order
			if(EO_DEBUG_ACTION_LEVEL > 3) eo_log('Product Being Added:' . PHP_EOL . var_export($new_product, true) . PHP_EOL);
	    	eo_add_product_to_order($oID, $new_product);

	    	// Update Subtotal and Pricing
	    	eo_update_order_subtotal($oID, $new_product);

	    	// Save the changes
	   		eo_update_database_order_totals($oID);
	   		$GLOBALS['order'] = eo_get_order_by_id($oID);

	   		// Remove the low order fee (will automatically repopulate if needed)
	    	foreach($GLOBALS['order']->totals as $key => $total) {
	    		if($total['class'] == 'ot_loworderfee') {
	    			// Update the information in the order
	    			$total['title'] = '';
	    			$total['value'] = 0;
	    			$total['code'] = $total['class'];

	    			eo_update_database_order_total($oID, $total);
	    			unset($GLOBALS['order']->totals[$key]);
	    			break;
	    		}
	    	}

	    	// Requires $GLOBALS['order'] to be reset and populated
	    	$GLOBALS['order'] = eo_get_order_by_id($oID);
	   		eo_update_database_order_totals($oID);

	   		switch(true) {
	   			case EO_DEBUG_ACTION_LEVEL > 3:
	   				eo_log('Final Products in Order:' . PHP_EOL . var_export($GLOBALS['order']->products, true) . PHP_EOL);
	   			case EO_DEBUG_ACTION_LEVEL > 2:
	   				eo_log('Final Order Totals:' . PHP_EOL . var_export($GLOBALS['order']->totals, true) . PHP_EOL);
	   			case EO_DEBUG_ACTION_LEVEL > 1:
	   				eo_log('Final Tax (total): ' . $GLOBALS['order']->info['tax']);
	   				eo_log('Final Tax Groups:' . PHP_EOL . var_export($GLOBALS['order']->info['tax_groups'], true) . PHP_EOL);
	   			default:
	   		}

	   		zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
		}
		break;
    }
  }

  if (($action == 'edit') && isset($_GET['oID'])) {
    $orders_query = $db->Execute("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
    $order_exists = true;
    if (!$orders_query->RecordCount()) {
      $order_exists = false;
      $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');
    }
    else {
    	$order = eo_get_order_by_id($oID);

    	if(!is_array($order->customer['country']) || !array_key_exists('id', $order->customer['country']) ||
    		!is_array($order->billing['country']) || !array_key_exists('id', $order->billing['country']) ||
    		!is_array($order->delivery['country']) || !array_key_exists('id', $order->delivery['country'])) {
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
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
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
<body onload="init()">
<!-- header //-->
<div class="header-area">
<?php
	require(DIR_WS_INCLUDES . 'header.php');
?>
</div>
<!-- header_eof //-->
<?php
  if (($action == 'edit') && ($order_exists == true)) {
    if ($order->info['payment_module_code']) {
      if (file_exists(DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php')) {
        require(DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php');
        require(DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/payment/' . $order->info['payment_module_code'] . '.php');
        $module = new $order->info['payment_module_code'];
      }
    }
// BEGIN - Add Super Orders Order Navigation Functionality
    $get_prev = $db->Execute("SELECT orders_id FROM " . TABLE_ORDERS . " WHERE orders_id < '" . $oID . "' ORDER BY orders_id DESC LIMIT 1");

    if (zen_not_null($get_prev->fields['orders_id'])) {
      $prev_button = '            <INPUT class="normal_button button" TYPE="BUTTON" VALUE="<<< ' . $get_prev->fields['orders_id'] . '" ONCLICK="window.location.href=\'' . zen_href_link(FILENAME_ORDERS, 'oID=' . $get_prev->fields['orders_id'] . '&action=edit') . '\'">';
    }
    else {
      $prev_button = '            <INPUT class="normal_button button" TYPE="BUTTON" VALUE="' . BUTTON_TO_LIST . '" ONCLICK="window.location.href=\'' . zen_href_link(FILENAME_ORDERS) . '\'">';
    }


    $get_next = $db->Execute("SELECT orders_id FROM " . TABLE_ORDERS . " WHERE orders_id > '" . $oID . "' ORDER BY orders_id ASC LIMIT 1");

    if (zen_not_null($get_next->fields['orders_id'])) {
      $next_button = '            <INPUT class="normal_button button" TYPE="BUTTON" VALUE="' . $get_next->fields['orders_id'] . ' >>>" ONCLICK="window.location.href=\'' . zen_href_link(FILENAME_ORDERS, 'oID=' . $get_next->fields['orders_id'] . '&action=edit') . '\'">';
    }
    else {
      $next_button = '            <INPUT class="normal_button button" TYPE="BUTTON" VALUE="' . BUTTON_TO_LIST . '" ONCLICK="window.location.href=\'' . zen_href_link(FILENAME_ORDERS) . '\'">';
  }
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
            <td align="center">
	    <table border="0" cellspacing="3" cellpadding="0">
              <tr>
                <td class="main" align="center" valign="bottom"><?php echo $prev_button; ?></td>
                <td class="smallText" align="center" valign="bottom"><?php
                  echo SELECT_ORDER_LIST . '<br />';
                  echo zen_draw_form('input_oid', FILENAME_ORDERS, '', 'get', '', true);
                  echo zen_draw_input_field('oID', '', 'size="6"');
                  echo zen_draw_hidden_field('action', 'edit');
                  echo '</form>';
                ?></td>
                <td class="main" align="center" valign="bottom"><?php echo $next_button; ?></td>
              </tr>
            </table>
	    </td>
<!-- END - Add Super Orders Order Navigation Functionality -->
					<td class="pageHeading" align="right"> &nbsp; </td>
				</tr>
			</table>
		</td>
	</tr>


      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?> #<?php echo $oID; ?></td>
            <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="pageHeading" align="right">
	    <?php echo '<a href="' . zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('action'))) . '">' . zen_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?>
	    <?php echo '<a href="' . zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('oID', 'action')) . 'oID=' . $oID . '&amp;action=edit', 'NONSSL') . '">' . zen_image_button('button_details.gif', IMAGE_ORDER_DETAILS) . '</a>'; ?>
	    </td>
         </tr>
        </table></td>
      </tr>


<!-- Begin Addresses Block -->
      <tr>
      <td><?php echo zen_draw_form('edit_order', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action','paycc')) . 'action=update_order'); ?>
      <table width="100%" border="0">
	  <tr>
	  <td>
      <table width="100%" border="0">
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
    <td valign="top"><?php
    	if(is_array($order->customer['country']) && array_key_exists('id', $order->customer['country'])) {
    		echo zen_get_country_list('update_customer_country', $order->customer['country']['id']);
    	}
    	else { ?>
			<input name="update_customer_country" size="45" value="<?php echo zen_html_quotes($order->customer['country']); ?>">
		<?php } ?>
    </td>
    <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</strong></td>
    <td valign="top"><?php
    	if(is_array($order->billing['country']) && array_key_exists('id', $order->billing['country'])) {
    		echo zen_get_country_list('update_billing_country', $order->billing['country']['id']);
    	}
    	else { ?>
			<input name="update_billing_country" size="45" value="<?php echo zen_html_quotes($order->billing['country']); ?>">
		<?php } ?>
	</td>
    <td valign="top"><strong><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</strong></td>
    <td valign="top"><?php
    	if(is_array($order->delivery['country']) && array_key_exists('id', $order->delivery['country'])) {
    		echo zen_get_country_list('update_delivery_country', $order->delivery['country']['id']);
    	}
    	else { ?>
			<input name="update_delivery_country" size="45" value="<?php echo zen_html_quotes($order->delivery['country']); ?>">
		<?php } ?>
  </tr>
</table>
</td></tr></table>
<!-- End Addresses Block -->

      <tr>
	<td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>

<!-- Begin Phone/Email Block -->
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">
      		<tr>
      		  <td class="main"><strong><?php echo ENTRY_TELEPHONE_NUMBER; ?></strong></td>
      		  <td class="main"><input name='update_customer_telephone' size='15' value='<?php echo zen_html_quotes($order->customer['telephone']); ?>'></td>
      		</tr>
      		<tr>
      		  <td class="main"><strong><?php echo ENTRY_EMAIL_ADDRESS; ?></strong></td>
      		  <td class="main"><input name='update_customer_email_address' size='35' value='<?php echo zen_html_quotes($order->customer['email_address']); ?>'></td>
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
	    <td class="main"><input name='update_info_payment_method' size='20' value='<?php echo zen_html_quotes($order->info['payment_method']); ?>'>
	    <?php
	    if($order->info['payment_method'] != "Credit Card")
	    echo ENTRY_UPDATE_TO_CC;
		else
	    echo ENTRY_UPDATE_TO_CK;
	    ?></td>
	  </tr>

	<?php if ($order->info['cc_type'] || $order->info['cc_owner'] || $order->info['payment_method'] == "Credit Card" || $order->info['cc_number']) { ?>
	  <!-- Begin Credit Card Info Block -->
	  <tr>
	    <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
	  </tr>
	  <tr>
	    <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_TYPE; ?></strong></td>
	    <td class="main"><input name='update_info_cc_type' size='10' value='<?php echo zen_html_quotes($order->info['cc_type']); ?>'></td>
	  </tr>
	  <tr>
	    <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_OWNER; ?></strong></td>
	    <td class="main"><input name='update_info_cc_owner' size='20' value='<?php echo zen_html_quotes($order->info['cc_owner']); ?>'></td>
	  </tr>
	  <tr>
	    <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></strong></td>
	    <td class="main"><input name='update_info_cc_number' size='20' value='<?php echo zen_html_quotes($order->info['cc_number']); ?>'></td>
	  </tr>
	  <tr>
	    <td class="main"><strong><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></strong></td>
	    <td class="main"><input name='update_info_cc_expires' size='4' value='<?php echo zen_html_quotes($order->info['cc_expires']); ?>'></td>
	  </tr>
	  <!-- End Credit Card Info Block -->
	<?php } ?>

	<?php
        if( (zen_not_null($order->info['account_name']) || zen_not_null($order->info['account_number']) || zen_not_null($order->info['po_number'])) ) {
		?>
	  <tr>
        <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo ENTRY_ACCOUNT_NAME; ?></td>
		<td class="main"><?php echo zen_html_quotes($order->info['account_name']); ?></td>
	  </tr>
	  <tr>
		<td class="main"><?php echo ENTRY_ACCOUNT_NUMBER; ?></td>
		<td class="main"><?php echo zen_html_quotes($order->info['account_number']); ?></td>
	  </tr>
	  <tr>
		<td class="main"><?php echo ENTRY_PURCHASE_ORDER_NUMBER; ?></td>
		<td class="main"><?php echo zen_html_quotes($order->info['po_number']); ?></td>
	  </tr>
		<?php
		// purchaseorder end
		    }
?>
	</table></td>
      </tr>
      <tr>
	<td valign="top"><?php echo zen_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
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
	for($i=0; $i<sizeof($order->products); $i++) {
		$orders_products_id = $orders_products_id_mapping[$i];
		if(EO_DEBUG_ACTION_LEVEL > 3) eo_log(
			PHP_EOL . '============================================================' .
			PHP_EOL . '= Creating display of Order Product #' . $orders_products_id .
			PHP_EOL . '============================================================' .
			PHP_EOL . 'Product Details:' .
			PHP_EOL . var_export($order->products[$i],true) . PHP_EOL
		); ?>

		<tr class="dataTableRow">
			<td class="dataTableContent" valign="top" align="left"><input name="update_products[<?php echo $orders_products_id; ?>][qty]" size="2" value="<?php echo zen_db_prepare_input($order->products[$i]['qty']); ?>" />&nbsp;&nbsp;&nbsp;&nbsp; X</td>
			<td class="dataTableContent" valign="top" align="left"><input name="update_products[<?php echo $orders_products_id; ?>][name]" size="55" value="<?php echo zen_html_quotes($order->products[$i]['name']); ?>" /><?php

		if(sizeof($order->products[$i]['attributes']) > 0) { ?>
				<br/><nobr><small>&nbsp;<i>
					<?php echo TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?><input name="update_products[<?php echo $orders_products_id; ?>][onetime_charges]" size="8" value="<?php echo zen_db_prepare_input($order->products[$i]['onetime_charges']); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
				</i></small></nobr><br/><?php

			$selected_attributes_id_mapping = eo_get_orders_products_options_id_mappings($oID, $orders_products_id);
			$attrs = eo_get_product_attributes_options($order->products[$i]['id']);
			$optionID = array_keys($attrs);
			for($j=0; $j<sizeof($attrs); $j++)
			{
				$optionInfo = $attrs[$optionID[$j]];
				$orders_products_attributes_id = $selected_attributes_id_mapping[$optionID[$j]];

				if(EO_DEBUG_ACTION_LEVEL > 3) {
					eo_log('Options ID #' . $optionID[$j]);
					eo_log('Product Attribute: ' . PHP_EOL . var_export($orders_products_attributes_id, true) . PHP_EOL);
					eo_log('Options Info:' . PHP_EOL . var_export($optionInfo, true) . PHP_EOL);
				}

				switch($optionInfo['type']) {
					case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
					case PRODUCTS_OPTIONS_TYPE_RADIO:
					case PRODUCTS_OPTIONS_TYPE_SELECT:
						echo '<label class="attribsSelect" for="opid-' .
							$orders_products_id . '-oid-' . $optionID[$j] .
							'">' . $optionInfo['name'] . '</label>';
						$products_options_array = array();
						$selected_attribute = null;
						foreach($optionInfo['options'] as $attributeId => $attributeValue) {
							if(eo_is_selected_product_attribute_id($orders_products_attributes_id[0], $attributeId))
								$selected_attribute = $attributeId;
							$products_options_array[] = array(
								'id' => $attributeId,
								'text' => $attributeValue
							);
						}
						if($selected_attribute === null) $selected_attribute = $products_options_array[0]['id'];

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
						unset($products_options_array); unset($selected_attribute);
						unset($attributeId); unset($attributeValue);
						break;
					case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
						// First we need to see which items are checked.
						// This also handles correctly forwarding $id_map.
						$checked = array();
						foreach($optionInfo['options'] as $attributeId => $attributeValue) {
							for($k=0;$k<sizeof($orders_products_attributes_id);$k++) {
								if(eo_is_selected_product_attribute_id($orders_products_attributes_id[$k], $attributeId)) {
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
								$optionID[$j] .	'-' . $attributeId . '"'
							) . '<label class="attribsCheckbox" for="opid-' .
								$orders_products_id . '-oid-' .
								$optionID[$j] .	'-' . $attributeId . '">' .
							$attributeValue . '</label><br />' . "\n";
						}
						echo zen_draw_hidden_field(
							'update_products[' . $orders_products_id . '][attr][' .
							$optionID[$j] . '][type]', $optionInfo['type']
						) . '</div>';
						unset($checked); unset($attributeId); unset($attributeValue);
						break;
					case PRODUCTS_OPTIONS_TYPE_TEXT:
						$text = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_shift(array_keys($optionInfo['options'])));
						if($text === null) {
							$text = '';
						}
						$text = zen_html_quotes($text);
						echo '<label class="attribsInput" for="opid-' .
							$orders_products_id . '-oid-' . $optionID[$j] .
							'">' . $optionInfo['name'] . '</label>';
						if($optionInfo['rows'] > 1 ) {
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
								$optionID[$j] .	'" /><br />' . "\n";
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
						if(zen_not_null($value)) {
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
			unset($optionID); unset($optionInfo);
		} ?>
			</td>
			<td class="dataTableContent" valign="top"><input name="update_products[<?php echo $orders_products_id; ?>][model]" size="55" value="<?php echo $order->products[$i]['model']; ?>" /></td>
			<td class="dataTableContent" align="right" valign="top">
				<input class="amount" name="update_products[<?php echo $orders_products_id; ?>][tax]" size="3" value="<?php echo zen_display_tax_value($order->products[$i]['tax']); ?>" />&nbsp;%
			</td>
			<td class="dataTableContent" align="right" valign="top"><input class="amount" name="update_products[<?php echo $orders_products_id; ?>][final_price]" size="5" value="<?php echo number_format($order->products[$i]['final_price'], 2, '.', ''); ?>" /></td>
			<td class="dataTableContent" align="right" valign="top"><?php echo $GLOBALS['currencies']->format($order->products[$i]['final_price'] * $order->products[$i]['qty'] + $order->products[$i]['onetime_charges'], true, $order->info['currency'], $order->info['currency_value']); ?></td>
		</tr><?php
	} ?>
	<!-- End Products Listings Block -->

	<!-- Begin Order Total Block -->
	  <tr>
	    <td align="right" colspan="6">
	    	<table border="0" cellspacing="0" cellpadding="2" width="100%">
	    	<tr>
	    <td valign='top'>
		<br>
		<?php echo '<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('oID', 'action')) . 'oID=' . $oID . '&amp;action=add_prdct', 'NONSSL') . '">' . zen_image_button('button_add_product.gif', TEXT_ADD_NEW_PRODUCT) . '</a>'; ?>
		</td>
	    	<td align='right'>
	    	<table border="0" cellspacing="0" cellpadding="2">
<?php
	// Iterate over the order totals.
	for($i=0, $index=0, $n=count($order->totals); $i<$n; $i++, $index++) { ?>
				<tr><?php
		$total = $order->totals[$i];
		$details = array_shift(eo_get_order_total_by_order((int)$oID, $total['class']));
		switch($total['class']) {
			// Automatically generated fields, those should never be included
			case 'ot_subtotal':
			case 'ot_total':
			case 'ot_tax':
			case 'ot_local_sales_taxes': ?>
					<td align="right">&nbsp;</td>
					<td class="main" align="right"><strong><?php echo $total['title']; ?></strong></td>
					<td class="main" align="right"><strong><?php echo $total['text']; ?></strong></td><?php
				$index--;
				break;

			// Include these in the update but do not allow them to be changed
			case 'ot_group_pricing':
			case 'ot_loworderfee': ?>
					<td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
					<td align="right" class="main"><?php
						echo strip_tags($total['title']);
						echo zen_draw_hidden_field('update_total[' . $index . '][title]', strip_tags($total['title'])); ?>
					</td>
					<td align="right" class="main"><?php
						echo $total['text'];
						echo zen_draw_hidden_field('update_total[' . $index . '][value]', $details['value']); ?>
					</td><?php
				break;

			// Allow changing the title / text, but not the value. Typically used
			// for order total modules which handle the value based upon another condition
			case 'ot_coupon': ?>
					<td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?>
					</td>
					<td align="right" class="main"><?php
						echo $total['text'];
						echo zen_draw_hidden_field('update_total[' . $index . '][value]', $details['value']); ?>
					</td><?php
				break;

			case 'ot_shipping': ?>
					<td align="right"><?php
						echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']);
						echo zen_draw_pull_down_menu('update_total[' . $index . '][shipping_module]', eo_get_available_shipping_modules(), $order->info['shipping_module_code']); ?>
					</td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?>
					</td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][value]', $details['value'], 'class="amount" size="6"'); ?>
					</td><?php
				break;

			case 'ot_gv':
			case 'ot_voucher': ?>
					<td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?>
					</td>
					<td align="right" class="smallText"><?php
						if($details['value'] > 0) $details['value'] = $details['value'] * -1;
						echo '<input class="amount" size="6" name="update_total[' . $index . '][value]" value="' . $details['value'], '" />'; ?>
					</td><?php
				break;

			default: ?>
					<td align="right"><?php echo zen_draw_hidden_field('update_total[' . $index . '][code]', $total['class']); ?></td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][title]', strip_tags(trim($total['title'])), 'class="amount" size="' . strlen(strip_tags(trim($total['title']))) . '"'); ?>
					</td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][value]', $details['value'], 'class="amount" size="6"'); ?>
					</td><?php
				break;
		} ?>
				</tr><?php
	}
	if(count(eo_get_available_order_totals_class_values($oID)) > 0) { ?>
				<tr>
					<td align="right" class="smallText"><?php echo TEXT_ADD_ORDER_TOTAL;
						echo zen_draw_pull_down_menu('update_total[' . $index . '][code]', eo_get_available_order_totals_class_values($oID), '', 'id="update_total_code"');
					?></td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][title]', '', 'class="amount" style="width: 100%"'); ?>
					</td>
					<td align="right" class="smallText"><?php
						echo zen_draw_input_field('update_total[' . $index . '][value]', '', 'class="amount" size="6"'); ?>
					</td>
				</tr>
				<tr>
					<td align="left" colspan="3" class="smallText" id="update_total_shipping" style="display: none"><?php echo TEXT_CHOOSE_SHIPPING_MODULE;
						echo zen_draw_pull_down_menu('update_total[' . $index . '][shipping_module]', eo_get_available_shipping_modules()); ?>
					</td>
				</tr><?php
	}
	unset($i, $index, $n, $total, $details); ?>
	    	</table>
	    	</td>
	    	</tr>
	    	</table>
	    </td>
	  </tr>
	<!-- End Order Total Block -->

	</table></td>
      </tr>

      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
		<td class="main">
			<strong><?php echo zen_image(DIR_WS_IMAGES . 'icon_comment_add.png', TABLE_HEADING_STATUS_HISTORY) . '&nbsp;' . TABLE_HEADING_STATUS_HISTORY; ?></strong>
		</td>
      </tr>
      <tr>
        <td class="main">
		<table border="1" cellspacing="0" cellpadding="5" width="60%">
<?php if (TY_TRACKER == 'True') { ?>
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
    $orders_history = $db->Execute("select orders_status_id, date_added, customer_notified, comments
                                    from " . TABLE_ORDERS_STATUS_HISTORY . "
                                    where orders_id = '" . zen_db_input($oID) . "'
                                    order by date_added");
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
<?php } ?>
        </table></td>
      </tr>

      <tr>
        <td class="main"><br><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
      </tr>
      <tr>
        <td class="main">
			<table width="60%" border="0"  cellspacing="0" cellpadding="0">
				<tr>
					<td>

					<?php
					echo zen_draw_textarea_field('comments', 'soft', '60', '5');
					?>
					</td>
				</tr>
			</table>
        </td>
      </tr>

<!-- TY TRACKER 7 BEGIN, ENTER TRACKING INFORMATION -->
<?php if(defined(TY_TRACKER) && TY_TRACKER == 'True') { ?>
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
          <?php /* } */ ?>
        </table></td>
      </tr>

      <tr>
	<td valign="top"><?php echo zen_image_submit('button_update.gif', IMAGE_UPDATE); ?></td>
      </tr>
  </form>
<?php
  }

if($action == "add_prdct")
{ ?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE_ADD_PRODUCT; ?> #<?php echo $oID; ?></td>
            <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
			<td class="pageHeading" align="right">
				<?php echo '<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('oID', 'action', 'resend')) . 'oID=' . $oID . '&amp;action=edit', 'NONSSL') . '">' . zen_image_button('button_back.gif', IMAGE_EDIT) . '</a>' ?>
				<?php echo '<a href="' . zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('oID', 'action', 'resend')) . 'oID=' . $oID . '&amp;action=edit', 'NONSSL') . '">' . zen_image_button('button_details.gif', IMAGE_ORDER_DETAILS) . '</a>'; ?></td>
			</td>
          </tr>
        </table></td>
      </tr>
      <tr><td><table border='0'>
<?php
	// Set Defaults
	if(!IsSet($add_product_categories_id))
		$add_product_categories_id = .5;

	if(!IsSet($add_product_products_id))
		$add_product_products_id = 0;

	// Step 1: Choose Category
	if ($add_product_categories_id == .5) {
		// Handle initial population of categories
		$categoriesarr = zen_get_category_tree();
		$catcount = count($categoriesarr);
		$texttempcat1 = $categoriesarr[0][text];
		$idtempcat1 = $categoriesarr[0][id];
		$catcount++;
		for ($i=1; $i<$catcount; $i++) {
			$texttempcat2 = $categoriesarr[$i][text];
			$idtempcat2 = $categoriesarr[$i][id];
			$categoriesarr[$i][id] = $idtempcat1;
			$categoriesarr[$i][text] = $texttempcat1;
			$texttempcat1 = $texttempcat2;
			$idtempcat1 = $idtempcat2;
		}


		$categoriesarr[0][text] = "Choose Category";
		$categoriesarr[0][id] = .5;


		$categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', $categoriesarr, $current_category_id, 'onChange="this.form.submit();"');
		$categoryselectoutput = str_replace('<option value="0" SELECTED>','<option value="0">',$categoryselectoutput);
		$categoryselectoutput = str_replace('<option value=".5">','<option value=".5" SELECTED>',$categoryselectoutput);
	} else {

		// Add the category selection. Selecting a category will override the search
		$categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', zen_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"');
	}
	echo "<tr class='dataTableRow'>".zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . 'oID='.$oID.'&action=add_prdct', 'post', '', true);
	echo "<td class='dataTableContent' align='right'><strong>" . ADDPRODUCT_TEXT_STEP1 . "</strong></td><td class='dataTableContent' valign='top'>";
	echo ' ' . $categoryselectoutput . ' OR ';
	echo HEADING_TITLE_SEARCH_DETAIL . ' ' . zen_draw_input_field('search', (isset($_POST['search']) && $add_product_categories_id <= 1) ? $_POST['search'] : '', 'onClick="this.form.add_product_categories_id.value=0;"') . zen_hide_session_id();
	echo "<input type='hidden' name='step' value='2'>";
	echo "</td>\n";
	echo "</form></tr>\n";
	echo "<tr><td colspan='3'>&nbsp;</td></tr>\n";

	// Step 2: Choose Product
	if($step > 1 && ($add_product_categories_id != .5 || zen_not_null($_POST['search']))) {
		$query =
			'SELECT `p`.`products_id`, `p`.`products_model`, `pd`.`products_name` ' .
			'FROM `' . TABLE_PRODUCTS . '` `p` ' .
			'LEFT JOIN `' . TABLE_PRODUCTS_DESCRIPTION . '` `pd` ON `pd`.`products_id`=`p`.`products_id` ';

		if($add_product_categories_id >= 1)
		{
			$query .=
				'LEFT JOIN `' . TABLE_PRODUCTS_TO_CATEGORIES . '` `ptc` ON `ptc`.`products_id`=`p`.`products_id` ' .
				'WHERE `pd`.`language_id` = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND `ptc`.`categories_id`=\'' . (int)$add_product_categories_id .'\' ORDER BY `p`.`products_id`';
		}
		else if(zen_not_null($_POST['search']))
		{
			// Handle case where a product search was entered
			$keywords = zen_db_input(zen_db_prepare_input($_POST['search']));

			$query .=
				'WHERE `pd`.`language_id` = \'' . (int)$_SESSION['languages_id'] . '\' ' .
				'AND ( ' .
					'`pd`.`products_name` LIKE \'%' . $keywords . '%\' OR ' .
					'`pd`.`products_description` LIKE \'%' . $keywords . '%\' OR ' .
					'`p`.`products_id` = \'' . $keywords . '\' OR ' .
					'`p`.`products_model` LIKE \'%' . $keywords . '%\' ' .
				') ORDER BY `p`.`products_id`';
		}

		echo "<tr class='dataTableRow'>".zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . 'oID='.$oID.'&action=add_prdct', 'post', '', true);
		echo "<td class='dataTableContent' align='right'><strong>" . ADDPRODUCT_TEXT_STEP2 . "</strong></td><td class='dataTableContent' valign='top'><select name=\"add_product_products_id\" onChange=\"this.form.submit();\">";

		$ProductOptions = "<option value='0'>" .  ADDPRODUCT_TEXT_SELECT_PRODUCT . "\n";
		$result = $db->Execute($query);
		while(!$result->EOF)
		{
			$ProductOptions .= '<option value="' . $result->fields['products_id'] .
				'">' . $result->fields['products_name'] .
				' [' . $result->fields['products_model'] . ']</option>' . PHP_EOL;
			$result->MoveNext();
		}
		$ProductOptions = str_replace(
			'value="' . $add_product_products_id . '"',
			'value="' . $add_product_products_id . '" selected',
			$ProductOptions
		);
		echo $ProductOptions;
		unset($ProductOptions);

		echo "</select></td>\n";
		echo "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
		echo "<input type='hidden' name='search' value='" . $_POST['search'] . "'>";
		echo "<input type='hidden' name='step' value='3'>";
		echo "</form></tr>\n";
		echo "<tr><td colspan='3'>&nbsp;</td></tr>\n";
	}

	// Step 3: Choose Options
	if(($step > 2) && ($add_product_products_id > 0))
	{
		// Skip to Step 4 if no Options
		if(!zen_has_product_attributes($add_product_products_id))
		{
			echo "<tr class=\"dataTableRow\">\n";
			echo "<td class='dataTableContent' align='right'><strong>" . ADDPRODUCT_TEXT_STEP3 . "</strong></td><td class='dataTableContent' valign='top' colspan='2'><i>".ADDPRODUCT_TEXT_OPTIONS_NOTEXIST."</i></td>";
			echo "</tr>\n";
			$step = 4;
		}
		else
		{
			$attrs = eo_get_product_attributes_options($add_product_products_id);

			echo "<tr class='dataTableRow'>".zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . 'oID='.$oID.'&action=add_prdct', 'post', '', true);
			echo "<td class='dataTableContent' align='right'><strong>" . ADDPRODUCT_TEXT_STEP3 . "</strong></td><td class='dataTableContent' valign='top'>";
			foreach($attrs as $optionID => $optionInfo)
			{
				switch($optionInfo['type']) {
					case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
					case PRODUCTS_OPTIONS_TYPE_RADIO:
					case PRODUCTS_OPTIONS_TYPE_SELECT:
						echo '<label class="attribsSelect" for="attrib-' . $optionID .
							'">' . $optionInfo['name'] . '</label>';
						$products_options_array = array();
						foreach($optionInfo['options'] as $attributeId => $attributeValue) {
							$products_options_array[] = array(
								'id' => $attributeId,
								'text' => $attributeValue
							);
						}
						$selected_attribute = $products_options_array[0]['id'];
						if(isset($_POST['id'][$optionID])) $selected_attribute = $_POST['id'][$optionID]['value'];
						echo zen_draw_pull_down_menu(
							'id[' . $optionID . '][value]', $products_options_array,
							$selected_attribute, 'id="attrib-' . $optionID . '"'
						) . "<br />\n";
						unset($products_options_array); unset($selected_attribute);
						unset($attributeId); unset($attributeValue);
						echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
						break;
					case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
						echo '<div class="attribsCheckboxGroup"><div class="attribsCheckboxName">' . $optionInfo['name'] . '</div>';
						foreach($optionInfo['options'] as $attributeId => $attributeValue) {
							$checked = (isset($_POST['id'][$optionID]['value'][$attributeId]) ? true : false);
							echo zen_draw_checkbox_field(
									'id[' . $optionID . '][value][' . $attributeId . ']',
									$attributeId, $checked, null, 'id="attrib-' .
									$optionID . '-' . $attributeId . '"'
								) . '<label class="attribsCheckbox" for="attrib-' .
								$optionID . '-' . $attributeId . '">' .
								$attributeValue . '</label><br />' . "\n";
						}
						unset($checked); unset($attributeId); unset($attributeValue);
						echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']) . '</div>';
						break;
					case PRODUCTS_OPTIONS_TYPE_TEXT:
						$text = (isset($_POST['id'][$optionID]['value']) ? $_POST['id'][$optionID]['value'] : '');
						$text = zen_html_quotes($text);
						echo '<label class="attribsInput" for="attrib-' . $optionID .
							'">' . $optionInfo['name'] . '</label>';
						if($optionInfo['rows'] > 1 ) {
							echo zen_draw_textarea_field(
								'id[' . $optionID . '][value]', 'hard',
								$optionInfo['size'], $optionInfo['rows'], $text,
								'class="attribsTextarea" id="attrib-' . $optionID . '"'
							) . "<br />\n";
						} else {
							echo zen_draw_input_field(
								'id[' . $optionID . '][value]', $text,
								'size="' . $optionInfo['size'] . '" maxlength="' .
									$optionInfo['size'] . '" id="attrib-' . $optionID . '"'
							) . "<br />\n";
						}
						echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
						break;
					case PRODUCTS_OPTIONS_TYPE_FILE:
						// TODO: Implement File Handling
						echo '<span class="attribsFile">' . $optionInfo['name'] .
						': FILE UPLOAD NOT SUPPORTED</span><br />';
						break;
					case PRODUCTS_OPTIONS_TYPE_READONLY:
					default:
						$optionValue = array_shift($optionInfo['options']);
						echo zen_draw_hidden_field('id[' . $optionID . '][value]', $optionValue) .
							'<span class="attribsRO">' . $optionInfo['name'] . ': ' .
							$optionValue . '</span><br />';
						unset($optionValue);
						echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
						break;
				}
			}
			echo "<td class='dataTableContent' align='center'><input type='submit' value='" . ADDPRODUCT_TEXT_OPTIONS_CONFIRM . "'>";
			echo "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
			echo "<input type='hidden' name='add_product_products_id' value='$add_product_products_id'>";
			echo "<input type='hidden' name='search' value='" . $_POST['search'] . "'>";
			echo "<input type='hidden' name='step' value='4'>";
			echo "</td>\n";
			echo "</form></tr>\n";
		}

		echo "<tr><td colspan='3'>&nbsp;</td></tr>\n";
	}

	// Step 4: Confirm
	if($step > 3)
	{
		echo "<tr class='dataTableRow'>".zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('action', 'oID')) . 'oID='.$oID.'&action=add_prdct', 'post', '', true);
		echo "<td class='dataTableContent' align='right'><strong>" . ADDPRODUCT_TEXT_STEP4 . "</strong></td>";
		echo "<td class='dataTableContent' valign='top'>" . ADDPRODUCT_TEXT_CONFIRM_QUANTITY . "<input name='add_product_quantity' size='2' value='1'>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='applyspecialstoprice' CHECKED>". ADDPRODUCT_SPECIALS_SALES_PRICE ."</td>";
		echo "<td class='dataTableContent' align='center'><input type='submit' value='" . ADDPRODUCT_TEXT_CONFIRM_ADDNOW . "'>";

		if($_POST['id'] != NULL)
		{
			foreach($_POST['id'] as $id => $value) {
				if(is_array($value)) {
					foreach($value as $id2 => $value2) {
						if(is_array($value2)) {
							foreach($value2 as $id3 => $value3) {
								echo '<input type="hidden" name="id[' . $id . '][' . $id2 .'][' . $id3 . ']" value="' . zen_html_quotes($value3) . '">';
							}
						}
						else {
							echo '<input type="hidden" name="id[' . $id . '][' . $id2 .']" value="' . zen_html_quotes($value2) . '">';
						}
					}
				}
				else {
					echo '<input type="hidden" name="id[' . $id . ']" value="' . zen_html_quotes($value) . '">';
				}
			}
		}
		echo "<input type='hidden' name='add_product_categories_id' value='$add_product_categories_id'>";
		echo "<input type='hidden' name='add_product_products_id' value='$add_product_products_id'>";
		echo "<input type='hidden' name='step' value='5'>";
		echo "</td>\n";
		echo "</form></tr>\n";
	}

	echo "</table></td></tr>\n";
}
?>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<script type="text/javascript">
	<!--
	handleShipping();
	function handleShipping() {
		if(document.getElementById('update_total_code') != undefined && document.getElementById('update_total_code').value == 'ot_shipping') {
			document.getElementById('update_total_shipping').style.display = 'table-cell';
		}
		else {
			document.getElementById('update_total_shipping').style.display = 'none';
		}
	}
	document.getElementById('update_total_code').onchange = function(){handleShipping();};
	// -->
</script>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php
require(DIR_WS_INCLUDES . 'application_bottom.php');