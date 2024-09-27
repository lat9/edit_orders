<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019-2024, Vinos de Frutas Tropicales.
//
// Last modified v5.0.0
//
// This module is loaded in global scope by /admin/includes/modules/edit_orders/eo_edit_action_display.php.
//
// The following variables are set for the module's use:
//
// $address_icon ..... The name of the FA icon associated with the address' information.
// $address_label .... The label, e.g. "Customer" to associate with the address information.
// $address_name ..... The specific address, one of 'customer', 'billing' or 'delivery', that is being displayed.  This is
//                     used to create unique form-field names for the three variants of addresses.
// $address_fields ... An array of information, presumedly from the order-object (e.g. $address_fields), that
//                     contains the to-be-rendered field values.
// $address_notifier . The notification to be raised at the end of EO's standard address elements.
//
$modal_id = $address_name . '-modal';
$google_map_address = urlencode($address_fields['street_address'] . ',' . $address_fields['city'] . ',' . $address_fields['state'] . ',' . $address_fields['postcode']);
$google_map_link = 'https://maps.google.com/maps/search/?api=1&amp;query=' . $google_map_address;
?>
<div class="row my-2">
    <div class="panel panel-default">
        <div class="panel-heading">
            <i class="fa-2x <?= $address_icon ?>"></i> <span class="h3"><?= rtrim($address_label, ':') ?></span>
        </div>
        <div class="panel-body">
<?php
if ($address_name === 'delivery' && ($order->info['shipping_module_code'] === 'storepickup' || $order->content_type === 'virtual')) {
    $no_shipping_text = ($order->content_type === 'virtual') ? TEXT_VIRTUAL_NO_SHIP_ADDR : TEXT_STOREPICKUP_NO_SHIP_ADDR;
?>
            <p class="text-center"><?= $no_shipping_text ?></p>
<?php
} else {
?>
            <div class="col-md-6">
                <div class="btn-group btn-group-sm mt-2">
                    <a id="google-map-link-<?= $address_name ?>" href="<?= $google_map_link ?>" rel="noreferrer" target="map" role="button" class="btn btn-default me-2">
                        <i class="fa-regular fa-map"></i>&nbsp;<?= BUTTON_MAP_ADDRESS ?>
                    </a>
                    <br>
                    <button type="button" class="btn btn-info mt-1" data-toggle="modal" data-target="#<?= $modal_id ?>">
                        <?= ICON_EDIT ?>
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <address class="mb-0">
                    <div id="address-<?= $address_name ?>" class="eo-address border p-2">
                        <?= zen_address_format($address_fields['format_id'], $address_fields, 1, '', '<br>') ?>
                        <div class="mt-2">
                            <?= $address_fields['telephone'] ?? '&nbsp;' ?>
                            <br>
                            <?= $address_fields['email_address'] ?? '&nbsp;' ?>
                        </div>
                    </div>
                </address>
            </div>
<?php
}
?>
        </div>
    </div>
</div>

<div id="<?= $modal_id ?>" class="modal fade address-modal" role="dialog">
    <div class="modal-dialog"><form class="form-horizontal">
        <?= zen_draw_hidden_field($address_name . '_changed', '0', 'class="eo-changed"') ?>
        <?= zen_draw_hidden_field('address_type', $address_name, 'class="eo-addr-type"') ?>
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <?= sprintf(TEXT_MODAL_ADDRESS_HEADER, rtrim($address_label, ':')) ?>
                </h4>
            </div>
<?php
// -----
// Ensure any special characters in strings are 'sanitized' for the display.
//
foreach ($address_fields as $key => $value) {
    if (!is_array($value)) {
        $address_fields[$key] = zen_output_string_protected($value);
    }
}

