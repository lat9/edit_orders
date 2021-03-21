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
// that script in global context for the rendering for its 'edit' action.
//
if ($order->info['payment_module_code']) {
    if (file_exists(DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php')) {
        require DIR_FS_CATALOG_MODULES . 'payment/' . $order->info['payment_module_code'] . '.php';
        require DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/modules/payment/' . $order->info['payment_module_code'] . '.php';
        $module = new $order->info['payment_module_code'];
    }
}

// BEGIN - Add Super Orders Order Navigation Functionality
require DIR_WS_MODULES . 'edit_orders/eo_navigation.php';
// END - Add Super Orders Order Navigation Functionality
?>
<!-- body //-->
<table class="eo-table">
    <tr>
<!-- body_text //-->
        <td class="w100 v-top"><table class="eo-table">
            <tr>
                <td class="w100"><table class="w100">
                    <tr>
                        <td class="pageHeading"><?php echo HEADING_TITLE; ?> #<?php echo $oID; ?></td>
                        <td class="pageHeading a-r"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                        <td class="pageHeading a-r">
                            <a href="<?php echo zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(['action'])); ?>" class="btn btn-primary btn-sm" role="button"><?php echo IMAGE_BACK; ?></a>
                            <a href="<?php echo zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(['oID', 'action']) . "oID=$oID&amp;action=edit"); ?>" class="btn btn-primary btn-sm" role="button"><?php echo DETAILS; ?></a>
                        </td>
                    </tr>
                </table></td>
            </tr>

<!-- Begin Addresses Block -->
            <tr>
                <td><?php echo zen_draw_form('edit_order', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'paycc']) . 'action=update_order'); ?><table width="100%" border="0">
                    <tr>
                        <td><table role="table" class="w100" id="c-form">
<?php
// -----
// Gather the maximum database field-length for each of the address-related fields in the
// order, noting that the ASSUMPTION is made that each of the customer/billing/delivery fields
// are of equal length!
//
$max_name_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_name') . '"';
$max_company_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_company') . '"';
$max_street_address_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_street_address') . '"';
$max_suburb_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_suburb') . '"';
$max_city_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_city') . '"';
$max_state_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_state') . '"';
$max_postcode_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_postcode') . '"';
$max_country_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_country') . '"';

// -----
// Starting with EO v4.5.0, a store can indicate the display-order for the order-related addresses.  Rather
// than including all that code here, we'll use a separate 'module' to display the Customer-Shipping-Billing vs.
// Customer-Billing-Shipping version.
//
$module_name = (EO_ADDRESSES_DISPLAY_ORDER == 'CBS') ? 'eo_addresses_cbs.php' : 'eo_addresses_csb.php';
require DIR_WS_MODULES . 'edit_orders/' . $module_name;

// -----
// Give a watching observer the chance to inject some additional, per-address-type, information.
//
$additional_rows = '';
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_ADDRESS_ROWS', $order, $additional_rows);
echo $additional_rows;
?>

                        </table></td>
                    </tr>

<!-- End Addresses Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Phone/Email Block -->
<?php
$max_telephone_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_telephone') . '"';
$max_email_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'customers_email_address') . '"';

// -----
// Give a watching observer the opportunity to supply additional contact-information for the order.
//
// The $additional_contact_info (supplied as the notification's 2nd parameter), if supplied, is a
// numerically-indexed array of arrays containing each label and associated content, e.g.:
//
// $additional_contact_info[] = ['label' => LABEL_TEXT, 'content' => $field_content];
//
$additional_contact_info = [];
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_CONTACT_INFORMATION', $order, $additional_contact_info);
?>
                    <tr>
                        <td><table class="eo-pad">
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
                                <td class="main"><input name="update_customer_telephone" size="15" value="<?php echo zen_output_string_protected($order->customer['telephone']); ?>" <?php echo $max_telephone_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_EMAIL_ADDRESS; ?></td>
                                <td class="main"><input name="update_customer_email_address" size="35" value="<?php echo zen_output_string_protected($order->customer['email_address']); ?>" <?php echo $max_email_length; ?>></td>
                            </tr>
