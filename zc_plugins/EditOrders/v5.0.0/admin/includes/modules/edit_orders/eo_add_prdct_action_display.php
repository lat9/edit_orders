<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
//-Last modified: EO v2.7.0
//
// -----
// Prior to EO v4.6.0, this code was in-line in the main /admin/edit_orders.php script.  Now required by
// that script in global context for the rendering for its 'edit' action.
//
$order_parms = zen_get_all_get_params(['oID', 'action', 'resend']) . "oID=$oID&amp;action=edit";
?>
<table class="eo-table">
    <tr>
        <td width="100%"><table class="w100">
            <tr>
                <td class="pageHeading"><?php echo HEADING_TITLE_ADD_PRODUCT; ?> #<?php echo $oID; ?></td>
                <td class="pageHeading a-r"><?php echo zen_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
                <td class="pageHeading a-r">
                    <a href="<?php echo zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(['action'])); ?>" class="btn btn-primary btn-sm" role="button"><?php echo IMAGE_BACK; ?></a>
                    <a href="<?php echo zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(['oID', 'action']) . "oID=$oID&amp;action=edit"); ?>" class="btn btn-primary btn-sm" role="button"><?php echo DETAILS; ?></a>
                </td>
            </tr>
        </table></td>
    </tr>

<?php
// Set Defaults
if (!isset($add_product_categories_id)) {
    $add_product_categories_id = .5;
}

if (!isset($add_product_products_id)) {
    $add_product_products_id = 0;
}

// Step 1: Choose Category
if ($add_product_categories_id == .5) {
    // Handle initial population of categories
    $categoriesarr = zen_get_category_tree();
    array_unshift($categoriesarr, ['id' => 0.5, 'text' => ADDPRODUCT_CHOOSE_CATEGORY]);

    $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', $categoriesarr, 0.5, 'onchange="this.form.submit();"');
} else {
    // Add the category selection. Selecting a category will override the search
    $categoryselectoutput = zen_draw_pull_down_menu('add_product_categories_id', zen_get_category_tree(), $current_category_id, 'onchange="this.form.submit();"');
}
?> 
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'oID']) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP1; ?></td>
                <td class="dataTableContent">
<?php
echo 
    ' ' . $categoryselectoutput . 
    ' OR ' .
    HEADING_TITLE_SEARCH_DETAIL . ' ' . 
    zen_draw_input_field('search', (isset($_POST['search']) && $add_product_categories_id <= 1) ? $_POST['search'] : '', 'onclick="this.form.add_product_categories_id.value=0;"') .
    zen_hide_session_id().
    zen_draw_hidden_field('step', '2');
?>
                </td>
            </tr>
        </table></form></td>
    </tr>

    <tr>
        <td>&nbsp;</td>
    </tr>
<?php
// Step 2: Choose Product
if ($step > 1 && ($add_product_categories_id != .5 || !empty($_POST['search']))) {
    $query =
        "SELECT p.products_id, p.products_model, pd.products_name, p.products_status
           FROM " . TABLE_PRODUCTS . " p
                INNER JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd
                    ON pd.products_id = p.products_id
                   AND pd.language_id = " . (int)$_SESSION['languages_id'];

    if ($add_product_categories_id >= 1) {
        $query .=
            " LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
                ON ptc.products_id = p.products_id
             WHERE ptc.categories_id=" . (int)$add_product_categories_id;
    } elseif (!empty($_POST['search'])) {
        $keyword_search_fields = [
            'pd.products_name',
            'pd.products_description',
            'p.products_id',
            'p.products_model',
        ];
        $query .= zen_build_keyword_where_clause($keyword_search_fields, trim($_POST['search']));
    }

    $query .= zen_products_sort_order();
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'oID']) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP2; ?></td>
                <td class="dataTableContent">
                    <select name="add_product_products_id" onchange="this.form.submit();">
