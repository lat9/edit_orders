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
$input_prefix = 'update_' . $address_name;
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
                    <div id="address-<?= $address_name ?>" class="eo-address p-2">
                        <?= zen_address_format($address_fields['format_id'], $address_fields, 1, '', '<br>') ?>
                    </div>
                    <div class="mt-2">
                        <?= $address_fields['telephone'] ?? '&nbsp;' ?>
                        <br>
                        <?= $address_fields['email_address'] ?? '&nbsp;' ?>
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
    <div class="modal-dialog"><form>
        <?= zen_draw_hidden_field($input_prefix . '_changed', '0', 'class="eo-changed"') ?>
        <?= zen_draw_hidden_field('address_type', $address_name, 'class="eo-addr-type"') ?>
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <?= sprintf(TEXT_MODAL_ADDRESS_HEADER, rtrim($address_label, ':')) ?>
                </h4>
            </div>

            <div class="modal-body">
                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_company"><?= ENTRY_CUSTOMER_COMPANY ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_company',
                            zen_output_string_protected($address_fields['company']),
                            $max_company_length . ' id="' . $input_prefix . '_company" class="form-control"'
                        ) ?>
                    </div>
                </div>

                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_name"><?= ENTRY_CUSTOMER_NAME ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_name',
                            zen_output_string_protected($address_fields['name']),
                            $max_name_length . ' id="' . $input_prefix . '_name" class="form-control"'
                        ) ?>
                    </div>
                </div>

                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_address"><?= ENTRY_CUSTOMER_ADDRESS ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_street_address',
                            zen_output_string_protected($address_fields['street_address']),
                            $max_street_address_length . ' id="' . $input_prefix . '_street_address" class="form-control"'
                        ) ?>
                    </div>
                </div>

                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_suburb"><?= ENTRY_CUSTOMER_SUBURB ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_suburb',
                            zen_output_string_protected($address_fields['suburb']),
                            $max_suburb_length . ' id="' . $input_prefix . '_suburb" class="form-control"'
                        ) ?>
                    </div>
                </div>

                <div class="row my-2 country-wrapper">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_country"><?= ENTRY_CUSTOMER_COUNTRY ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_get_country_list(
                            'update_' . $address_name . '_country',
                            $address_fields['country']['id'],
                            'id="update_' . $address_name . '_country" class="form-control address-country"'
                        ) ?>
                    </div>
                </div>
<?php
if (ACCOUNT_STATE === 'true') {
?>
                <div class="row my-2 state-wrapper">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_state"><?= ENTRY_CUSTOMER_STATE ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_pull_down_menu(
                            $input_prefix . '_zone_id',
                            zen_prepare_country_zones_pull_down($address_fields['country_id']),
                            $address_fields['zone_id'], 'id="' . $input_prefix . '_zone" class="form-control state-select"'
                        ) ?>

                        <?= zen_draw_input_field(
                            $input_prefix . '_state',
                            zen_output_string_protected($address_fields['state']),
                            $max_state_length . ' id="' . $input_prefix . '_state" class="form-control state-input"'
                        ) ?>
                    </div>
                </div>
<?php
}
?>
                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_city"><?= ENTRY_CUSTOMER_CITY ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_city',
                            zen_output_string_protected($address_fields['city']),
                            $max_city_length . ' id="' . $input_prefix . '_city" class="form-control"'
                        ) ?>
                    </div>
                </div>

                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_postcode"><?= ENTRY_CUSTOMER_POSTCODE ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_postcode',
                            zen_output_string_protected($address_fields['postcode']),
                            $max_postcode_length . ' id="' . $input_prefix . '_postcode" class="form-control"'
                        ) ?>
                    </div>
                </div>
<?php
if (isset($address_fields['telephone'])) {
?>

                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_telephone"><?= rtrim(ENTRY_TELEPHONE_NUMBER, ':') ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_telephone',
                            zen_output_string_protected($address_fields['telephone']),
                            $max_telephone_length . ' id="' . $input_prefix . '_telephone" class="form-control"'
                        ) ?>
                    </div>
                </div>
<?php
}

if (isset($address_fields['email_address'])) {
?>
                <div class="row my-2">
                    <div class="col-sm-3 control-label">
                        <label for="<?= $input_prefix ?>_email_address"><?= rtrim(ENTRY_EMAIL_ADDRESS, ':') ?></label>:&nbsp;
                    </div>
                    <div class="col-sm-9">
                        <?= zen_draw_input_field(
                            $input_prefix . '_email_address',
                            zen_output_string_protected($address_fields['email_address']),
                            $max_email_length . ' id="' . $input_prefix . '_email_address" class="form-control"'
                        ) ?>
                    </div>
                </div>
<?php
}

// -----
// Now, issue the address-specific notification to allow other plugins to add fields to the
// associated address.
// 
// A watching observer can provide an associative array in the form:
//
// $extra_data = array(
//     array(
//       'label' => 'label_name',   //-No trailing ':', that will be added by EO.
//       'value' => $value          //-This is the form-field to be added
//     ),
// );
//
$additional_rows = [];
$zco_notifier->notify($address_notifier, $address_fields, $additional_rows);
if (!empty($additional_rows)) {
    foreach ($additional_rows as $next_row) {
?>
                <div class="row my-2">
                    <div class="col-sm-3 control-label"><?= $next_row['label'] ?></label>:&nbsp;</div>
                    <div class="col-sm-9"><?= $next_row['value'] ?></div>
                </div>
<?php
    }
}
?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-warning btn-save d-none me-2"><?= IMAGE_SAVE ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
            </div>
        </div>
    </form></div>
</div>