<?php
if (is_array($additional_contact_info) && count($additional_contact_info) != 0) {
    foreach ($additional_contact_info as $contact_info) {
        if (!empty($contact_info['label']) && !empty($contact_info['content'])) {
?>
                            <tr>
                                <td class="main eo-label"><?php echo $contact_info['label']; ?></td>
                                <td class="main"><?php echo $contact_info['content']; ?></td>
                            </tr>
<?php
        }
    }
}
?>
                        </table></td>
                    </tr>
<!-- End Phone/Email Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Payment Block -->
<?php
$max_payment_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'payment_method') . '"';
?>
                    <tr>
                        <td><table class="eo-pad">
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_PAYMENT_METHOD; ?></td>
                                <td class="main"><input name="update_info_payment_method" size="20" value="<?php echo zen_output_string_protected($order->info['payment_method']); ?>" <?php echo $max_payment_length; ?>> <?php echo ($order->info['payment_method'] != 'Credit Card') ? ENTRY_UPDATE_TO_CC : ENTRY_UPDATE_TO_CK; ?></td>
                            </tr>
<?php 
if (!empty($order->info['cc_type']) || !empty($order->info['cc_owner']) || $order->info['payment_method'] == "Credit Card" || !empty($order->info['cc_number'])) {
    $max_type_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_type') . '"';
    $max_owner_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_owner') . '"';
    $max_number_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_number') . '"';
    $max_expires_length = 'maxlength="' . zen_field_length(TABLE_ORDERS, 'cc_expires') . '"';
?>
<!-- Begin Credit Card Info Block -->
                            <tr>
                                <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_TYPE; ?></td>
                                <td class="main"><input name="update_info_cc_type" size="10" value="<?php echo zen_output_string_protected($order->info['cc_type']); ?>" <?php echo $max_type_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_OWNER; ?></td>
                                <td class="main"><input name="update_info_cc_owner" size="20" value="<?php echo zen_output_string_protected($order->info['cc_owner']); ?>" <?php echo $max_owner_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_NUMBER; ?></td>
                                <td class="main"><input name="update_info_cc_number" size="20" value="<?php echo zen_output_string_protected($order->info['cc_number']); ?>" <?php echo $max_number_length; ?>></td>
                            </tr>
                            <tr>
                                <td class="main eo-label"><?php echo ENTRY_CREDIT_CARD_EXPIRES; ?></td>
                                <td class="main"><input name="update_info_cc_expires" size="4" value="<?php echo zen_output_string_protected($order->info['cc_expires']); ?>" <?php echo $max_expires_length; ?>></td>
                            </tr>
<!-- End Credit Card Info Block -->
<?php 
}

// -----
// NOTE: No maximum lengths provided for these non-standard fields, since there's no way to know what database table
// the information is stored in!
//
if (isset($order->info['account_name']) || isset($order->info['account_number']) || isset($order->info['po_number'])) {
?>
                            <tr>
                                <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                            </tr>
<?php
    if (isset($order->info['account_name'])) {
?>
                            <tr>
                                <td class="main"><?php echo ENTRY_ACCOUNT_NAME; ?></td>
                                <td class="main"><?php echo zen_output_string_protected($order->info['account_name']); ?></td>
                            </tr>
<?php
    }
    if (isset($order->info['account_number'])) {
?>
                            <tr>
                                <td class="main"><?php echo ENTRY_ACCOUNT_NUMBER; ?></td>
                                <td class="main"><?php echo zen_output_string_protected($order->info['account_number']); ?></td>
                            </tr>
<?php
    }
    if (isset($order->info['po_number'])) {
?>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_PURCHASE_ORDER_NUMBER; ?></strong></td>
                                <td class="main"><?php echo zen_output_string_protected($order->info['po_number']); ?></td>
                            </tr>
<?php
    }
}
?>
                        </table></td>
                    </tr>
                    <tr>
                        <td valign="top">