<?php
    $ProductOptions = '<option value="0">' .  ADDPRODUCT_TEXT_SELECT_PRODUCT . '</option>' . PHP_EOL;
    $result = $db->Execute($query);
    foreach ($result as $product) {
        $ProductOptions .= 
            '<option value="' . $product['products_id'] . '">' . 
                 $product['products_name'] .
                ' [' . $product['products_model'] . '] ' . ($product['products_status'] == 0 ? ' (OOS)' : '') .
            '</option>' . PHP_EOL;
    }
    $ProductOptions = str_replace(
        'value="' . $add_product_products_id . '"',
        'value="' . $add_product_products_id . '" selected',
        $ProductOptions
    );
    echo $ProductOptions;
    unset($ProductOptions);
?>
                    </select>
<?php
    echo
        zen_draw_hidden_field('add_product_categories_id', $add_product_categories_id) .
        zen_draw_hidden_field('search', $_POST['search']) .
        zen_draw_hidden_field('step', 3);
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
<?php
}

// Step 3: Choose Options
if ($step > 2 && $add_product_products_id > 0) {
    // Skip to Step 4 if no Options
    if (!zen_has_product_attributes($add_product_products_id, false)) {
        $step = 4;
?>
    <tr class="dataTableRow v-top">
        <td class="dataTableContent eo-label"><?php echo ADDPRODUCT_TEXT_STEP3; ?> <i><?php echo ADDPRODUCT_TEXT_OPTIONS_NOTEXIST; ?></i></td>
    </tr>
<?php
    } else {
        $attrs = eo_get_product_attributes_options($add_product_products_id);
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'oID']) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP3; ?></td>
                <td class="dataTableContent">
<?php
        foreach ($attrs as $optionID => $optionInfo) {
            $option_name = $optionInfo['name'];
            $attrib_id = "attrib-$optionID";
            switch ($optionInfo['type']) {
                case PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID:
                case PRODUCTS_OPTIONS_TYPE_RADIO:
                case PRODUCTS_OPTIONS_TYPE_SELECT:
                case PRODUCTS_OPTIONS_TYPE_SELECT_SBA:
?>
                    <label class="attribsSelect" for="<?php echo $attrib_id; ?>"><?php echo $option_name; ?></label>
<?php
                    $products_options_array = [];
                    foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                        $products_options_array[] = [
                            'id' => $attributeId,
                            'text' => $attributeValue
                        ];
                    }
                    $selected_attribute = $products_options_array[0]['id'];
                    if (isset($_POST['id'][$optionID])) {
                        $selected_attribute = $_POST['id'][$optionID]['value'];
                    }
                    echo zen_draw_pull_down_menu('id[' . $optionID . '][value]', $products_options_array, $selected_attribute, 'id="' . $attrib_id . '"') . '<br>' . PHP_EOL;
                    unset($products_options_array, $selected_attribute, $attributeId, $attributeValue);
                    echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
                    break;

                case PRODUCTS_OPTIONS_TYPE_CHECKBOX:
?>
                    <div class="attribsCheckboxGroup">
                        <div class="attribsCheckboxName"><?php echo $option_name; ?></div>
<?php
                    foreach ($optionInfo['options'] as $attributeId => $attributeValue) {
                        $checked = isset($_POST['id'][$optionID]['value'][$attributeId]);
                        echo
                            zen_draw_checkbox_field('id[' . $optionID . '][value][' . $attributeId . ']', $attributeId, $checked, null, 'id="' . $attrib_id . '-' . $attributeId . '"') .
                            '<label class="attribsCheckbox" for="' . $attrib_id . '-' . $attributeId . '">' .
                                $attributeValue .
                            '</label><br>' . PHP_EOL;
                    }
                    unset($checked, $attributeId, $attributeValue);
                    echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
?>
                    </div>
<?php
                    break;

                case PRODUCTS_OPTIONS_TYPE_TEXT:
                    $text = $_POST['id'][$optionID]['value'] ?? '';
                    $text = zen_output_string_protected($text);
?>
                    <label class="attribsInput" for="<?php echo $attrib_id; ?>"><?php echo $option_name; ?></label>
<?php
                    $field_name = 'id[' . $optionID . '][value]';
                    $field_size = $optionInfo['size'];
                    $field_length = $optionInfo['length'];
                    if ($optionInfo['rows'] > 1 ) {
                        echo zen_draw_textarea_field($field_name, 'hard', $field_size, $optionInfo['rows'], $text, 'class="attribsTextarea" id="' . $attrib_id . '"') . '<br>' . PHP_EOL;
                    } else {
                        echo zen_draw_input_field($field_name, $text, 'size="' . $field_size . '" maxlength="' . $field_length . '" id="' . $attrib_id . '"') . '<br>' . PHP_EOL;
                    }
                    echo zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']);
                    break;

                case PRODUCTS_OPTIONS_TYPE_FILE:
?>
                    <span class="attribsFile"><?php echo $option_name . ': FILE UPLOAD NOT SUPPORTED'; ?></span><br>
<?php
                    break;

                case PRODUCTS_OPTIONS_TYPE_READONLY:
                default:
                    $optionValue = array_pop($optionInfo['options']);
?>
                    <span class="attribsRO"><?php echo $option_name . ': ' . $optionValue; ?></span><br>
<?php
                    echo
                        zen_draw_hidden_field('id[' . $optionID . '][value]', $optionValue) .
                        zen_draw_hidden_field('id[' . $optionID . '][type]', $optionInfo['type']) . PHP_EOL;
                    unset($optionValue);
                    break;
            }
        }
