<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003-2024 The zen-cart developers
//
// Last modified v5.0.0
//

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
?>
<tr class="eo-ot <?= $ot_class ?>">
    <td class="dataTableContent" colspan="3"><?= $add_product_button ?></td>
<?php
    $add_product_button = '';

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
?>
    <td colspan="<?= $columns - 2 ?>"></td>
    <td class="text-right eo-label"><?= $trimmed_title ?></td>
    <td class="text-right eo-label"><?= $next_total['text'] ?></td>
<?php
            break;

        // Include these in the update but do not allow them to be changed
        case 'ot_group_pricing':
        case 'ot_cod_fee':
        case 'ot_loworderfee':
?>
    <td colspan="<?= $columns - 2 ?>"></td>
    <td class="text-right"><?= strip_tags($next_total['title']) ?></td>
    <td class="text-right"><?= $next_total['text'] ?></td>
<?php
            break;

        // Allow changing the title / text, but not the value. Typically used
        // for order total modules which handle the value based upon another condition
        case 'ot_coupon': 
?>
    <td colspan="<?= $columns - 1 ?>"></td>
    <td class="text-right">
        <button class="btn btn-sm btn-warning me-2 mb-1 btn-update"><?= IMAGE_UPDATE ?></button>
    </td>
    <td class="text-right">
        <?= zen_draw_input_field('title', $trimmed_title, 'class="eo-entry form-control"') ?>
    </td>
    <td class="text-right">
        <?= $next_total['text'] .
            zen_draw_hidden_field('value', $next_total['value'])
        ?>
    </td>
<?php
            break;

        case 'ot_shipping':
            $shipping_tax_rate = $eo->eoGetShippingTaxRate($order);
            $shipping_title_max = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'shipping_method') . '"';
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
?>
    <td class="text-right">
        <button class="btn btn-sm btn-warning me-2 mb-2 btn-update"><?= IMAGE_UPDATE ?></button>
        <?= zen_draw_pull_down_menu(
            'shipping_module',
            $available_modules,
            $order->info['shipping_module_code'],
            'id="shipping-select" class="eo-entry me-2 form-control"'
        ) ?>
        <?= zen_draw_input_field('title', $trimmed_title, 'id="ship-title" class="eo-entry form-control" ' . $shipping_title_max) ?>
    </td>

    <td>
        <div class="tax-percentage">&nbsp;%</div>
        <?= zen_draw_input_field('shipping_tax', (string)$shipping_tax_rate, 'id="ship-tax" class="amount form-control"' . $input_tax_params, false, $input_field_type) ?>
    </td>
<?php
            $value_name = 'value';
            if (DISPLAY_PRICE_WITH_TAX === 'true') {
                $shipping_net = $next_total['value'] / (1 + ($shipping_tax_rate / 100));
?>
    <td>
        <?= zen_draw_input_field('value', (string)$shipping_net, 'id="ship-net" class="amount form-control"' . $input_value_params, false, $input_field_type) ?>
    </td>
<?php
                $value_name = 'gross';
            }
?>
    <td></td>
    <td>
        <?= zen_draw_input_field($value_name, $next_total['value'], 'id="ship-gross" class="amount form-control"' . $input_value_params, false, $input_field_type) ?>
    </td>
<?php
            break;

        case 'ot_gv':
        case 'ot_voucher': 
?>
    <td colspan="<?= $columns - 1 ?>"></td>
    <td class="text-right">
        <button class="btn btn-sm btn-warning me-2 mb-1 btn-update"><?= IMAGE_UPDATE ?></button>
    </td>
    <td class="text-right">
        <?= zen_draw_input_field('title', $trimmed_title, 'class="eo-entry form-control"') ?>
    </td>
    <td class="text-right">
<?php
            if ($next_total['value'] > 0) {
                $next_total['value'] *= -1;
            }
            echo zen_draw_input_field('value', $next_total['value'], 'class="amount form-control" step="any"', false, $input_field_type);
?>
    </td>
<?php
            break;

        default:
?>
    <td colspan="<?= $columns - 1 ?>"></td>
    <td class="text-right">
        <button class="btn btn-sm btn-warning me-2 mb-1 btn-update"><?= IMAGE_UPDATE ?></button>
    </td>
    <td class="text-right">
        <?= zen_draw_input_field('title', $trimmed_title, 'class="eo-entry form-control"') ?>
    </td>
    <td class="text-right">
        <?= zen_draw_input_field('value', $next_total['value'], 'class="amount form-control"') ?>
    </td>
<?php
            break;
    }
?>
</tr>
<?php
}

$unused_order_totals = $eo->getUnusedOrderTotalModules($order);
if (count($unused_order_totals) !== 0) {
?>
<tr id="add-ot-wrapper">
    <td colspan="<?= $columns ?>">&nbsp;</td>
    <td>
        <button id="add-ot" class="btn btn-sm btn-warning me-2 mb-1"><?= TEXT_ADD_ORDER_TOTAL ?></button>
        <?= zen_draw_pull_down_menu('code', $unused_order_totals, '', 'class="form-control d-inline"') ?>
    </td>
    <td>
        <?= zen_draw_input_field('title', '', 'class="form-control"') ?>
    </td>
    <td>
        <?= zen_draw_input_field('value', '', 'id="add-ot-value" class="form-control" step="any"', false, $input_field_type) ?>
    </td>
</tr>
<?php
}