<?php
$reset_totals_block = '<b>' . RESET_TOTALS . '</b>' . zen_draw_checkbox_field('reset_totals', '', (EO_TOTAL_RESET_DEFAULT == 'on'));
$payment_calc_choice = '';
$priceClass = 'amount p-n';
$priceMessage = '';
if (EO_PRODUCT_PRICE_CALC_METHOD == 'Choose') {
    $choices = [
        ['id' => 1, 'text' => PAYMENT_CALC_AUTOSPECIALS],
        ['id' => 2, 'text' => PAYMENT_CALC_AUTO],
        ['id' => 3, 'text' => PAYMENT_CALC_MANUAL]
    ];
    switch (EO_PRODUCT_PRICE_CALC_DEFAULT) {
        case 'AutoSpecials':
            $default = 1;
            break;
        case 'Auto':
            $default = 2;
            break;
        default:
            $default = 3;
            break;
    }
    if (isset($_SESSION['eo_price_calculations']) && $_SESSION['eo_price_calculations'] >= 1 && $_SESSION['eo_price_calculations'] <= 3) {
        $default = $_SESSION['eo_price_calculations'];
    }
    $payment_calc_choice = '<b>' . PAYMENT_CALC_METHOD . '</b> ' . zen_draw_pull_down_menu('payment_calc_method', $choices, $default);
} else {
    switch (EO_PRODUCT_PRICE_CALC_METHOD) {
        case 'AutoSpecials':
            $payment_calc_choice = PRODUCT_PRICES_CALC_AUTOSPECIALS;
            $priceClass = 'amount p-n hidden';
            $priceMessage = EO_PRICE_AUTO_GRID_MESSAGE;
            break;
        case 'Auto':
            $payment_calc_choice = PRODUCT_PRICES_CALC_AUTO;
            $priceClass = 'amount p-n hidden';
            $priceMessage = EO_PRICE_AUTO_GRID_MESSAGE;
            break;
        default:
            $payment_calc_choice = PRODUCT_PRICES_CALC_MANUAL;
            break;
    }
}

$additional_inputs = '';
$zco_notifier->notify('EDIT_ORDERS_FORM_ADDITIONAL_INPUTS', $order, $additional_inputs);
?>
                            <input type="submit" class="btn btn-danger" value="<?php echo IMAGE_UPDATE; ?>">
                            <?php echo "&nbsp;$reset_totals_block&nbsp;$payment_calc_choice$additional_inputs"; ?>
                        </td>
                    </tr>
<!-- End Payment Block -->

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>

<!-- Begin Products Listing Block -->
                    <tr>
                        <td><table class="eo-table" id="eo-prods">
                            <tr class="dataTableHeadingRow">
<?php
// -----
// To add more columns at the beginning of the order's products' table, a
// watching observer can provide an associative array in the form:
//
// $extra_headings = [
//     [
//       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
//       'text' => $value
//     ],
// ];
//
// Observer note:  Be sure to check that the $p2/$extra_headings value is specifically (bool)false before initializing, since
// multiple observers might be injecting content!
//
$extra_headings = false;
$zco_notifier->notify('EDIT_ORDERS_PRODUCTS_HEADING_1', [], $extra_headings);
if (is_array($extra_headings)) {
    foreach ($extra_headings as $heading_info) {
        $align = '';
        if (isset($heading_info['align'])) {
            switch ($heading_info['align']) {
                case 'center':
                    $align = ' a-c';
                    break;
                case 'right':
                    $align = ' a-r';
                    break;
                default:
                    $align = '';
                    break;
            }
        }
?>
                <td class="dataTableHeadingContent<?php echo $align; ?>"><strong><?php echo $heading_info['text']; ?></td>
<?php
    }
}
?>
                                <td class="dataTableHeadingContent a-c" colspan="3"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_TAX; ?></td>
