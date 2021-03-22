<?php
// -----
// Part of the Edit Orders plugin, v4.5.0 and later, provided by lat9.
// Copyright 2019-2021, Vinos de Frutas Tropicales.
//
//-Last modified 20210321-lat9 Edit Orders v4.6.0
//
// This module is loaded in global scope by /admin/includes/modules/edit_orders/eo_edit_action_display.php.
//
// The following variables are set for the module's use:
//
// $address_icon ..... The name of the icon associated with the address' information.
// $address_label .... The label, e.g. "Customer" to associate with the address information.
// $address_name ..... The specific address, e.g. 'customer' or 'billing', that is being displayed.  This is
//                     used to create unique form-field names for the three variants of addresses.
// $address_fields ... An array of information, presumedly from the order-object (e.g. $address_fields), that
//                     contains the to-be-rendered field values.
// $address_notifier . The notification to be raised at the end of EO's standard address elements.
//
?>
<table class="table">
    <tr>
        <td><?php echo zen_image(DIR_WS_IMAGES . $address_icon, $address_label); ?></td>
        <td class="eo-label"><?php echo $address_label; ?></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_NAME; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_name" size="45" value="<?php echo zen_output_string_protected($address_fields['name']); ?>" <?php echo $max_name_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_COMPANY; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_company" size="45" value="<?php echo zen_output_string_protected($address_fields['company']); ?>" <?php echo $max_company_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_ADDRESS; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_street_address" size="45" value="<?php echo zen_output_string_protected($address_fields['street_address']); ?>" <?php echo $max_street_address_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_SUBURB; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_suburb" size="45" value="<?php echo zen_output_string_protected($address_fields['suburb']); ?>" <?php echo $max_suburb_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_CITY; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_city" size="45" value="<?php echo zen_output_string_protected($address_fields['city']); ?>" <?php echo $max_city_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_STATE; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_state" size="45" value="<?php echo zen_output_string_protected($address_fields['state']); ?>" <?php echo $max_state_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_POSTCODE; ?>:&nbsp;</td>
        <td><input name="update_<?php echo $address_name; ?>_postcode" size="45" value="<?php echo zen_output_string_protected($address_fields['postcode']); ?>" <?php echo $max_postcode_length; ?>></td>
    </tr>

    <tr>
        <td class="eo-label"><?php echo ENTRY_CUSTOMER_COUNTRY; ?>:&nbsp;</td>
        <td>
<?php
if (is_array($address_fields['country']) && isset($address_fields['country']['id'])) {
    echo zen_get_country_list('update_' . $address_name . '_country', $address_fields['country']['id']);
} else {
    echo '<input name="update_' . $address_name . '_country" size="45" value="' . zen_output_string_protected($address_fields['country']) . '"' . $max_country_length . '">';
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
$zco_notifier->notify($address_notifier, null, $additional_rows);
if (!empty($additional_rows)) {
    foreach ($additional_rows as $next_row) {
?>
    <tr>
        <td class="eo-label"><?php echo $next_row['label']; ?>:&nbsp;</td>
        <td><?php echo $next_row['value']; ?></td>
    </tr>
<?php
    }
}
?>
</table>

