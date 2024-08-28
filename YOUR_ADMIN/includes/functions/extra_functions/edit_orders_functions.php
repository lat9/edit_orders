<?php
// -----
// Part of the "Edit Orders" plugin for Zen Cart.
//
// Last updated: EO v4.7.0, 20240228, lat9
//
// -----
// Since other plugins (like "Admin New Order") also provide some of these functions,
// continue this function-file "load" only if the current page-load is on
// behalf of "Edit Orders" processing.
//
if (basename($PHP_SELF, '.php') !== FILENAME_EDIT_ORDERS) {
    return;
}

// -----
// Present in the storefront version of the html_output.php functions.
//
if (!function_exists('zen_get_country_list')) {
    function zen_get_country_list($name, $selected = '', $parameters = '') 
    {
        $countriesAtTopOfList = [];
        $countries_array = [
            [
                'id' => '', 
                'text' => PULL_DOWN_DEFAULT
            ]
        ];
        $countries = zen_get_countries();

        // Set some default entries at top of list:
        if (SHOW_CREATE_ACCOUNT_DEFAULT_COUNTRY !== '' && STORE_COUNTRY !== SHOW_CREATE_ACCOUNT_DEFAULT_COUNTRY) {
            $countriesAtTopOfList[] = SHOW_CREATE_ACCOUNT_DEFAULT_COUNTRY;
        }
        $countriesAtTopOfList[] = STORE_COUNTRY;
        // IF YOU WANT TO ADD MORE DEFAULTS TO THE TOP OF THIS LIST, SIMPLY ENTER THEIR NUMBERS HERE.
        // Duplicate more lines as needed
        // Example: Canada is 108, so use 108 as shown:
        //$countriesAtTopOfList[] = 108;

        //process array of top-of-list entries:
        foreach ($countriesAtTopOfList as $key => $val) {
            // -----
            // Account for the possibility that one of the top-of-list countries has been disabled.  If
            // that's the case, issue a PHP notice since the condition really shouldn't happen!
            //
            $country_name = zen_get_country_name($val);
            if ($country_name === '') {
                trigger_error('Country with countries_id = ' . $val . ' is either disabled or does not exist.', E_USER_NOTICE);
            } else {
                $countries_array[] = ['id' => $val, 'text' => $country_name];
            }
        }
        // now add anything not in the defaults list:
        foreach ($countries as $country) {
            $alreadyInList = false;
            foreach ($countriesAtTopOfList as $key => $val) {
                if ($country['countries_id'] == $val) {
                    // If you don't want to exclude entries already at the top of the list, comment out this next line:
                    $alreadyInList = true;
                    continue;
                }
            }
            if (!$alreadyInList) {
                $countries_array[] = [
                    'id' => $country['countries_id'],
                    'text' => $country['countries_name'],
                ];
            }
        }
        return zen_draw_pull_down_menu($name, $countries_array, $selected, $parameters);
    }
}

// Start Edit Orders configuration functions
function eo_debug_action_level_list($level)
{
    $levels = [
        ['id' => '0', 'text' => 'Off'],
        ['id' => '1', 'text' => 'On'],
    ];

    $level = ($level == 0) ? $level : 1;

    // Generate the configuration pulldown
    return zen_draw_pull_down_menu('configuration_value', $levels, $level);
}

// Start Edit Orders functions

/**
 * Retrieves the country id, name, iso_code_2, and iso_code_3 from the database
 * for the requested country.
 *
 * Note: Future-proofing for zc157's addition of multi-lingual Country Names.
 *
 * @param string $country the name, or iso code for the country.
 * @return NULL|array the country if one is found, otherwise NULL
 */
function eo_get_country($country) 
{
    global $eo;

    // -----
    // If the $country input is already an array, then an observer has already manipulated an
    // element of the 'order' class' addresses and the value will be simply returned, noting
    // the processing via an EO log.
    //
    if (is_array($country)) {
        global $eo;
        $eo->eoLog('eo_get_country, returning modified country array: ' . json_encode($country));
        return $country;
    }

    $country = (string)$country;
    $country_data = null;

    // -----
    // First, try to locate the country's ID assuming that the supplied input is the
    // country's name.
    //
    $countries_id = $eo->getCountryId($country);

    // -----
    // If the country was located by name, gather the additional fields for the country.
    //
    if ($countries_id != 0) {
        $country_info = $GLOBALS['db']->Execute(
            "SELECT *
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_id = $countries_id
              LIMIT 1"
        );
        if (!$country_info->EOF) {
            $country_data = [
                'id' => $countries_id,
                'name' => $country,
                'iso_code_2' => $country_info->fields['countries_iso_code_2'],
                'iso_code_3' => $country_info->fields['countries_iso_code_3'],
            ];
        }
    // -----
    // Otherwise, see if a matching entry can be found for the country's ISO-code-2 or -3.
    //
    } else {
        $country_info = $GLOBALS['db']->Execute(
            "SELECT *
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_iso_code_2 = '$country'
                 OR countries_iso_code_3 = '$country'
              LIMIT 1"
        );
        if (!$country_info->EOF) {
            $country_data = [
                'id' => $country_info->fields['countries_id'],
                'name' => zen_get_country_name($country_info->fields['countries_id']),
                'iso_code_2' => $country_info->fields['countries_iso_code_2'],
                'iso_code_3' => $country_info->fields['countries_iso_code_3'],
            ];
        }
    }
    return $country_data;
}

function eo_get_product_attributes_options($products_id)
{
    global $db;

    if (!class_exists('attributes')) {
        require DIR_WS_CLASSES . 'attributes.php';
    }

    $attributes = new attributes();
    $attributes = $attributes->get_attributes_options($products_id);

    // Rearrange these by option id instead of attribute id
    $retval = [];
    foreach ($attributes as $attr_id => $info) {
        $id = $info['id'];
        if (!isset($retval[$id])) {
            $retval[$id] = [
                'options' => [],
                'name' => $info['name'],
                'type' => $info['type'],
                'length' => $info['length'],
                'size' => $info['size'],
                'rows' => $info['rows']
            ];
        }
        $retval[$id]['options'][$attr_id] = $info['value'];
    }
    return $retval;
}