<?php
// -----
// Starting with v4.4.0, show both the net and gross unit prices when the store is configured to display prices with tax.
//
if (DISPLAY_PRICE_WITH_TAX == 'true') {
?>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_UNIT_PRICE_NET; ?></td>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_UNIT_PRICE_GROSS; ?></td>
<?php
} else {
?>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_UNIT_PRICE; ?></td>
<?php
}
?>
                                <td class="dataTableHeadingContent a-r"><?php echo TABLE_HEADING_TOTAL_PRICE; ?></td>
                            </tr>
<!-- Begin Products Listings Block -->
<?php
// -----
// Initialize (outside of the loop, for performance) the attributes for the various product-related
// input fields.
//
$name_parms = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '" class="eo-name"';
$model_parms = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '" class="eo-name"';

// -----
// Loop through each of the products in the order.
//
$orders_products_id_mapping = eo_get_orders_products_id_mappings((int)$oID);
for ($i = 0, $i2 = count($order->products); $i < $i2; $i++) {
    $orders_products_id = $orders_products_id_mapping[$i];
    $eo->eoLog (
        PHP_EOL . '============================================================' .
        PHP_EOL . '= Creating display of Order Product #' . $orders_products_id .
        PHP_EOL . '============================================================' .
        PHP_EOL . 'Product Details:' .
        PHP_EOL . $eo->eoFormatArray($order->products[$i]) . PHP_EOL
    ); 
?>
                            <tr class="dataTableRow v-top">
<?php
// -----
// To add more columns at the beginning of the order's products' table, a
// watching observer can provide an associative array in the form:
//
// $extra_data = [
//     [
//       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
//       'text' => $value
//     ],
// ];
//
// Observer note:  Be sure to check that the $p2/$extra_data value is specifically (bool)false before initializing, since
// multiple observers might be injecting content!
//
$extra_data = false;
$product_info = $order->products[$i];
$product_info['orders_products_id'] = $orders_products_id;
$zco_notifier->notify('EDIT_ORDERS_PRODUCTS_DATA_1', $product_info, $extra_data);
if (is_array($extra_data)) {
    foreach ($extra_data as $data) {
        $align = '';
        if (isset($data['align'])) {
            switch ($data['align']) {
                case 'center':
                    $align = ' a-c';
                    break;
                case 'right':
                    $align = ' a-r';
                    break;
                default:
                    $align = '';
                    break;
            }
        }
?>
                <td class="dataTableContent<?php echo $align; ?>"><strong><?php echo $data['text']; ?></td>
<?php
    }
}
?>
                                <td class="dataTableContent a-c"><input class="eo-qty" name="update_products[<?php echo $orders_products_id; ?>][qty]" value="<?php echo zen_db_prepare_input($order->products[$i]['qty']); ?>" <?php echo $value_parms; ?> /></td>
                                <td>&nbsp;X&nbsp;</td>
                                <td class="dataTableContent"><input name="update_products[<?php echo $orders_products_id; ?>][name]" value="<?php echo zen_output_string_protected($order->products[$i]['name']); ?>" <?php echo $name_parms; ?> />
<?php
    if (isset($order->products[$i]['attributes']) && count($order->products[$i]['attributes']) > 0) { 
?>
                                    <br/><nobr><small>&nbsp;<i><?php echo TEXT_ATTRIBUTES_ONE_TIME_CHARGE; ?>
                                    <input name="update_products[<?php echo $orders_products_id; ?>][onetime_charges]" value="<?php echo zen_db_prepare_input($order->products[$i]['onetime_charges']); ?>" <?php echo $value_parms; ?> />&nbsp;&nbsp;&nbsp;&nbsp;</i></small></nobr><br/>
<?php
        $selected_attributes_id_mapping = eo_get_orders_products_options_id_mappings($oID, $orders_products_id);
        $attrs = eo_get_product_attributes_options($order->products[$i]['id']);

        $optionID = array_keys($attrs);
        for ($j = 0, $j2 = count($attrs); $j < $j2; $j++) {
            $option_id = $optionID[$j];
            $optionInfo = $attrs[$option_id];

            // -----
            // If an option for the product wasn't selected (or provided, in the case of TEXT
            // attributes) previously, there's nothing to be selected for its to-be-displayed
            // value.
            //
            $orders_products_attributes_id = (!array_key_exists($option_id, $selected_attributes_id_mapping)) ? [] : $selected_attributes_id_mapping[$option_id];
            
            $option_type = $optionInfo['type'];
            $option_type_hidden_field = zen_draw_hidden_field("update_products[$orders_products_id][attr][$option_id][type]", $option_type);
            $option_name = $optionInfo['name'];

            $eo->eoLog (
                PHP_EOL . 'Options ID #' . $option_id . PHP_EOL .
                'Product Attribute: ' . PHP_EOL . $eo->eoFormatArray($orders_products_attributes_id) . PHP_EOL .
                'Options Info:' . PHP_EOL . $eo->eoFormatArray($optionInfo)
            );

            switch ($option_type) {
                case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
                case PRODUCTS_OPTIONS_TYPE_RADIO:
                case PRODUCTS_OPTIONS_TYPE_SELECT:
                case PRODUCTS_OPTIONS_TYPE_SELECT_SBA:
                case PRODUCTS_OPTIONS_TYPE_IMAGE_SWATCH:
                    echo "<label class=\"attribsSelect\" for=\"opid-$orders_products_id-oid-$option_id\">$option_name</label>";
                    $products_options_array = [];
                    $selected_attribute = null;
                    foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                        if (!empty($orders_products_attributes_id) && eo_is_selected_product_attribute_id($orders_products_attributes_id[0], $attributeId)) {
                            $selected_attribute = $attributeId;
                        }
                        $products_options_array[] = [
                            'id' => $attributeId,
                            'text' => $attributeValue
                        ];
                    }
                    if ($selected_attribute === null) {
                        $selected_attribute = $products_options_array[0]['id'];
                    }

                    echo zen_draw_pull_down_menu(
                        "update_products[$orders_products_id][attr][$option_id][value]", 
                        $products_options_array,
                        $selected_attribute, 
                        "id=\"opid-$orders_products_id-oid-$option_id\""
                    ) . "<br />\n";
                    echo $option_type_hidden_field;
                    break;

                case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                    // First we need to see which items are checked.
                    // This also handles correctly forwarding $id_map.
                    $checked = [];
                    foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                        for ($k = 0, $k2 = count($orders_products_attributes_id); $k < $k2; $k++) {
                            if (eo_is_selected_product_attribute_id($orders_products_attributes_id[$k], $attributeId)) {
                                $checked[$attributeId] = $orders_products_attributes_id[$k];
                            }
                        }
                    }

                    // Now display the options
                    echo '<div class="attribsCheckboxGroup"><div class="attribsCheckboxName">' . $option_name . '</div>';
                    foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                        $option_html_id = "opid-$orders_products_id-oid-$option_id-$attributeId";
                        echo zen_draw_checkbox_field(
                            "update_products[$orders_products_id][attr][$option_id][value][$attributeId]",
                            $attributeId, 
                            isset($checked[$attributeId]),
                            null, 
                            "id=\"$option_html_id\""
                        ) . "<label class=\"attribsCheckbox\" for=\"$option_html_id\">$attributeValue</label><br />" . PHP_EOL;
                    }
                    echo $option_type_hidden_field . '</div>';
                    break;

                case PRODUCTS_OPTIONS_TYPE_TEXT:
                    $text = null;
                    if (!empty($orders_products_attributes_id)) {
                        $text = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_key_first($optionInfo['options']));
                    }
                    if ($text === null) {
                        $text = '';
                    }
                    $text = zen_output_string_protected($text);
                    $option_html_id = "opid-$orders_products_id-oid-$option_id";
                    $option_input_name = "update_products[$orders_products_id][attr][$option_id][value]";
                    $option_rows = $optionInfo['rows'];
                    $option_cols = $optionInfo['size'];
                    echo "<label class=\"attribsInput\" for=\"$option_html_id\">$option_name</label>";
                    if ($optionInfo['rows'] > 1 ) {
                        echo "<textarea class=\"attribsTextarea\" name=\"$option_input_name\" rows=\"$option_rows\" cols=\"$option_cols\" id=\"$option_html_id\">$text</textarea>" . PHP_EOL;
                    } else {
                        echo "<input type=\"text\" name=\"$option_input_name\" size=\"$option_cols\" maxlength=\"$option_cols\" value=\"$text\" id=\"$option_html_id\" /><br />" . PHP_EOL;
                    }
                    echo $option_type_hidden_field;
                    break;

                case PRODUCTS_OPTIONS_TYPE_FILE:
                    $optionValue = '';
                    if (!empty($orders_products_attributes_id)) {
                        $optionValue = eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id[0], array_key_first($optionInfo['options']));
                    }
                    echo "<span class=\"attribsFile\">$option_name: " . (!empty($optionValue) ? $optionValue : TEXT_ATTRIBUTES_UPLOAD_NONE) . '</span><br />';
                    if (!empty($optionValue)) {
                        echo zen_draw_hidden_field("update_products[$orders_products_id][attr][$option_id][value]", $optionValue);
                        echo $option_type_hidden_field;
                    }
                    break;

                case PRODUCTS_OPTIONS_TYPE_READONLY:
                default:
                    $optionValue = array_shift($optionInfo['options']);
                    echo '<input type="hidden" name="update_products[' .
                        $orders_products_id . '][attr][' . $optionID[$j] . '][value]" value="' .
                        $optionValue . '" /><span class="attribsRO">' .
                        $optionInfo['name'] . ': ' . $optionValue . '</span><br />';
                    echo $option_type_hidden_field;
                    break;
            }
        }
        unset($optionID, $optionInfo, $products_options_array, $selected_attribute, $attributeId, $attributeValue, $optionValue, $text, $checked);
    } 

    // -----
    // Starting with EO v4.4.0, both the net and gross prices are displayed when the store displays prices with tax.
    //
    if (DISPLAY_PRICE_WITH_TAX == 'true') {
        $final_price = $order->products[$i]['final_price'];
        $onetime_charges = $order->products[$i]['onetime_charges'];
    } else {
        $final_price = $order->products[$i]['final_price'];
        $onetime_charges = $eo->eoRoundCurrencyValue($order->products[$i]['onetime_charges']);
    }
    $data_index = " data-opi=\"$orders_products_id\"";
