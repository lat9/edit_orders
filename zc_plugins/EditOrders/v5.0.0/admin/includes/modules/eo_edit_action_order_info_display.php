<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003-2025 The zen-cart developers
//
// Last modified v5.0.0
//
?>
<details class="panel panel-default">
    <summary class="panel-heading">
        <span class="h3"><?= TEXT_PANEL_HEADER_ADDL_INFO ?></span>
    </summary>
    <div class="panel-body">
<?php
// -----
// Give a watching observer the opportunity to supply additional contact-information for the order.
//
// The $additional_contact_info (supplied as the notification's 2nd parameter), if supplied, is a
// numerically-indexed array of arrays containing each label and associated content, e.g.:
//
// $additional_contact_info[] = [
//     'label' => LABEL_TEXT,
//     'content' => $field_content,
// ];
//
// Note: These inputs, unlike prior EO versions, are read-only, and not rendered within a form.
//
// For EO versions prior to 5.0.0, this notification was 'EDIT_ORDERS_ADDITIONAL_CONTACT_INFORMATION'.
//
$additional_contact_info = [];
$zco_notifier->notify('NOTIFY_EO_ADDL_CONTACT_INFO', $order, $additional_contact_info);

if (is_array($additional_contact_info) && count($additional_contact_info) !== 0) {
    foreach ($additional_contact_info as $contact_info) {
        if (!empty($contact_info['label']) && !empty($contact_info['content'])) {
?>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= $contact_info['label'] ?></div>
            <div class="col-sm-8"><?= $contact_info['content'] ?></div>
        </div>
<?php
        }
    }
}

// -----
// Note: Using loose comparison since the value is recorded (currently) as decimal(14,6)
// and shows up in the order as (string)1.000000 if the order's placed in the store's
// default currency.
//
if ($order->info['currency_value'] != 1) {
?>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= sprintf(ENTRY_CURRENCY_VALUE, $order->info['currency']) ?></div>
            <div class="col-sm-8"><?= $order->info['currency_value'] ?></div>
        </div>
<?php
}
?>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_IS_GUEST_ORDER ?></div>
            <div class="col-sm-8"><?= ($_SESSION['eoChanges']->isGuestCheckout() === true) ? TEXT_YES : TEXT_NO ?></div>
        </div>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_IS_WHOLESALE ?></div>
            <div class="col-sm-8">
<?php
switch ($order->info['is_wholesale'] ?? '2') {
    case '1':
        echo TEXT_YES;
        break;
    case '0':
        echo TEXT_NO;
        break;
    default:
        echo TEXT_UNKNOWN;
        break;
}
?>
            </div>
        </div>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_CUSTOMER_WHOLESALE ?></div>
            <div class="col-sm-8">
                <?= (Customer::isWholesaleCustomer() === true) ? (TEXT_YES . ' (' . Customer::getCustomerWholesaleTier() . ')'): TEXT_NO ?>
            </div>
        </div>
        <div class="row mt-2 mb-4">
            <div class="col-sm-4 fw-bold"><?= ENTRY_CUSTOMER_TAX_EXEMPT ?></div>
            <div class="col-sm-8"><?= (Customer::isTaxExempt() === true) ? TEXT_YES : TEXT_NO ?></div>
        </div>

        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_PAYMENT_MODULE ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected($order->info['payment_module_code']) ?></div>
        </div>
        <div class="row my-2">
            <div class="form-group">
                <label class="control-label col-sm-4" for="pymt-method"><?= ENTRY_PAYMENT_METHOD ?></label>
                <div class="col-sm-8">
                    <?= zen_draw_input_field('payment_method', $order->info['payment_method'], 'id="pymt-method" class="form-control"') ?>
                    <?= zen_draw_hidden_field('pm_changed', '0', 'id="pm-changed" class="eo-changed"') ?>
                </div>
            </div>
        </div>
<?php
if (!empty($order->info['cc_type']) || !empty($order->info['cc_owner']) || !empty($order->info['cc_number'])) {
?>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_CREDIT_CARD_TYPE ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected((string)$order->info['cc_type']) ?></div>
        </div>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_CREDIT_CARD_OWNER ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected((string)$order->info['cc_owner']) ?></div>
        </div>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_CREDIT_CARD_NUMBER ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected($order->info['cc_number']) ?></div>
        </div>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_CREDIT_CARD_EXPIRES ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected($order->info['cc_expires']) ?></div>
        </div>
<?php
}

if (!empty($order->info['account_name']) || !empty($order->info['account_number']) || !empty($order->info['po_number'])) {
?>
        <hr>
<?php
}

if (!empty($order->info['account_name'])) {
?>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_ACCOUNT_NAME ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected($order->info['account_name']) ?></div>
        </div>
<?php
}
if (!empty($order->info['account_number'])) {
?>
        <div class="row my-2">
            <div class="col-sm-4 fw-bold"><?= ENTRY_ACCOUNT_NUMBER ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected($order->info['account_number']) ?></div>
        </div>
<?php
}
if (!empty($order->info['po_number'])) {
?>
        <div class="row my-2">
            <div class="col-sm-4 eo-label"><?= ENTRY_PURCHASE_ORDER_NUMBER ?></div>
            <div class="col-sm-8"><?= zen_output_string_protected($order->info['po_number']) ?></div>
        </div>
<?php
}
?>
    </div>
</details>