function eo_get_new_product($product_id, $product_qty, $product_tax, $product_options = [], $use_specials = true)
{
    global $db;

    $product_id = (int)$product_id;
    $product_qty = (float)$product_qty;

    $retval = [
        'id' => $product_id,
        'qty' => $product_qty,
        'tax' => (float)$product_tax,
    ];

    $query = $db->Execute(
        "SELECT p.*, pd.*
           FROM " . TABLE_PRODUCTS . " p
                INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd
                    ON pd.products_id = p.products_id
                   AND pd.language_id = " . (int)$_SESSION['languages_id'] . "
          WHERE p.products_id = " . (int)$product_id . "
          LIMIT 1"
    );

    if (!$query->EOF) {
        // -----
        // First, handle some common product-related fields used by EO.
        //
        $retval['name'] = $query->fields['products_name'];
        $retval['model'] = $query->fields['products_model'];
        $retval['price'] = $query->fields['products_price'];
        $retval['tax'] = ($product_tax === false) ? number_format(zen_get_tax_rate_value($query->fields['products_tax_class_id']), 4) : (float)$product_tax;
        $retval['tax_description'] = zen_get_tax_description($query->fields['products_tax_class_id']);

        // -----
        // Next, merge the product-related fields from the database.
        //
        $retval = array_merge($retval, $query->fields);

        // Handle pricing
        $special_price = zen_get_products_special_price($product_id);
        if ($use_specials && $special_price && $retval['products_priced_by_attribute'] === '0') {
            $retval['price'] = $special_price;
        } else {
            $special_price = 0;
        }

        if (zen_get_products_price_is_free($product_id)) {
            // no charge
            $retval['price'] = 0;
        }
        // adjust price for discounts when priced by attribute
        if ($retval['products_priced_by_attribute'] === '1' && zen_has_product_attributes($product_id, 'false')) {
            // reset for priced by attributes
            if ($special_price) {
                $retval['price'] = $special_price;
            } else {
                $retval['price'] = $query->fields['products_price'];
                // START MARKUP
                if (isset($GLOBALS['priceMarkup'])) {
                    $retval['price'] = $GLOBALS['priceMarkup']->calculatePrice(
                        $product_id, 
                        $query->fields['manufacturers_id'],
                        $query->fields['master_categories_id'],
                        $retval['price']
                    );
                }
                // END MARKUP
            }
        } else {
            // discount qty pricing
            if ($retval['products_discount_type'] !== '0') {
                $retval['price'] = zen_get_products_discount_price_qty($product_id, $retval['qty']);
            }
            // START MARKUP
            if (isset($GLOBALS['priceMarkup'])) {
                $retval['price'] = $GLOBALS['priceMarkup']->calculatePrice(
                    $product_id,
                    $query->fields['manufacturers_id'],
                    $query->fields['master_categories_id'],
                    $retval['price']
                );
            }
            // END MARKUP
        }
        unset($special_price);

        $retval['onetime_charges'] = 0;
        $retval['final_price'] = $retval['price'];
    }

    // Handle attributes
    if (is_array($product_options) && count($product_options) > 0) {
        $retval['attributes'] = [];

        if (!class_exists('attributes')) {
            require DIR_WS_CLASSES . 'attributes.php';
        }
        $attributes = new attributes();

        foreach ($product_options as $option_id => $details) {
            $attr = [];
            $add_attribute = true;
            switch ($details['type']) {
                case PRODUCTS_OPTIONS_TYPE_TEXT:
                case PRODUCTS_OPTIONS_TYPE_FILE:
                    $attr['option_id'] = $option_id;
                    $attr['value'] = $details['value'];
                    $attr['value_id'] = 0;
                    if ($attr['value'] == '') {
                        $add_attribute = false;
                        break;
                    }

                    // There should only be one text per name.....
                    $get_attr_id = $attributes->get_attributes_by_option($product_id, $option_id);
                    if (count($get_attr_id) === 1) {
                        $details['value'] = $get_attr_id[0]['products_attributes_id'];
                    }
                    unset($get_attr_id);
                    break;

                case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
                    if (!isset($details['value'])) {
                        $add_attribute = false;
                        break;
                    }
                    $tmp_id = array_shift($details['value']);
                    foreach ($details['value'] as $attribute_id) {
                        // We only get here if more than one checkbox per
                        // option was selected.
                        $tmp = $attributes->get_attribute_by_id($attribute_id, 'order');
                        $retval['attributes'][] = $tmp;

                        // Handle pricing
                        $prices = eo_get_product_attribute_prices($attribute_id, $tmp['value'], $product_qty);
                        unset($tmp);
                        if (!$query->EOF) {
                            $retval['onetime_charges'] += $prices['onetime_charges'];
                            $retval['final_price'] += $prices['price'];
                        }
                    }
                    $details['value'] = $tmp_id;
                    $attr = $attributes->get_attribute_by_id($details['value'], 'order');
                    unset($attribute_id, $attribute_value, $tmp_id);
                    break;

                case PRODUCTS_OPTIONS_TYPE_READONLY:
                    $attr['option_id'] = $option_id;
                    $attr['value'] = $details['value'];

                    // There should only be one R/O attributer per option_id
                    $ro_attrs = $attributes->get_attributes_by_option($product_id, $option_id);
                    $attr['value_id'] = $ro_attrs[0]['options_values_id'];
                    unset($ro_attrs);
                    break;

                default:
                    $attr = $attributes->get_attribute_by_id($details['value'], 'order');
                    break;
            }

            if ($add_attribute === true && !empty($attr)) {
                $retval['attributes'][] = $attr;
                $GLOBALS['eo']->eoLog('eo_get_new_product, adding attribute: ' . json_encode($details) . ', ' . json_encode($attr));
                if (!$query->EOF) {
                    // Handle pricing
                    $prices = eo_get_product_attribute_prices($details['value'], $attr['value'], $product_qty);
                    $retval['onetime_charges'] += $prices['onetime_charges'];
                    $retval['final_price'] += $prices['price'];
                    $retval['products_weight'] += eo_get_product_attribute_weight($product_id, $attr['option_id'], $attr['value_id']);
                }
            }
        }
        unset($query, $attr, $prices, $option_id, $details);
    }
    return $retval;
}

function eo_get_product_attribute_weight($product_id, $option_id, $option_value_id)
{
    global $db;

    $attrib_weight = $db->Execute(
        "SELECT products_attributes_weight, products_attributes_weight_prefix
           FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
          WHERE products_id = $product_id
            AND options_id = " . (int)$option_id . "
            AND options_values_id = " . (int)$option_value_id . "
          LIMIT 1"
    );
    $attribute_weight = 0;
    if (!$attrib_weight->EOF) {
        $attribute_weight = $attrib_weight->fields['products_attributes_weight'];
        if ($attrib_weight->fields['products_attributes_weight_prefix'] === '-') {
            $attribute_weight *= -1;
        }
    }
    return $attribute_weight;
}