?>
                                </td>
                                <td class="dataTableContent"><input name="update_products[<?php echo $orders_products_id; ?>][model]" value="<?php echo $order->products[$i]['model']; ?>" <?php echo $model_parms; ?> /></td>
                                <td class="dataTableContent a-r"><input class="amount p-t" name="update_products[<?php echo $orders_products_id; ?>][tax]" value="<?php echo zen_display_tax_value($order->products[$i]['tax']); ?>"<?php echo $data_index . ' ' . $tax_parms; ?> />&nbsp;%</td>
                                <td class="dataTableContent a-r"><input class="<?php echo $priceClass; ?>" name="update_products[<?php echo $orders_products_id; ?>][final_price]" value="<?php echo $final_price; ?>"<?php echo $data_index . ' ' . $value_parms; ?> /><?php echo $priceMessage; ?></td>
<?php
    if (DISPLAY_PRICE_WITH_TAX == 'true') {
        $gross_price = zen_add_tax($final_price, $order->products[$i]['tax']);
        $final_price = $gross_price;
?>
                                <td class="dataTableContent a-r"><input class="amount p-g" name="update_products[<?php echo $orders_products_id; ?>][gross]" value="<?php echo $gross_price; ?>"<?php echo $data_index . ' ' . $value_parms; ?> /></td>
<?php
    }