// -----
// Set the common parameters for the tooltip popups.
//
$tooltip_parameters = 'class="fa-solid fa-circle-info fa-lg" data-toggle="tooltip" data-trigger="click" data-html="true"';
?>
            <div class="modal-body">
                <div class="form-group">
                    <?= zen_draw_label(ENTRY_CUSTOMER_COMPANY, $address_name . '_company', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'company',
                                $address_fields['company'],
                                $max_company_length . ' id="' . $address_name . '_company" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['company']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?= zen_draw_label(ENTRY_CUSTOMER_NAME, $address_name . '_name', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'name',
                                zen_output_string_protected($address_fields['name']),
                                $max_name_length . ' id="' . $address_name . '_name" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['name']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?= zen_draw_label(ENTRY_CUSTOMER_ADDRESS, $address_name . '_street_address', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'street_address',
                                zen_output_string_protected($address_fields['street_address']),
                                $max_street_address_length . ' id="' . $address_name . '_street_address" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['street_address']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?= zen_draw_label(ENTRY_CUSTOMER_SUBURB, $address_name . '_suburb', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'suburb',
                                zen_output_string_protected($address_fields['suburb']),
                                $max_suburb_length . ' id="' . $address_name . '_suburb" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['suburb']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group country-wrapper">
                    <?= zen_draw_label(ENTRY_CUSTOMER_COUNTRY, $address_name . '_country', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_get_country_list(
                                'country',
                                $address_fields['country']['id'],
                                'id="' . $address_name . '_country" class="form-control address-country"'
                            ) ?>
<?php
$country_name = zen_get_country_name((int)$address_fields['country']['id']);
?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $country_name) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>
<?php
if (ACCOUNT_STATE === 'true') {
    $zone_name = zen_get_zone_code((int)$address_fields['country_id'], (int)$address_fields['zone_id'], TEXT_UNKNOWN);
?>
                <div class="form-group state-wrapper">
                    <?= zen_draw_label(ENTRY_CUSTOMER_STATE, $address_name . '_state', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_pull_down_menu(
                                'zone_id',
                                zen_prepare_country_zones_pull_down($address_fields['country_id']),
                                $address_fields['zone_id'],
                                'id="' . $address_name . '_zone" class="form-control state-select"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $zone_name) ?>"></i>
                            </span>
                        </div>
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'state',
                                zen_output_string_protected($address_fields['state']),
                                $max_state_length . ' id="' . $address_name . '_state" class="form-control state-input"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['state']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>
<?php
}
?>
                <div class="form-group">
                    <?= zen_draw_label(ENTRY_CUSTOMER_CITY, $address_name . '_city', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'city',
                                zen_output_string_protected($address_fields['city']),
                                $max_city_length . ' id="' . $address_name . '_city" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['city']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?= zen_draw_label(ENTRY_CUSTOMER_POSTCODE, $address_name . '_postcode', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'postcode',
                                zen_output_string_protected($address_fields['postcode']),
                                $max_postcode_length . ' id="' . $address_name . '_postcode" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['postcode']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>
<?php
if (isset($address_fields['telephone'])) {
?>
                <div class="form-group">
                    <?= zen_draw_label(rtrim(ENTRY_TELEPHONE_NUMBER, ':'), $address_name . '_telephone', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= zen_draw_input_field(
                                'telephone',
                                zen_output_string_protected($address_fields['telephone']),
                                $max_telephone_length . ' id="' . $address_name . '_telephone" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['telephone']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>
<?php
}

if (isset($address_fields['email_address'])) {
?>
                <div class="form-group">
                    <?= zen_draw_label(rtrim(ENTRY_EMAIL_ADDRESS, ':'), $address_name . '_email_address', 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                           <?= zen_draw_input_field(
                                'email_address',
                                zen_output_string_protected($address_fields['email_address']),
                                $max_email_length . ' id="' . $address_name . '_email_address" class="form-control"'
                            ) ?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $address_fields['email_address']) ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>
<?php
}

// -----
// Now, issue the address-specific notification to allow other plugins to add fields to the
// associated address.
// 
// A watching observer can provide an associative array in the form, note that all fields are required:
//
// $extra_data = [
//     [
//         'label' => 'The text to include for the field label',
//         'field_id' => 'label "for" attribute, must match id of input field'
//         'input' => 'The form-field to be output, one per entry',
//     ],
//     ...
// ];
//
// Note: If the 'label' is specified as an empty string, the label tag isn't
// generated and the 'field_id' is not used. Observers can use this to insert
// hidden fields into the address form.
//
$additional_rows = [];
$zco_notifier->notify($address_notifier, $address_fields, $additional_rows);
$additional_labels = [];
foreach ($additional_rows as $next_row) {
    // -----
    // Try to locate the original value for the observer-supplied input.
    //
    // No tooltips for 'select' tags or checkbox-/radio-type inputs.
    //
    if (str_contains($next_row['input'], '<select ') || str_contains($next_row['input'], '"checkbox"') || str_contains($next_row['input'], '"radio"')) {
        $show_tooltip = false;
    // -----
    // Otherwise, grab the contents from the first 'value=' attribute. If no
    // 'value=' attribute is found, the original value is an empty string.
    //
    } else {
        $show_tooltip = true;
        $original_value = '';
        if (preg_match('/value="(\S*)"/', $next_row['input'], $matches) === 1) {
            $original_value = $matches[1];
        }
    }

    // -----
    // Now, try to determine the 'name' of the form field. If none can be
    // found, trigger an error and continue.
    //
    if (preg_match('/name="(\S*)"/', $next_row['input'], $matches) !== 1) {
        trigger_error('No name= attribute found for the input or multiple attributes were found, the element was not displayed: ' . json_encode($next_row), E_USER_WARNING);
        continue;
    }
    $form_var_name = $matches[1];
    $additional_labels[$form_var_name] = $next_row['label'];

    if ($next_row['label'] === '') {
        echo $next_row['input'];
        continue;
    }
?>
                <div class="form-group">
                    <?= zen_draw_label($next_row['label'], $next_row['field_id'], 'class="col-sm-3 control-label"') ?>
                    <div class="col-sm-9">
                        <div class="input-group">
                            <?= $next_row['input'] ?>
<?php
    if ($show_tooltip === true) {
?>
                            <span class="input-group-addon">
                                <i <?= $tooltip_parameters ?> title="<?= sprintf(TEXT_ORIGINAL_VALUE, $original_value) ?>"></i>
                            </span>
<?php
    }
?>
                        </div>
                    </div>
                </div>
<?php
}
?>
            </div>
<?php
// -----
// Save any additional-fields' labels into the session-based class that
// monitors the changes.
//
$_SESSION['eoChanges']->saveAdditionalAddressFieldLabels($address_name, $additional_labels);
?>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning btn-save d-none me-2"><?= IMAGE_SAVE ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
            </div>
        </div>
    </form></div>
</div>