function eo_get_product_attribute_prices($attr_id, $attr_value = '', $qty = 1) 
{
    global $db;

    $retval = [
        'onetime_charges' => 0,
        'price' => 0
    ];
    $attr_id = (int)$attr_id;
    $attribute_price = $db->Execute(
        "SELECT *
           FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
          WHERE products_attributes_id = $attr_id
          LIMIT 1"
    );
    if ($attribute_price->EOF) {
        return $retval;
    }
    
    $qty = (float)$qty;
    $product_id = $attribute_price->fields['products_id'];

    // Only check when attributes is not free or the product is not free
    if ($attribute_price->fields['product_attribute_is_free'] !== '1' || !zen_get_products_price_is_free($product_id)) {
        // Handle based upon discount enabled
        if ($attribute_price->fields['attributes_discounted'] === '1') {
            // Calculate proper discount for attributes
            $added_charge = zen_get_discount_calc($product_id, $attr_id, $attribute_price->fields['options_values_price'], $qty);
        } else {
            $added_charge = $attribute_price->fields['options_values_price'];
        }

        // Handle negative price prefix
        // Other price prefixes ("+" and "") should add so no special processing
        if ($attribute_price->fields['price_prefix'] === '-') {
            $added_charge *= -1;
        }
        $retval['price'] += $added_charge;

        //////////////////////////////////////////////////
        // calculate additional charges

        // products_options_value_text
        if (zen_get_attributes_type($attr_id) === PRODUCTS_OPTIONS_TYPE_TEXT) {
            $text_words = zen_get_word_count_price($attr_value, $attribute_price->fields['attributes_price_words_free'], $attribute_price->fields['attributes_price_words']);
            $text_letters = zen_get_letters_count_price($attr_value, $attribute_price->fields['attributes_price_letters_free'], $attribute_price->fields['attributes_price_letters']);
            $retval['price'] += $text_letters;
            $retval['price'] += $text_words;
        }

        // attributes_price_factor
        if ($attribute_price->fields['attributes_price_factor'] > 0) {
            $chk_price = zen_get_products_base_price($product_id);
            $chk_special = zen_get_products_special_price($product_id, false);
            $added_charge = zen_get_attributes_price_factor($chk_price, $chk_special, $attribute_price->fields['attributes_price_factor'], $attribute_price->fields['attributes_price_factor_offset']);
            $retval['price'] += $added_charge;
        }

        // attributes_qty_prices
        if ($attribute_price->fields['attributes_qty_prices'] != '') {
            $chk_price = zen_get_products_base_price($product_id);
            $chk_special = zen_get_products_special_price($product_id, false);
            $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price->fields['attributes_qty_prices'], $qty);
            $retval['price'] += $added_charge;
        }

        // attributes_price_onetime
        if ($attribute_price->fields['attributes_price_onetime'] > 0) {
            $retval['onetime_charges'] = $attribute_price->fields['attributes_price_onetime'];
        }

        // attributes_price_factor_onetime
        if ($attribute_price->fields['attributes_price_factor_onetime'] > 0) {
            $chk_price = zen_get_products_base_price($product_id);
            $chk_special = zen_get_products_special_price($product_id, false);
            $added_charge = zen_get_attributes_price_factor($chk_price, $chk_special, $attribute_price->fields['attributes_price_factor_onetime'], $attribute_price->fields['attributes_price_factor_onetime_offset']);
            $retval['onetime_charges'] += $added_charge;
        }

        // attributes_qty_prices_onetime
        if ($attribute_price->fields['attributes_qty_prices_onetime'] != '') {
            $chk_price = zen_get_products_base_price($product_id);
            $chk_special = zen_get_products_special_price($product_id, false);
            $added_charge = zen_get_attributes_qty_prices_onetime($attribute_price->fields['attributes_qty_prices_onetime'], $qty);
            $retval['onetime_charges'] += $added_charge;
        }
        ////////////////////////////////////////////////
    }
    return $retval;
}