?>
                </td>
                <td class="dataTableContent a-c">
                    <input type="submit" value="<?php echo ADDPRODUCT_TEXT_OPTIONS_CONFIRM; ?>" />
<?php
        echo zen_draw_hidden_field('add_product_categories_id', $add_product_categories_id) .
            zen_draw_hidden_field('add_product_products_id', $add_product_products_id) .
            zen_draw_hidden_field('search', $_POST['search']) .
            zen_draw_hidden_field('step', '4');
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
<?php
    }
?>
    <tr>
        <td>&nbsp;</td>
    </tr>
<?php
}

// Step 4: Confirm
if ($step > 3) {
?>
    <tr>
        <td><?php echo zen_draw_form('add_prdct', FILENAME_EDIT_ORDERS, zen_get_all_get_params(['action', 'oID']) . "oID=$oID&amp;action=add_prdct", 'post', '', true); ?><table border="0">
            <tr class="dataTableRow v-top">
                <td class="dataTableContent a-r eo-label"><?php echo ADDPRODUCT_TEXT_STEP4; ?></td>
                <td class="dataTableContent"><?php echo ADDPRODUCT_TEXT_CONFIRM_QUANTITY .
                    zen_draw_input_field('add_product_quantity', 1, 'class="eo-qty"' . $input_value_parms, true, $input_field_type) .
                    '&nbsp;&nbsp;&nbsp;&nbsp;' .
                    zen_draw_checkbox_field('applyspecialstoprice', '1', true) . ADDPRODUCT_SPECIALS_SALES_PRICE; ?></td>
                 <td class="dataTableContent a-c">
                    <input type="submit" value="<?php echo ADDPRODUCT_TEXT_CONFIRM_ADDNOW; ?>">
<?php
    if (isset($_POST['id'])) {
        foreach ($_POST['id'] as $id => $value) {
            if (is_array($value)) {
                foreach ($value as $id2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $id3 => $value3) {
                            echo zen_draw_hidden_field('id[' . $id . '][' . $id2 . '][' . $id3 . ']', zen_output_string_protected($value3));
                        }
                    } else {
                        echo zen_draw_hidden_field('id[' . $id . '][' . $id2 . ']', zen_output_string_protected($value2));
                    }
                }
            } else {
                echo zen_draw_hidden_field('id[' . $id . ']', zen_output_string_protected($value));
            }
        }
    }
    echo zen_draw_hidden_field('add_product_categories_id', $add_product_categories_id) .
        zen_draw_hidden_field('add_product_products_id', $add_product_products_id) .
        zen_draw_hidden_field('step', '5');
?>
                </td>
            </tr>
        </table></form></td>
    </tr>
<?php
}
?>
</table>