?>
                                <td class="dataTableContent a-r"><?php echo $currencies->format($final_price * $order->products[$i]['qty'] + $onetime_charges, true, $order->info['currency'], $order->info['currency_value']); ?></td>
                            </tr>
<?php
} 
?>
<!-- End Products Listings Block -->

<!-- Begin Order Total Block -->
<?php
require DIR_WS_MODULES . 'edit_orders/eo_edit_action_ot_table_display.php';
?>
<!-- End Order Total Block -->
                        </table></td>
                    </tr>

                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    <tr>
                        <td class="main"><strong><?php echo zen_image(DIR_WS_IMAGES . 'icon_comment_add.png', TABLE_HEADING_STATUS_HISTORY) . '&nbsp;' . TABLE_HEADING_STATUS_HISTORY; ?></strong></td>
                    </tr>
                    
                    <tr>
                        <td class="main">
                            <?php require DIR_WS_MODULES . 'edit_orders/eo_edit_action_osh_table_display.php'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="main"><br /><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
                    </tr>
                    
                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><?php echo zen_draw_textarea_field('comments', 'soft', '60', '5'); ?></td>
                    </tr>
<?php
// -----
// Give an observer the opportunity to add additional content to the status-history form.
//
// The additional-content array is numerically-indexed and provides the HTML to be included.
//
$additional_osh_content = [];
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_OSH_CONTENT', $order, $additional_osh_content);
if (is_array($additional_osh_content) && count($additional_osh_content) != 0) {
    foreach ($additional_osh_content as $osh_content) {
?>
                    <tr>
                        <td class="main"><?php echo $osh_content; ?></td>
                    </tr>
<?php
    }
}
?>
                    <tr>
                        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><strong><?php echo ENTRY_CURRENT_STATUS; ?><?php echo $orders_status_array[$orders_history->fields['orders_status_id']] ;?></strong></td>
                    </tr>
                    
                    <tr>
                        <td class="main"><strong><?php echo ENTRY_STATUS; ?></strong> <?php echo zen_draw_pull_down_menu('status', $orders_statuses, $orders_history->fields['orders_status_id']); ?></td>
                    </tr>
