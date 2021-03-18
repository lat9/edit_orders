<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
//-Last modified 20210318-lat9 Edit Orders v4.6.0
//
// -----
// Prior to EO v4.6.0, this code was in-line in the main /admin/edit_orders.php script.  Now required by
// /admin/includes/modules/edit_orders/eo_edit_action_display.php in global context for the rendering of the
// current order's orders-status-history table.
//
$eo_href_link = zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['oID', 'action']) . "oID=$oID&amp;action=add_prdct");
$eo_add_product_button = zen_image_button('button_add_product.gif', TEXT_ADD_NEW_PRODUCT);
$eo_add_button_link = '<a href="' . $eo_href_link . '" class="btn btn-warning " role="button">' . TEXT_ADD_NEW_PRODUCT . '</a>';

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