function eo_add_product_to_order($order_id, $product)
{
    global $db, $order, $zco_notifier, $eo, $messageStack;

    // -----
    // If the store has set Configuration->Stock->Allow Checkout to 'false', check to see that sufficient
    // stock is fulfill this order.  Unlike the storefront, the product-add is allowed but the admin
    // receives a message indicating the situation.
    //
    if (STOCK_ALLOW_CHECKOUT === 'false') {
        $qty_available = $eo->getProductsStock($product['id']);
        $eo->eoLog("quantity available: $qty_available, requested " . $product['qty']);
        if ($qty_available < $product['qty']) {
            $messageStack->add_session(sprintf(WARNING_INSUFFICIENT_PRODUCT_STOCK, $product['name'], (string)$product['qty'], (string)$qty_available), 'warning');
        }
    }

    // Handle product stock
    $doStockDecrement = true;
    $zco_notifier->notify('EDIT_ORDERS_ADD_PRODUCT_STOCK_DECREMENT', ['order_id' => $order_id, 'product' => $product], $doStockDecrement);
    $products_id = (int)zen_get_prid($product['id']);
    if (STOCK_LIMITED === 'true' && $doStockDecrement === true) {
        if (DOWNLOAD_ENABLED === 'true') {
            $stock_query_raw =
                "SELECT p.products_quantity, pad.products_attributes_filename, p.product_is_always_free_shipping
                   FROM " . TABLE_PRODUCTS . " p
                        LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                            ON p.products_id = pa.products_id
                        LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                            ON pa.products_attributes_id = pad.products_attributes_id
                  WHERE p.products_id = $products_id";

            // Will work with only one option for downloadable products
            // otherwise, we have to build the query dynamically with a loop
            if (!empty($product['attributes']) && is_array($product['attributes'])) {
                $products_attributes = $product['attributes'];
                $stock_query_raw .= " AND pa.options_id = " . (int)$product['attributes'][0]['option_id'] . " AND pa.options_values_id = " . (int)$product['attributes'][0]['value_id'];
            }
            $stock_values = $db->Execute($stock_query_raw);
        } else {
            $stock_values = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS . " WHERE products_id = $products_id LIMIT 1");
        }

        if (!$stock_values->EOF) {
            // do not decrement quantities if products_attributes_filename exists
            if (DOWNLOAD_ENABLED !== 'true' || $stock_values->fields['product_is_always_free_shipping'] === '2' || empty($stock_values->fields['products_attributes_filename'])) {
                $stock_left = $stock_values->fields['products_quantity'] - $product['qty'];
                $product['stock_reduce'] = $product['qty'];
            } else {
                $stock_left = $stock_values->fields['products_quantity'];
            }

            $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_quantity = " . $stock_left . " WHERE products_id = $products_id LIMIT 1");
            if ($stock_left <= 0) {
                // only set status to off when not displaying sold out
                if (SHOW_PRODUCTS_SOLD_OUT === '0') {
                    $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_status = 0 WHERE products_id = $products_id LIMIT 1");
                }
            }

            // for low stock email
            if ($stock_left <= STOCK_REORDER_LEVEL) {
                // WebMakers.com Added: add to low stock email
                $order->email_low_stock .=  "ID# $products_id\t\t" . $product['model'] . "\t\t" . $product['name'] . "\t\t" . ' Qty Left: ' . $stock_left . "\n";
            }
        }
    }

    // Update products_ordered (for bestsellers list)
    $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_ordered = products_ordered + " . sprintf('%f', $product['qty']) . " WHERE products_id = $products_id LIMIT 1");

    $products_prid = $product['id'];
    if (!empty($product['attributes']) && is_array($product['attributes'])) {
        $attributeArray = [];
        foreach ($product['attributes'] as $attributes) {
            $attributeArray[$attributes['option_id']] = $attributes['value_id'];
        }
        $products_prid = zen_get_uprid($product['id'], $attributeArray);
    }

    $sql_data_array = [
        'orders_id' => (int)$order_id,
        'products_id' => $product['id'],
        'products_model' => $product['model'],
        'products_name' => $product['name'],
        'products_price' => $product['price'],
        'final_price' => $product['final_price'],
        'onetime_charges' => (float)$product['onetime_charges'],
        'products_tax' => $product['tax'],
        'products_quantity' => $product['qty'],
        'products_priced_by_attribute' => (int)$product['products_priced_by_attribute'],
        'product_is_free' => (int)$product['product_is_free'],
        'products_discount_type' => (int)$product['products_discount_type'],
        'products_discount_type_from' => (int)$product['products_discount_type_from'],
        'products_prid' => $products_prid,
        'products_weight' => (float)$product['products_weight'],
        'products_virtual' => (int)$product['products_virtual'],
        'product_is_always_free_shipping' => (int)$product['product_is_always_free_shipping'],
        'products_quantity_order_min'  => (float)$product['products_quantity_order_min'],
        'products_quantity_order_units' => (float)$product['products_quantity_order_units'],
        'products_quantity_order_max' => (float)$product['products_quantity_order_max'],
        'products_quantity_mixed' => (int)$product['products_quantity_mixed'],
        'products_mixed_discount_quantity' => (int)$product['products_mixed_discount_quantity'],
    ];

    // -----
    // Add the product to the order.
    //
    zen_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = $db->Insert_ID();
    $op_sql_data_array = $sql_data_array;

    // -----
    // Note: Similar to the 'EDIT_ORDERS_ADD_PRODUCT' notification at the end of the function, but moved
    // here as a heads-up for products with attributes.  Added in EO v4.7.0.
    //
    $zco_notifier->notify('NOTIFY_EO_ADD_PRODUCT', [
        'orders_products_id' => $order_products_id,
        'product' => $product,
        'sql_data_array' => $sql_data_array,
    ]);

    //------ bof: insert customer-chosen options to order--------
    $attributes_exist = '0';
    if (!empty($product['attributes']) && is_array($product['attributes'])) {
        $attributes_exist = '1';
        foreach ($product['attributes'] as $current_attribute) {
            // -----
            // For TEXT type attributes, the 'value_id' isn't set ... default to 0.
            //
            $value_id = (int)($current_attribute['value_id'] ?? 0);
            if (DOWNLOAD_ENABLED == 'true') {
                $attributes_values = $db->Execute(
                    "SELECT popt.products_options_name, poval.products_options_values_name,
                            pa.options_values_price, pa.price_prefix,
                            pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
                            pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
                            pa.attributes_price_factor, pa.attributes_price_factor_offset,
                            pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
                            pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
                            pa.attributes_price_words, pa.attributes_price_words_free,
                            pa.attributes_price_letters, pa.attributes_price_letters_free,
                            pad.products_attributes_maxdays, pad.products_attributes_maxcount, pad.products_attributes_filename
                       FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                            INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " popt
                                ON popt.products_options_id = pa.options_id
                               AND popt.language_id = " . (int)$_SESSION['languages_id'] . "
                            INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval
                                ON poval.products_options_values_id = pa.options_values_id
                               AND poval.language_id = " . (int)$_SESSION['languages_id'] . "
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                ON pa.products_attributes_id = pad.products_attributes_id
                      WHERE pa.products_id = $products_id
                        AND pa.options_id = " . (int)$current_attribute['option_id'] . "
                        AND pa.options_values_id = $value_id
                      LIMIT 1"
                );
            } else {
                $attributes_values = $db->Execute(
                    "SELECT popt.products_options_name, poval.products_options_values_name,
                            pa.options_values_price, pa.price_prefix,
                            pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
                            pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
                            pa.attributes_price_factor, pa.attributes_price_factor_offset,
                            pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
                            pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
                            pa.attributes_price_words, pa.attributes_price_words_free,
                            pa.attributes_price_letters, pa.attributes_price_letters_free
                       FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                            INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " popt
                                ON popt.products_options_id = pa.options_id
                               AND popt.language_id = " . (int)$_SESSION['languages_id'] . "
                            INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval
                                ON poval.products_options_values_id = pa.options_values_id
                               AND poval.language_id = " . (int)$_SESSION['languages_id'] . "
                      WHERE pa.products_id = $products_id
                        AND pa.options_id = " . (int)$current_attribute['option_id'] . "
                        AND pa.options_values_id = $value_id
                      LIMIT 1"
                );
            }

            //clr 030714 update insert query.  changing to use values form $order->products for products_options_values.
            $sql_data_array = [
                'orders_id' => (int)$order_id,
                'orders_products_id' => (int)$order_products_id,
                'products_options' => $attributes_values->fields['products_options_name'],
                'products_options_values' => $current_attribute['value'],
                'options_values_price' => $attributes_values->fields['options_values_price'],
                'price_prefix' => $attributes_values->fields['price_prefix'],
                'product_attribute_is_free' => $attributes_values->fields['product_attribute_is_free'],
                'products_attributes_weight' => $attributes_values->fields['products_attributes_weight'],
                'products_attributes_weight_prefix' => $attributes_values->fields['products_attributes_weight_prefix'],
                'attributes_discounted' => $attributes_values->fields['attributes_discounted'],
                'attributes_price_base_included' => $attributes_values->fields['attributes_price_base_included'],
                'attributes_price_onetime' => $attributes_values->fields['attributes_price_onetime'],
                'attributes_price_factor' => $attributes_values->fields['attributes_price_factor'],
                'attributes_price_factor_offset' => $attributes_values->fields['attributes_price_factor_offset'],
                'attributes_price_factor_onetime' => $attributes_values->fields['attributes_price_factor_onetime'],
                'attributes_price_factor_onetime_offset' => $attributes_values->fields['attributes_price_factor_onetime_offset'],
                'attributes_qty_prices' => $attributes_values->fields['attributes_qty_prices'],
                'attributes_qty_prices_onetime' => $attributes_values->fields['attributes_qty_prices_onetime'],
                'attributes_price_words' => $attributes_values->fields['attributes_price_words'],
                'attributes_price_words_free' => $attributes_values->fields['attributes_price_words_free'],
                'attributes_price_letters' => $attributes_values->fields['attributes_price_letters'],
                'attributes_price_letters_free' => $attributes_values->fields['attributes_price_letters_free'],
                'products_options_id' => (int)$current_attribute['option_id'],
                'products_options_values_id' => $value_id,
                'products_prid' => $products_prid,
            ];
            zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
            $order_products_attributes_id = $db->Insert_ID();

            // -----
            // Note: Added in EO v4.7.0 to indicate that an attribute for the current
            // product has been added/updated.
            //
            $zco_notifier->notify('NOTIFY_EO_ADD_PRODUCT_ATTRIBUTE', [
                'orders_products_attributes_id' => $order_products_attributes_id,
                'product' => $product,
                'sql_data_array' => $sql_data_array,
            ]);

            if (DOWNLOAD_ENABLED === 'true' && !empty($attributes_values->fields['products_attributes_filename'])) {
                $sql_data_array = [
                    'orders_id' => (int)$order_id,
                    'orders_products_id' => (int)$order_products_id,
                    'orders_products_filename' => $attributes_values->fields['products_attributes_filename'],
                    'download_maxdays' => $attributes_values->fields['products_attributes_maxdays'],
                    'download_count' => $attributes_values->fields['products_attributes_maxcount'],
                    'products_prid' => $products_prid,
                    'products_attributes_id' => $order_products_attributes_id,
                ];
                zen_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);

                // -----
                // Note: Added in EO v4.7.0 to indicate that an download attribute for the current
                // product has been added/updated.
                //
                $zco_notifier->notify('NOTIFY_EO_ADD_PRODUCT_ATTRIBUTE_DOWNLOAD', [
                    'product' => $product,
                    'sql_data_array' => $sql_data_array,
                ]);
            }
        }
    }

    $order->products[] = $product;

    // -----
    // Note: The 'sql_data_array' element reflects the data just recorded for the 'orders_products' table, starting
    // with EO v4.5.5.
    //
    $zco_notifier->notify('EDIT_ORDERS_ADD_PRODUCT', ['order_id' => (int)$order_id, 'orders_products_id' => $order_products_id, 'product' => $product, 'sql_data_array' => $op_sql_data_array]);

    return $product;
}