<?php
// -----
// Determine the default setting for the customer notification, based on the configuration
// setting added in v4.4.0.
//
switch (EO_CUSTOMER_NOTIFICATION_DEFAULT) {
    case 'Hidden':
        $email_default = false;
        $noemail_default = false;
        $hidden_default = true;
        break;
    case 'No Email':
        $email_default = false;
        $noemail_default = true;
        $hidden_default = false;
        break;
    default:
        $email_default = true;
        $noemail_default = false;
        $hidden_default = false;
        break;
}
?>
                    <tr>
                        <td><table>
                            <tr>
                                <td class="main"><strong><?php echo ENTRY_NOTIFY_CUSTOMER; ?></strong> [<?php echo zen_draw_radio_field('notify', '1', $email_default) . '-' . TEXT_EMAIL . ' ' . zen_draw_radio_field('notify', '0', $noemail_default) . '-' . TEXT_NOEMAIL . ' ' . zen_draw_radio_field('notify', '-1', $hidden_default) . '-' . TEXT_HIDE; ?>]&nbsp;&nbsp;&nbsp;</td>
                                <td class="main"><strong><?php echo ENTRY_NOTIFY_COMMENTS; ?></strong> <?php echo zen_draw_checkbox_field('notify_comments', '', true); ?></td>
                            </tr>
                        </table></td>
                    </tr>

                    <tr>
                        <td valign="top">
                            <input type="submit" class="btn btn-danger" value="<?php echo IMAGE_UPDATE; ?>">
                        </td>
                    </tr>
                </table></form></td>
            </tr>
        </table></td>
    </tr>
</table>
