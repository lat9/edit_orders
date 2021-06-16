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
// that script in global context for its 'add_prdct' action.
//
$redirect_required = false;
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
    $attributes = (isset($_POST['id'])) ? zen_db_prepare_input($_POST['id']) : [];
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
            $total['sort_order'] = 0;

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

    // -----
    // Let the main script 'know' that a redirect is required.
    //
    $redirect_required = true;
}