function eo_update_order_subtotal($order_id, $product, $add = true)
{
    global $db, $order, $eo;

    // Retrieve running subtotal
    if (!isset($order->info['subtotal'])) {
        $query = $db->Execute(
            "SELECT `value` 
               FROM " . TABLE_ORDERS_TOTAL . "
              WHERE orders_id = " . (int)$order_id . "
                AND `class` = 'ot_subtotal'
              LIMIT 1"
        );
        if (!$query->EOF) {
            $order->info['subtotal'] = $query->fields['value'];
        }
    }

    $eo->eoLog("eo_update_order_subtotal ($add), taxes on entry. " . $eo->eoFormatTaxInfoForLog(true), 'tax');

    // Determine the product price
    $final_price = $product['final_price'];
    $products_tax = $product['tax'];
    $qty = $product['qty'];
    $onetime_charges = $product['onetime_charges'];
    $shown_price = $eo->eoRoundCurrencyValue(zen_add_tax(($final_price * $qty) + $onetime_charges, $products_tax));

    $starting_totals = [
        'subtotal' => $order->info['subtotal'],
        'tax' => $order->info['tax'],
        'total' => $order->info['total']
    ];

    // Update the order information
    if ($add === true) {
        $order->info['subtotal'] += $shown_price;
        $order->info['tax'] += $eo->getProductTaxes($product, $shown_price, $add);
    } else {
        $order->info['subtotal'] -= $shown_price;
        $order->info['tax'] -= $eo->getProductTaxes($product, $shown_price, $add);
    }
    unset($shown_price);

    // Update the final total to include tax if not already tax-inc
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $order->info['total'] = $order->info['subtotal'] + $order->info['shipping_cost'];
    } else {
        $order->info['total'] = $order->info['subtotal'] + $order->info['tax'] + $order->info['shipping_cost'];
    }

    // Update the order totals (if present)
    foreach ($order->totals as $index => $total) {
        switch($total['class']) {
            case 'ot_subtotal':
                $order->totals[$index]['value'] = $order->info['subtotal'];
                $order->totals[$index]['text'] = $eo->eoFormatCurrencyValue($order->totals[$index]['value']);
                break;
            case 'ot_tax':
                $order->totals[$index]['value'] = $order->info['tax'];
                $order->totals[$index]['text'] = $eo->eoFormatCurrencyValue($order->totals[$index]['value']);
                break;
            case 'ot_total':
                $order->totals[$index]['value'] = $order->info['total'];
                $order->totals[$index]['text'] = $eo->eoFormatCurrencyValue($order->totals[$index]['value']);
                break;
            default:
                break;
        }
    }
    unset($index, $total);
    $eo->eoLog('eo_update_order_subtotal, taxes on exit. ' . $eo->eoFormatTaxInfoForLog(), 'tax');
}

function eo_remove_product_from_order($order_id, $orders_products_id)
{
    global $db, $order, $zco_notifier;

    $order_id = (int)$order_id;
    // First grab the order <==> product mappings
    $orders_products_id_mapping = eo_get_orders_products_id_mappings($order_id);

    // Handle product stock
    $doStockDecrement = true;
    $zco_notifier->notify('EDIT_ORDERS_REMOVE_PRODUCT_STOCK_DECREMENT', ['order_id' => $order_id, 'orders_products_id' => $orders_products_id], $doStockDecrement);
    if (STOCK_LIMITED === 'true' && $doStockDecrement === true) {
        $query = $db->Execute(
            "SELECT products_id, products_quantity
               FROM " . TABLE_ORDERS_PRODUCTS . "
              WHERE orders_id = $order_id
                AND orders_products_id = " . (int)$orders_products_id
        );

        foreach ($query as $product) {
            if (DOWNLOAD_ENABLED === 'true') {
                $check = $db->Execute(
                    "SELECT p.products_quantity, p.products_ordered, pad.products_attributes_filename, p.product_is_always_free_shipping
                       FROM " . TABLE_PRODUCTS . " AS p 
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " AS pa 
                                ON p.products_id = pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " AS pad 
                                ON pa.products_attributes_id = pad.products_attributes_id
                      WHERE p.products_id = {$product['products_id']}"
                );
            } else {
                $check = $db->Execute(
                    "SELECT p.products_quantity, p.products_ordered 
                       FROM " . TABLE_PRODUCTS . " AS p
                      WHERE p.products_id = {$product['products_id']}"
                );
            }
            if (!$check->EOF && (DOWNLOAD_ENABLED !== 'true' || $check->fields['product_is_always_free_shipping'] === '2' || empty($check->fields['products_attributes_filename']))) {
                $sql_data_array = [
                    'products_quantity' => $check->fields['products_quantity'] + $product['products_quantity'],
                    'products_ordered' => $check->fields['products_ordered'] - $product['products_quantity']
                ];
                if ($sql_data_array['products_ordered'] < 0) {
                    $sql_data_array['products_ordered'] = 0;
                }
                if ($sql_data_array['products_quantity'] > 0) {
                    // Only set status to on when not displaying sold out
                    if (SHOW_PRODUCTS_SOLD_OUT === '0') {
                        $sql_data_array['products_status'] = 1;
                    }
                }
                zen_db_perform(TABLE_PRODUCTS, $sql_data_array, 'update', 'products_id = ' . (int)$product['products_id']);
            }
        }
        unset($check, $query, $sql_data_array);
    }
    
    $zco_notifier->notify('EDIT_ORDERS_REMOVE_PRODUCT', ['order_id' => (int)$order_id, 'orders_products_id' => (int)$orders_products_id]);

    // Remove the product from the order in the database
    $remove_query = 'DELETE FROM `%1$s` WHERE orders_id = ' . (int)$order_id . ' AND orders_products_id = ' . (int)$orders_products_id;
    $db->Execute(sprintf($remove_query, TABLE_ORDERS_PRODUCTS));
    $db->Execute(sprintf($remove_query, TABLE_ORDERS_PRODUCTS_ATTRIBUTES));
    $db->Execute(sprintf($remove_query, TABLE_ORDERS_PRODUCTS_DOWNLOAD));
    unset($remove_query);

    // Handle the internal products array
    for ($i = 0, $n = count($order->products); $i < $n; $i++) {
        if ($orders_products_id == $orders_products_id_mapping[$i]) {
            // Remove from the product from the array
            unset($order->products[$i]);
            // rekey the array (so for loops work)
            $order->products = array_values($order->products);
            break;
        }
    }
}

