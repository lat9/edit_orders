<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003-2024 The zen-cart developers
//
// Last modified v5.0.0
//
// Declaring the EditOrders.php class' instance as global, since this module is also
// used during AJAX processing when an order-total field is updated.
//
global $eo, $zco_notifier;

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
$display_only_totals = [];
if (!empty($display_only_totals_list)) {
    $eo->eoLog('Display-only totals identified: ' . json_encode($display_only_totals_list));
    $display_only_totals = explode(',', str_replace(' ', '', (string)$display_only_totals_list));
}

// -----
// The number of columns displayed in this section depends on whether/not the store displays prices
// with tax.  If so, both the net- and gross-prices are displayed; otherwise, simply the net.
//
$columns = ((DISPLAY_PRICE_WITH_TAX === 'true') ? 7 : 6) - 2;

$add_product_button = '<button id="add-product" class="btn btn-sm btn-info">' . TEXT_ADD_NEW_PRODUCT . '</button>';

$add_ot_button = '';
$unused_order_totals = $eo->getUnusedOrderTotalModules($order);
if (count($unused_order_totals) !== 0) {
    $add_ot_button =
        '<button id="eo-add-ot" class="btn btn-sm btn-info me-2 mb-1">' . BUTTON_ADD . '</button>' .
        zen_draw_pull_down_menu('code', $unused_order_totals, '', 'id="eo-add-ot-code" class="form-control d-inline"');
}

// -----
// Retrieve the previously gathered order total classes for the order.
//
$order_totals = $eo->getOrderTotalsObject();

// Iterate over the order totals.
foreach ($order->totals as $next_total) {
    $ot_class = $next_total['class'] ?? $next_total['code'];
?>
<tr class="eo-ot">
    <td class="dataTableContent">
        <?= $add_product_button ?>
    </td>
    <td class="dataTableContent" colspan="2">
        <?= $add_ot_button ?>
    </td>
<?php
    $add_product_button = '';
    $add_ot_button = '';

    $trimmed_title = strip_tags(trim($next_total['title']));

    $total_class = (in_array($ot_class, $display_only_totals)) ? 'display-only' : $ot_class;
    switch ($total_class) {
        case 'ot_purchaseorder':
            break;

        // Automatically generated fields, they're displayed but cannot be directly changed.
        case 'ot_subtotal':
        case 'ot_total':
        case 'ot_tax':
        case 'ot_local_sales_taxes':
        case 'display-only':
        case 'ot_group_pricing':
        case 'ot_cod_fee':
        case 'ot_loworderfee':
        case 'ot_gv':
        case 'ot_voucher':
?>
    <td colspan="<?= $columns - 2 ?>"></td>
    <td class="text-right eo-label"><?= $trimmed_title ?></td>
    <td class="text-right eo-label"><?= $next_total['text'] ?></td>
<?php
            break;

        // Allow changing the title / text, but not the value. Typically used
        // for order total modules which handle the value based upon another condition
        case 'ot_coupon': 
?>
    <td colspan="<?= $columns - 3 ?>"></td>
    <td class="text-right">
        <button class="btn btn-sm btn-info me-2 mb-2 eo-btn-ot-edit" data-ot-class="ot_coupon">
            <?= ICON_EDIT ?>
        </button>
    </td>
    <td class="text-right eo-label"><?= $trimmed_title ?></td>
    <td class="text-right eo-label"><?= $next_total['text'] ?></td>
<?php
            break;

        case 'ot_shipping':
            $shipping_tax_rate = $eo->eoGetShippingTaxRate($order);
            $available_modules = $eo->getAvailableShippingModules($order);

            // -----
            // If no available modules, just render an empty row; EO's jQuery
            // will hide the entire row.
            //
            if (count($available_modules) === 0) {
?>
    <td id="eo-no-shipping" colspan="<?= $columns ?>"></td>
<?php
                break;
            }

            // -----
            // Otherwise, display the shipping-module dropdown for selection.
            //
            $shipping_module_code = $order->info['shipping_module_code'];
            if (strpos($shipping_module_code, '_') !== false) {
                $shipping_module_code = substr($shipping_module_code, 0, strpos($shipping_module_code, '_'));
            }
?>
    <td class="text-right">
        <button class="btn btn-sm btn-info me-2 mb-2 eo-btn-ot-edit" data-ot-class="ot_shipping">
            <?= ICON_EDIT ?>
        </button>
    </td>

    <td>
        <div class="tax-percentage"><?= $shipping_tax_rate ?>%</div>
    </td>
<?php
            if (DISPLAY_PRICE_WITH_TAX === 'true') {
                $shipping_net = $next_total['value'] / (1 + ($shipping_tax_rate / 100));
?>
    <td>
        <?= (string)$shipping_net ?>
    </td>
<?php
            }
?>
    <td class="text-right eo-label">
        <?= $trimmed_title ?>
    </td>
    <td class="text-right eo-label">
        <?= $next_total['text'] ?>
    </td>
<?php
            break;

        default:
            if (empty($GLOBALS[$total_class]->eoCanBeAdded)) {
?>
    <td colspan="<?= $columns - 2 ?>"></td>
 
<?php
            } else {
?>
   <td colspan="<?= $columns - 3 ?>"></td>
    <td class="text-right">
        <button class="btn btn-sm btn-info me-2 mb-2 eo-btn-ot-edit" data-ot-class="<?= $total_class ?>">
            <?= ICON_EDIT ?>
        </button>
    </td>
<?php
            }
?>
    <td class="text-right eo-label"><?= $trimmed_title ?></td>
    <td class="text-right eo-label"><?= $next_total['text'] ?></td>
<?php
            break;
    }
?>
</tr>
<?php
}
