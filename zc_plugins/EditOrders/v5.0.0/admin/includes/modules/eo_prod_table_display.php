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
// The $price_is_manual variable is set by the base edit_orders.php on the EO entry
// page's initial rendering.  Follow-on invocations of this module are based on AJAX
// activity, where the current pricing-calculation method is provided by a POSTed
// variable.
//
$price_is_manual = $price_is_manual ?? (($_POST['payment_calc_method'] ?? 'Manual') === 'Manual');

// -----
// Initialize (outside of the loop, for performance) the attributes for the various product-related
// input fields.
//
$name_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '"';
$model_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '"';
foreach ($order->products as $next_product) {
    $orders_products_id = $next_product['orders_products_id'];
?>
            <tr class="eo-prod dataTableRow">
<?php
    // -----
    // To add more columns at the beginning of the order's products' table, a
    // watching observer can provide an associative array in the form:
    //
    // $extra_data = [
    //     [
    //       'align' => $alignment,    // One of 'center', 'right' or 'left' (optional)
    //       'text' => $value
    //     ],
    // ];
    //
    // Observer note:  Be sure to check that the $p2/$extra_data value is specifically (bool)false before initializing, since
    // multiple observers might be injecting content!
    //
    $extra_data = false;
    $zco_notifier->notify('NOTIFY_EDIT_ORDERS_PRODUCTS_DATA_1', $next_product, $extra_data);
    if (is_array($extra_data)) {
        foreach ($extra_data as $data) {
            $align = '';
            if (isset($data['align'])) {
                switch ($data['align']) {
                    case 'center':
                        $align = ' text-center';
                        break;
                    case 'right':
                        $align = ' text-right';
                        break;
                    default:
                        $align = '';
                        break;
                }
            }
?>
                <td class="dataTableContent<?= $align ?>"><?= $data['text'] ?></td>
<?php
        }
    }

    $price_entry_disabled = ($price_is_manual === true) ? '' : 'disabled';
?>
                <td class="dataTableContent text-center">
                    <button class="eo-btn-prod-edit btn btn-sm btn-info mt-2 mx-2" data-opi="<?= $orders_products_id ?>">
                        <?= ICON_EDIT ?>
                    </button>
                </td>

                <td class="dataTableContent text-right">
                    <?= $next_product['qty'] ?>&nbsp;X&nbsp;
                </td>

                <td class="dataTableContent">
                    <?= $next_product['model'] ?>
                </td>

                <td class="dataTableContent">
                    <?= $next_product['name'] ?>
<?php
    if (isset($next_product['attributes'])) {
?>
                    <div class="row">
                        <small>&nbsp;<i><?= TEXT_ATTRIBUTES_ONE_TIME_CHARGE ?></i></small>
                        <?= $currencies->format($next_product['onetime_charges'], true, $order->info['currency'], $order->info['currency_value']) ?>
                    </div>

                    <ul class="attribs-list">
<?php
        foreach ($next_product['attributes'] as $next_attribute) {
?>
                        <li><?= $next_attribute['option'] . ': ' . nl2br(zen_output_string_protected($next_attribute['value'])) ?></li>
<?php
        }
?>
                    </ul>
<?php
    }
?>
                </td>
<?php
    // -----
    // Starting with EO v4.4.0, both the net and gross prices are displayed when the store displays prices with tax.
    //
    $final_price = $next_product['final_price'];
    $onetime_charges = $next_product['onetime_charges'];
?>
                <td class="dataTableContent text-right">
                    <?= zen_display_tax_value($next_product['tax']) ?>%
                </td>

                <td class="dataTableContent text-right">
                    <?= $currencies->format($final_price, true, $order->info['currency'], $order->info['currency_value']) ?>
                </td>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $next_product['tax']);
        $final_price = $gross_price;
?>
                <td class="dataTableContent text-right">
                    <?= $gross_price ?>
                </td>
<?php
    }
?>
                <td class="dataTableContent text-right">
                    <?= $currencies->format(($final_price * $next_product['qty']) + $onetime_charges, true, $order->info['currency'], $order->info['currency_value']) ?>
                </td>
            </tr>
<?php
}