function eo_get_order_total_by_order($order_id, $class = null)
{
    global $db, $eo;

    // Retrieve the raw value
    $and_clause = ($class !== null) ? " AND `class` = '$class'" : '';
    $order_totals = $db->Execute(
        "SELECT *
           FROM " . TABLE_ORDERS_TOTAL . "
          WHERE orders_id = " . (int)$order_id . "$and_clause
          ORDER BY sort_order ASC"
    );

    $retval = [];
    foreach ($order_totals as $ot) {
        $retval[$ot['class']] = [
            'title' => $ot['title'],
            'text' => $ot['text'],
            'value' => $ot['value'],
            'sort_order' => (int)$ot['sort_order'],
        ];
    }
    return $retval;
}

function eo_get_orders_products_id_mappings($order_id)
{
    global $db;
    $orders_products_ids = $db->Execute(
        "SELECT `orders_products_id`
           FROM " . TABLE_ORDERS_PRODUCTS . "
          WHERE `orders_id` = " . (int)$order_id . "
          ORDER BY `orders_products_id` ASC"
    );

    $retval = [];
    foreach ($orders_products_ids as $opi) {
        $retval[] = $opi['orders_products_id'];
    }
    return $retval;
}

function eo_get_orders_products_attributes_id_mappings($order_id, $order_product_id)
{
    global $db;
    $orders_products_ids = $db->Execute(
        "SELECT `orders_products_attributes_id`
           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
          WHERE `orders_id` = " . (int)$order_id . "
            AND `orders_products_id` = " . (int)$order_product_id . "
          ORDER BY orders_products_attributes_id ASC"
    );

    $retval = [];
    foreach ($orders_products_ids as $opa) {
        $retval[] = $opa['orders_products_attributes_id'];
    }
    return $retval;
}

function eo_get_orders_products_options_id_mappings($order_id, $order_product_id)
{
    global $db;
    $orders_products_ids = $db->Execute(
        "SELECT `products_options_id`, `orders_products_attributes_id`
           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
          WHERE `orders_id` = " . (int)$order_id . "
            AND `orders_products_id` = " . (int)$order_product_id
    );

    $retval = [];
    foreach ($orders_products_ids as $ids) {
        $options_id = $ids['products_options_id'];
        if (!isset($retval[$options_id])) {
            $retval[$options_id] = [];
        }
        $retval[$options_id][] = $ids['orders_products_attributes_id'];
    }
    return $retval;
}

function eo_is_selected_product_attribute_id($orders_products_attributes_id, $attribute_id)
{
    global $db;

    $attributes = new attributes();
    $attributes = $attributes->get_attribute_by_id($attribute_id);

    $query = $db->Execute(
        "SELECT COUNT(`orders_products_attributes_id`) AS `count`
           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " AS opa
                LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " AS op 
                    ON op.orders_products_id = opa.orders_products_id 
          WHERE opa.orders_products_attributes_id = " . (int)$orders_products_attributes_id . "
            AND op.products_id = " . (int)$attributes['products_id'] . "
            AND opa.products_options_id = " . (int)$attributes['options_id'] . "
            AND opa.products_options_values_id = " . (int)$attributes['options_values_id']
    );
    unset($attributes);

    return (!$query->EOF && $query->fields['count'] == 1);
}

function eo_get_selected_product_attribute_value_by_id($orders_products_attributes_id, $attribute_id)
{
    global $db;

    $attributes = new attributes();
    $attributes = $attributes->get_attribute_by_id($attribute_id);

    $query = $db->Execute(
        "SELECT products_options_values
           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " AS opa
                LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " AS op
                    ON op.products_id = " . (int)$attributes['products_id'] . "
                   AND op.orders_products_id = opa.orders_products_id
          WHERE opa.orders_products_attributes_id = " . (int)$orders_products_attributes_id . "
            AND opa.products_options_id = " . (int)$attributes['options_id'] . "
            AND opa.products_options_values_id = " . (int)$attributes['options_values_id'] . "
          LIMIT 1"
    );
    unset($attributes);

    return ($query->EOF) ? null : $query->fields['products_options_values'];
}

