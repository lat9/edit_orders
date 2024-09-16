<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
// Last modified v5.0.0
//
// -----
// Prior to EO v4.6.0, this code was in-line in the main /admin/edit_orders.php script.  Now required by
// /admin/includes/modules/edit_orders/eo_edit_action_display.php in global context for the rendering of the
// current order's orders-status-history table.
//
$eo_href_link = zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['oID', 'action']) . "oID=$oID&action=add_prdct");
$eo_add_product_button = zen_image_button('button_add_product.gif', TEXT_ADD_NEW_PRODUCT);
$eo_add_button_link = '<a href="' . $eo_href_link . '" class="btn btn-warning btn-xs" role="button">' . TEXT_ADD_NEW_PRODUCT . '</a>';

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

$add_product_button = '<button id="add-product" class="btn btn-sm btn-warning">' . TEXT_ADD_NEW_PRODUCT . '</button>';

// Iterate over the order totals.
foreach ($order->totals as $next_total) {
    $ot_class = $next_total['class'];

    $update_total = "update_total[$ot_class]";
    $update_total_title = $update_total . '[title]';
    $update_total_value = $update_total . '[value]';

    $index_update_needed = true;
?>
<tr>
    <td class="dataTableContent" colspan="3"><?= $add_product_button ?></td>
<?php
    $add_product_button = '';

    $trimmed_title = strip_tags(trim($next_total['title']));

    $total_class = (in_array($ot_class, $display_only_totals)) ? 'display-only' : $ot_class;
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
    <td colspan="<?= $columns - 2 ?>">&nbsp;</td>
    <td class="text-right eo-label"><?= $trimmed_title ?></td>
    <td class="text-right eo-label"><?= $next_total['text'] ?></td>
<?php
            break;

        // Include these in the update but do not allow them to be changed
        case 'ot_group_pricing':
        case 'ot_cod_fee':
        case 'ot_loworderfee':
?>
    <td colspan="<?= $columns - 2 ?>">&nbsp;</td>
    <td class="text-right"><?= strip_tags($next_total['title']) . zen_draw_hidden_field($update_total_title, $trimmed_title) ?></td>
    <td class="text-right"><?= $next_total['text'] . zen_draw_hidden_field($update_total_value, $next_total['value']) ?></td>
<?php
            break;

        // Allow changing the title / text, but not the value. Typically used
        // for order total modules which handle the value based upon another condition
        case 'ot_coupon': 
?>
    <td colspan="<?= $columns - 2 ?>">&nbsp;</td>
    <td class="smallText text-right"><?= zen_draw_input_field($update_total_title, $trimmed_title, 'class="amount eo-entry"') ?></td>
    <td class="text-right"><?= $next_total['text'] . zen_draw_hidden_field($update_total_value, $next_total['value']) ?></td>
<?php
            break;

        case 'ot_shipping':
            $shipping_tax_rate = $eo->eoGetShippingTaxRate($order);
            $shipping_title_max = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'shipping_method') . '"';
?>
    <td class="text-right">
        <?= zen_draw_pull_down_menu($update_total . '[shipping_module]', eo_get_available_shipping_modules(), $order->info['shipping_module_code'], 'class="form-control me-2"') ?>
        <?= zen_draw_input_field($update_total_title, $trimmed_title, 'id="ship-title" class="form-control" ' . $shipping_title_max) ?>
    </td>

    <td>
        <div class="tax-percentage">&nbsp;%</div>
        <?= zen_draw_input_field('shipping_tax', (string)$shipping_tax_rate, 'id="ship-tax" class="amount form-control"' . $input_tax_params, false, $input_field_type) ?>
    </td>
<?php
            if (DISPLAY_PRICE_WITH_TAX === 'true') {
                $shipping_net = $next_total['value'] / (1 + ($shipping_tax_rate / 100));
?>
    <td>
        <?= zen_draw_input_field($update_total_value, (string)$shipping_net, 'id="ship-net" class="amount form-control"' . $input_value_params, false, $input_field_type) ?>
    </td>
<?php
                $update_total_value = 'shipping_gross';
            }
?>
    <td>&nbsp;</td>
    <td>
        <?= zen_draw_input_field($update_total_value, $next_total['value'], 'id="ship-gross" class="amount form-control"' . $input_value_params, false, $input_field_type) ?>
    </td>
<?php
            break;

        case 'ot_gv':
        case 'ot_voucher': 
?>
    <td colspan="<?= $columns - 2 ?>">&nbsp;</td>
    <td class="smallText text-right"><?= zen_draw_input_field($update_total_title, $trimmed_title, 'class="form-control eo-entry"') ?></td>
    <td class="smallText text-right">
<?php
            if ($next_total['value'] > 0) {
                $next_total['value'] *= -1;
            }
            echo zen_draw_input_field($update_total_value, $next_total['value'], 'class="amount form-control" step="any"', false, $input_field_type);
?>
    </td>
<?php
            break;

        default: 
?>
    <td colspan="<?= $columns - 2 ?>">&nbsp;</td>
    <td class="smallText text-right"><?= zen_draw_input_field($update_total_title, $trimmed_title, 'class="form-control eo-entry"') ?></td>
    <td class="smallText text-right"><?= zen_draw_input_field($update_total_value, $next_total['value'], 'class="amount form-control"') ?></td>
<?php
            break;
    }
?>
</tr>
<?php
}

$additional_totals_displayed = false;
$available_order_totals = eo_get_available_order_totals_class_values($oID);
if (count($available_order_totals) > 0) {
    $additional_totals_displayed = true;
?>
<tr>
    <td colspan="<?= $columns ?>">&nbsp;</td>
    <td><?= TEXT_ADD_ORDER_TOTAL . zen_draw_pull_down_menu('update_total[new_total][code]', $available_order_totals, '', 'id="update_total_code" class="form-control d-inline"') ?></td>
    <td><?= zen_draw_input_field('update_total[new_total][title]', '', 'class="form-control eo-entry"') ?></td>
    <td><?= zen_draw_input_field('update_total[new_total][value]', '', 'class="amount form-control" step="any"', false, $input_field_type) ?></td>
</tr>
<?php
/* Not sure what this block is supposed to do, commented out for now [FIXME]
<tr>
    <td colspan="<?= $columns + 3 ?>" class="d-none" id="update_total_shipping">
        <?= TEXT_CHOOSE_SHIPPING_MODULE . zen_draw_pull_down_menu('update_total[new_shipping][shipping_module]', eo_get_available_shipping_modules()) ?>
    </td>
</tr>
*/
?>
<?php
}
unset($total, $details);
