<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019-2024, Vinos de Frutas Tropicales.
//
//-Last modified v4.7.1
//
// This module is loaded in global scope by /admin/includes/modules/edit_orders/eo_edit_action_display.php.
//
// The following variables are set for the module's use:
//
// $address_icon ..... The name of the FA icon associated with the address' information.
// $address_label .... The label, e.g. "Customer" to associate with the address information.
// $address_name ..... The specific address, e.g. 'customer' or 'billing', that is being displayed.  This is
//                     used to create unique form-field names for the three variants of addresses.
// $address_fields ... An array of information, presumedly from the order-object (e.g. $address_fields), that
//                     contains the to-be-rendered field values.
// $address_notifier . The notification to be raised at the end of EO's standard address elements.
//
?>
<div role="group" aria-labelledby="sr-<?= $address_name ?>">
<?php
// -----
// Add a hidden field containing the JSON-encoded version of the currently-displayed address.  Used
// on an update_order action to see if any of the addresses have changed.
//
echo zen_draw_hidden_field('existing-' . $address_name, $eo->arrayImplode($address_fields));
?>
    <table class="table">
        <tr>
            <td aria-hidden="true"><i class="fa-2x <?= $address_icon ?>"></i></td>
            <td class="eo-label" id="sr-<?= $address_name ?>" role="heading" aria-level="2"><?= $address_label ?></td>
        </tr>
        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_name"><?= ENTRY_CUSTOMER_NAME ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_name" size="45" value="<?= zen_output_string_protected($address_fields['name']) ?>" <?= $max_name_length ?> id="update_<?= $address_name ?>_name"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_company"><?= ENTRY_CUSTOMER_COMPANY ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_company" size="45" value="<?= zen_output_string_protected($address_fields['company']) ?>" <?= $max_company_length ?> id="update_<?= $address_name ?>_company"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_address"><?= ENTRY_CUSTOMER_ADDRESS ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_street_address" size="45" value="<?= zen_output_string_protected($address_fields['street_address']) ?>" <?= $max_street_address_length ?> id="update_<?= $address_name ?>_address"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_suburb"><?= ENTRY_CUSTOMER_SUBURB ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_suburb" size="45" value="<?= zen_output_string_protected($address_fields['suburb']) ?>" <?= $max_suburb_length ?> id="update_<?= $address_name ?>_suburb"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_city"><?= ENTRY_CUSTOMER_CITY ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_city" size="45" value="<?= zen_output_string_protected($address_fields['city']) ?>" <?= $max_city_length ?> id="update_<?= $address_name ?>_city"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_state"><?= ENTRY_CUSTOMER_STATE ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_state" size="45" value="<?= zen_output_string_protected($address_fields['state']) ?>" <?= $max_state_length ?> id="update_<?= $address_name ?>_state"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_postcode"><?= ENTRY_CUSTOMER_POSTCODE ?></label>:&nbsp;</td>
            <td><input name="update_<?= $address_name ?>_postcode" size="45" value="<?= zen_output_string_protected($address_fields['postcode']) ?>" <?= $max_postcode_length ?> id="update_<?= $address_name ?>_postcode"></td>
        </tr>

        <tr>
            <td class="eo-label"><label for="update_<?= $address_name ?>_country"><?= ENTRY_CUSTOMER_COUNTRY ?></label>:&nbsp;</td>
            <td>
<?php
if (is_array($address_fields['country']) && isset($address_fields['country']['id'])) {
    echo zen_get_country_list('update_' . $address_name . '_country', $address_fields['country']['id'], 'id="update_' . $address_name . '_country"');
} else {
    echo '<input name="update_' . $address_name . '_country" size="45" value="' . zen_output_string_protected($address_fields['country']) . '"' . $max_country_length . '" id="update_"' . $address_name . '_country">';
} 
?>
            </td>
        </tr>
<?php
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
        <tr>
            <td class="eo-label"><?= $next_row['label'] ?></label>:&nbsp;</td>
            <td><?= $next_row['value'] ?></td>
        </tr>
<?php
    }
}
?>
    </table>
</div>