function eo_update_database_order_totals($oID)
{
    global $db, $order, $eo;

    // Load required modules for order totals if enabled
    if (defined('MODULE_ORDER_TOTAL_INSTALLED') && !empty(MODULE_ORDER_TOTAL_INSTALLED)) {
        $eo->eoLog(PHP_EOL . 'eo_update_database_order_totals, taxes/totals on entry. ' . $eo->eoFormatTaxInfoForLog(true), 'tax');

        $eo->tax_updated = false;

        $order->info['shipping_tax'] = 0;

        // Load order totals.
        require_once DIR_FS_CATALOG . DIR_WS_CLASSES . 'order_total.php';
        $GLOBALS['order_total_modules'] = new order_total();

        // Load the shopping cart class into the session
        eo_shopping_cart();

        // Reset the final total (include tax if not already tax-inc)
        // This code causes the order totals to be correctly calculated.
        if (DISPLAY_PRICE_WITH_TAX == 'true') {
            $order->info['total'] = $order->info['subtotal'] + $order->info['shipping_cost'];
        } else {
            $order->info['total'] = $order->info['subtotal'] + $order->info['tax'] + $order->info['shipping_cost'];
        }

        $eo->eoLog('eo_update_database_order_totals, after adjustments: ' . $eo->eoFormatArray($order->info) . PHP_EOL . $eo->eoFormatArray($order->totals), 'tax');

        // Process the order totals
        $order_totals = $GLOBALS['order_total_modules']->process();
        $eo->eoLog('eo_update_database_order_totals, after process: ' . $eo->eoFormatArray($order->info) . PHP_EOL . $eo->eoFormatArray($order_totals) . PHP_EOL . $eo->eoFormatArray($order->totals), 'tax');

        $GLOBALS['zco_notifier']->notify('EO_UPDATE_DATABASE_ORDER_TOTALS_MAIN', $oID);
        // Update the order totals in the database
        foreach ($order_totals as $next_total) {
            $GLOBALS['zco_notifier']->notify('EO_UPDATE_DATABASE_ORDER_TOTALS_ITEM', $oID, $next_total);
            eo_update_database_order_total($oID, $next_total);
        }

        // -----
        // Account for order totals that were present, but have been removed.  Special processing
        // needed for the 'ot_shipping' total, since there might be multiple colons (:) tacked to
        // the end of the name.
        //
        $totals_titles = [];
        $totals_codes = [];
        foreach ($order_totals as $next_total) {
            $code = $next_total['code'];
            $totals_titles[] = ($code === 'ot_shipping') ? rtrim($next_total['title'], ':') : $next_total['title'];
            $totals_codes[] = $code;
        }
        foreach ($order->totals as $next_total) {
            $title = $next_total['title'];
            if ($next_total['class'] === 'ot_shipping') {
                $title = rtrim($title, ':');
            }
            if (!in_array($title, $totals_titles) || !in_array($next_total['class'], $totals_codes)) {
                $and_clause = (!in_array($title, $totals_titles)) ? ("title = '" . zen_db_input($title) . "'") : '';
                if (!in_array($next_total['class'], $totals_codes)) {
                    if ($and_clause !== '') {
                        $and_clause = "$and_clause OR ";
                    }
                    $and_clause .= ("`class` = '" . $next_total['class'] . "'");
                }
                $eo->eoLog("Removing order-total, and-clause: $and_clause");
                $db->Execute("DELETE FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = $oID AND ($and_clause) LIMIT 1");
            }
        }
        unset($order_totals);

        // -----
        // Now, remove any order-totals that were previously recorded but are no longer present.
        //
        $present_order_totals = implode("','", $totals_codes);
        $eo->eoLog("eo_update_database_order_totals, removing order-totals previously recorded other than: $present_order_totals");
        $db->Execute("DELETE FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = $oID AND `class` NOT IN ('$present_order_totals')");

        // -----
        // It's possible to have a "rogue" ot_tax value recorded, based on tax-processing for a previous
        // update.  Make sure that any no-longer-valid tax totals, i.e. those that aren't recorded in the
        // order's tax_groups, are removed.
        //
        // Note: Special handling required when a store's got SHOW_SPLIT_TAX_CHECKOUT set to 'false', i.e. multiple
        // tax-groups are combined into a single ot_tax record.
        //
        if (isset($order->info['tax_groups']) && is_array($order->info['tax_groups'])) {
            // -----
            // If more than one tax-group is recorded, check to see if the "Unknown" one
            // is recorded.  If it is and its value is 0, it's not contributing anything
            // so it'll be removed.
            //
            if (count($order->info['tax_groups']) > 1) {
                foreach ($order->info['tax_groups'] as $tax_name => $tax_value) {
                    if ($tax_name === TEXT_UNKNOWN_TAX_RATE && $tax_value == 0) {
                        unset($order->info['tax_groups'][$tax_name]);
                        break;
                    }
                }
            }
            $tax_groups = array_keys($order->info['tax_groups']);
            if (SHOW_SPLIT_TAX_CHECKOUT === 'false') {
                $tax_groups = "'" . implode(' + ', $tax_groups) . ":'";
            } else {
                $tax_groups = "'" . implode(":', '", $tax_groups) . ":'";
            }
            $db->Execute(
                "DELETE FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE orders_id = $oID
                    AND `class` = 'ot_tax'
                    AND `title` NOT IN ($tax_groups)"
            );
            $eo->eoLog("eo_update_database_order_totals, removing tax groups NOT IN ($tax_groups).", 'tax');
        }

        // -----
        // Handle a corner-case:  If the store has set Configuration->My Store->Sales Tax Display Status to '0' (no tax displayed
        // if it's 0), and the admin has removed the tax (setting the tax-percentages to 0) for this order.
        //
        // In that case, an ot_tax value doesn't get generated for this order-update but there might have previously been
        // a tax value set.  If this situation is detected, simply remove the ot_tax value from the order's stored
        // order-totals.
        //
        if (STORE_TAX_DISPLAY_STATUS === '0' && $order->info['tax'] == 0) {
            $db->Execute("DELETE FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = $oID AND `class` = 'ot_tax'");
        }
        $eo->eoLog('eo_update_database_order_totals, taxes on exit. ' . $eo->eoFormatTaxInfoForLog(), 'tax');
    }
}

function eo_update_database_order_total($oID, $order_total)
{
    global $db, $eo;
    $updated = false;

    $oID = (int)$oID;
    
    // -----
    // The 'ot_shipping' total's 'process' method appends a trailing ':' to the shipping method's
    // title on each call, resulting in an ever-growing number of ':'s at the end of that title.
    //
    // If the to-be-updated total is 'ot_shipping', strip all trailing colons and then add a single
    // one.
    //
    if ($order_total['code'] === 'ot_shipping') {
        $order_total['title'] = rtrim($order_total['title'], ':') . ':';
    }

    $sql_data_array = [
        'title' => $order_total['title'],
        'text' => $order_total['text'],
        'value' => (is_numeric($order_total['value'])) ? $order_total['value'] : 0,
        'sort_order' => (int)$order_total['sort_order']
    ];

    // Update the Order Totals in the Database, recognizing that there might be multiple records for the product's tax
    $and_clause = ($order_total['code'] === 'ot_tax' && SHOW_SPLIT_TAX_CHECKOUT === 'true') ? (" AND `title` = '" . $order_total['title'] . "'") : '';
    $found = $db->Execute(
        "SELECT orders_id 
           FROM " . TABLE_ORDERS_TOTAL . "
          WHERE `class` = '" . $order_total['code'] . "'
            AND `orders_id` = $oID$and_clause"
    );

    $eo->eoLog("eo_update_database_order_total: and_clause: ($and_clause), found (" . (int)$found->EOF . "), " . $eo->eoFormatArray($order_total), 'tax');
    if (!$found->EOF) {
        if (!empty($order_total['title']) && $order_total['title'] != ':') {
            zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array, 'update', "class='" . $order_total['code'] . "' AND orders_id=$oID$and_clause");
        } else {
            $db->Execute(
                "DELETE FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE `class`= '" . $order_total['code'] . "' 
                    AND orders_id = $oID$and_clause"
            );
        }
        $updated = true;
    } elseif (!empty($order_total['title']) && $order_total['title'] != ':') {
        $sql_data_array['orders_id'] = (int)$oID;
        $sql_data_array['class'] = $order_total['code'];

        zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
        $updated = true;
        $eo->eoLog("Adding order-total: " . json_encode($sql_data_array), 'tax');
    } else {
        return $updated;
    }

    // Update order if relevant
    switch ($order_total['code']) {
        case 'ot_tax':
//-bof-20160407-lat9-Any tax is now added to the order, so that stores using split-tax display can still use EO
            // -----
            // If this is the first "ot_tax" record, it's set into the order's tax; subsequent "ot_tax" records
            // get added ...
            //
            $tax_value = $sql_data_array['value'];
            if ($eo->tax_updated === true) {
                $tax_value = 'order_tax + ' . $tax_value;
            }
            $eo->tax_updated = true;
            $db->Execute("UPDATE " . TABLE_ORDERS . " SET order_tax = $tax_value WHERE orders_id = $oID LIMIT 1");
//-eof-20160407-lat9
            break;

        case 'ot_total':
            zen_db_perform(TABLE_ORDERS, ['order_total' => $sql_data_array['value']], 'update', "orders_id = $oID LIMIT 1");
            break;

        case 'ot_shipping':
            if (substr($order_total['title'], -1) === ':') {
                $order_total['title'] = substr($order_total['title'], 0, -1);
            }
            $sql_data_array = [
                'shipping_method' => $order_total['title'],
                'shipping_tax_rate' => $GLOBALS['eo']->eoGetShippingTaxRate($GLOBALS['order']),
            ];
            if (isset($order_total['shipping_module'])) {
                $sql_data_array['shipping_module_code'] = $order_total['shipping_module'];
            }

            zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = $oID LIMIT 1");
            break;

        default:
            break;
    }
    unset($sql_data_array);

    return $updated;
}

function eo_get_available_order_totals_class_values($oID)
{
    global $order;
    $retval = [];

    // Remove order totals already present in the order
    $module_list = explode(';', (str_replace('.php', '', MODULE_ORDER_TOTAL_INSTALLED)));
    $order_totals = eo_get_order_total_by_order($oID);
    if ($order_totals !== null) {
        foreach ($order_totals as $class => $total) {
            if ($class === 'ot_local_sales_taxes') {
                continue;
            }
            $keys = array_keys($module_list, $class);
            foreach ($keys as $key) {
                unset($module_list[$key]);
            }
        }
    }

    // -----
    // If it's not already created, initialize the order's shipping tax value for use
    // by the ot_shipping order-total.
    //
    if (!isset($order->info['shipping_tax'])) {
        $order->info['shipping_tax'] = 0;
    }

    // Load the order total classes
    if (!class_exists('order_total')) {
        require DIR_FS_CATALOG . DIR_WS_CLASSES . 'order_total.php';
    }
    $order_totals = new order_total();

    foreach ($module_list as $class) {
        if ($class === 'ot_group_pricing' || $class === 'ot_cod_fee' || $class === 'ot_tax' || $class === 'ot_loworderfee' || $class === 'ot_purchaseorder') {
            continue;
        }
        $retval[] = [
            'id' => $class,
            'text' => $GLOBALS[$class]->title,
            'sort_order' => (int)$GLOBALS[$class]->sort_order
        ];
    }
    return $retval;
}

function eo_get_available_shipping_modules()
{
    global $order;
    $retval = [];
    if (defined('MODULE_SHIPPING_INSTALLED') && !empty(MODULE_SHIPPING_INSTALLED)) {
        // Load the shopping cart class into the session
        eo_shopping_cart();

        // Load the shipping class into the globals
        if (!class_exists('shipping')) {
            require DIR_FS_CATALOG . DIR_WS_CLASSES . 'shipping.php';
        }
        $shipping_modules = new shipping();

        $use_strip_tags = (defined('EO_SHIPPING_DROPDOWN_STRIP_TAGS') && EO_SHIPPING_DROPDOWN_STRIP_TAGS === 'true');
        for ($i = 0, $n = count($shipping_modules->modules); $i < $n; $i++) {
            $class = substr($shipping_modules->modules[$i], 0, strrpos($shipping_modules->modules[$i], '.'));
            if (isset($GLOBALS[$class])) {
                $retval[] = [
                    'id' => $GLOBALS[$class]->code,
                    'text' => ($use_strip_tags) ? strip_tags($GLOBALS[$class]->title) : $GLOBALS[$class]->title
                ];
            }
        }
        unset($shipping_modules, $class, $i, $n);
    }
    return $retval;
}

function eo_shopping_cart()
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = new mockCart();
    }
}

function eo_display_customers_notifications_icon($customer_notified)
{
    $icon_color = 'text-success';
    switch ($customer_notified) {
        case '1':
            $status_icon = 'fa fa-check';
            $icon_alt_text = TEXT_YES;
            break;
         case '-1':
            $status_icon = 'fa fa-lock';
            $icon_alt_text = TEXT_HIDDEN;
            $icon_color = 'text-warning';
            break;
          default:
            $status_icon = 'fa fa-unlock';
            $icon_alt_text = TEXT_VISIBLE;
            break;
    }
    return '<i class="fa-lg ' . $status_icon . ' ' . $icon_color . '" title="' . $icon_alt_text . '"></i>';
}

function eo_checks_and_warnings()
{
    global $db, $messageStack;

    // -----
    // Ensure that some 'base' hidden configuration elements are present; they've been removed at times
    // by plugins' uninstall SQL scripts.
    //
    $reload = (!defined('PRODUCTS_OPTIONS_TYPE_SELECT') || !defined('UPLOAD_PREFIX') || !defined('TEXT_PREFIX'));
    if ($reload) {
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added)
             VALUES
                ('Product option type Select', 'PRODUCTS_OPTIONS_TYPE_SELECT', '0', 'The number representing the Select type of product option.', '6', now()),
                ('Upload prefix', 'UPLOAD_PREFIX', 'upload_', 'Prefix used to differentiate between upload options and other options', '6', now()),
                ('Text prefix', 'TEXT_PREFIX', 'txt_', 'Prefix used to differentiate between text option values and other options', '6', now())"
        );
        zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action']) . 'action=edit'));
    }

    // -----
    // Check to be sure that the admin's zen_add_tax function has been updated to remove
    // the unwanted pre-rounding that affects EO's calculations, denying
    // the usage of Edit Orders until the issue is resolved.
    //
    $value = zen_add_tax(5.1111, 0);
    if ($value != 5.1111) {
        $messageStack->add_session(ERROR_ZEN_ADD_TAX_ROUNDING, 'error');
        zen_redirect(zen_href_link(FILENAME_ORDERS, (isset($_GET['oID'])) ? ('action=edit&amp;oID=' . (int)$_GET['oID']) : ''));
    }
    
    // -----
    // Issue a notification, allowing other add-ons to add any warnings they might have.
    //
    $GLOBALS['zco_notifier']->notify('EDIT_ORDERS_CHECKS_AND_WARNINGS');

    // Warn user about subtotal calculations
    if (DISPLAY_PRICE_WITH_TAX_ADMIN !== DISPLAY_PRICE_WITH_TAX) {
        $messageStack->add(WARNING_DISPLAY_PRICE_WITH_TAX, 'warning');
    }

    // Warn user about potential issues with subtotal / total calculations
    $module_list = explode(';', (str_replace('.php', '', MODULE_ORDER_TOTAL_INSTALLED)));
    if (!in_array('ot_subtotal', $module_list)) {
        $messageStack->add(WARNING_ORDER_TOTAL_SUBTOTAL, 'warning');
    }
    if (!in_array('ot_total', $module_list)) {
        $messageStack->add(WARNING_ORDER_TOTAL_TOTAL, 'warning');
    }
    unset($module_list);

    // Check for the installation of "Absolute's Product Attribute Grid"
    if (!defined('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID')) {
        if (defined('CONFIG_ATTRIBUTE_OPTION_GRID_INSTALLED')) {
            define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', '23997');
            $messageStack->add(WARNING_ATTRIBUTE_OPTION_GRID, 'warning');
        } else {
            define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', '-1');
        }
    }
    
    // Check for the installation of "Potteryhouse's/mc12345678's Stock By Attributes"
    if (!defined('PRODUCTS_OPTIONS_TYPE_SELECT_SBA')) {
        define('PRODUCTS_OPTIONS_TYPE_SELECT_SBA', '-1');
    }
    
    // -----
    // Check for the installation of lat9's "Attribute Image Swapper".
    //
    if (!defined('PRODUCTS_OPTIONS_TYPE_IMAGE_SWATCH')) {
        define('PRODUCTS_OPTIONS_TYPE_IMAGE_SWATCH', -1);
    }
}
