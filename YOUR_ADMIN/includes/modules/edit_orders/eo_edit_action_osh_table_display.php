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
// /admin/includes/modules/edit_orders/eo_edit_action_display.php in global context for the rendering of the
// current order's orders-status-history table.
//
?>
<table id="osh" border="1" cellspacing="0" cellpadding="5">
<?php 
// -----
// Gather the order's status-history records, sorting based on the configuration setting added in v4.4.0.
//
$osh_order_by = (EO_STATUS_HISTORY_DISPLAY_ORDER === 'Desc') ? 'date_added DESC, orders_status_history_id DESC' : 'date_added ASC, orders_status_history_id ASC';
$orders_history = $db->Execute(
    "SELECT *
       FROM " . TABLE_ORDERS_STATUS_HISTORY . "
      WHERE orders_id = $oID
      ORDER BY $osh_order_by"
);

if ($orders_history->EOF) {
?>
    <tr>
        <td class="smallText no-osh"><?php echo TEXT_NO_ORDER_HISTORY; ?></td>
    </tr>
<?php
} else {
    // -----
    // Initialize the table describing the "standard" table elements to display, then issue a notification
    // that allows a watching observer to manipulate the table to re-arrange the order of each row's
    // display and/or insert additional display fields.  The table's columns (left-to-right) will be displayed
    // in the order specified in this table (top-to-bottom).
    //
    // Each table element is an associative array (keyed on the field name in the orders_status_history table),
    // containing an array with the following recognized elements:
    //
    // title ................ (Required) The title to be displayed in the table header for the data column.  Note that the
    //                        'title' can be blank, indicating that no title is associated with the database field and that
    //                        the field is not displayed within the overall status table.
    // show_function ........ (Optional) Identifies the name of the function to be called to display the database value.  The
    //                        function takes either 1 (the database field value) or 2 (the database field value, then the field
    //                        name), depending on the value of the 'include_field_name' field.
    //                        If the element is not supplied, the value present in the database is displayed.
    // include_field_name ... (Optional) If a 'show_function' is identified and this element is (bool)true, then the 'show_function'
    //                        takes two parameters, as identified above.
    // align ................ (Optional) Identifies the alignment to be applied when rendering the element in the table, one of:
    //                        center, right or left (the default).
    //
    $table_elements = [
        'date_added' => [
            'title' => TABLE_HEADING_DATE_ADDED,
            'show_function' => 'zen_datetime_short',
            'include_field_name' => false
        ],
        'customer_notified' => [
            'title' => TABLE_HEADING_CUSTOMER_NOTIFIED,
            'show_function' => 'eo_display_customers_notifications_icon',
            'align' => 'center',
            'include_field_name' => false
        ],
        'orders_status_id' => [
            'title' => TABLE_HEADING_STATUS,
            'show_function' => 'built-in'
        ],
        'comments' => [
            'title' => TABLE_HEADING_COMMENTS,
            'show_function' => 'built-in'
        ],
        'updated_by' => [
            'title' => TABLE_HEADING_UPDATED_BY,
            'align' => 'center',
            'show_function' => 'built-in'
        ],
    ];

    $zco_notifier->notify('EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT', $oID, $table_elements);
    if (!is_array($table_elements) || count($table_elements) == 0) {
        trigger_error('Non-array value returned from EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT: ' . json_encode($table_elements), E_USER_ERROR);
        exit();
    }

    $eo->eoLog('Preparing to display status history: ' . $eo->eoFormatArray($table_elements));

    // -----
    // Create the table's header, based on the current table-elements ...
    //
?>
    <tr class="dataTableHeadingRow v-top">
<?php
    foreach ($table_elements as $field_name => $field_values) {
        if (empty($field_values['title'])) {
            continue;
        }
        
        $align_class = '';
        if (isset($field_values['align'])) {
            switch ($field_values['align']) {
                case 'right':
                    $align_class = ' a-r';
                    break;
                case 'center':
                    $align_class = ' a-c';
                    break;
                default:
                    $align_class = ' a-l';
                    break;
            }
        }
        $table_elements[$field_name]['align_class'] = $align_class;
?>
        <td class="dataTableHeadingContent smallText<?php echo $align_class; ?>"><?php echo $field_values['title']; ?></td>
<?php
    }
?>
    </tr>
<?php
    // -----
    // Loop through each of the order's history records, displaying the columns as
    // identified in the current table elements.
    //
    foreach ($orders_history as $osh) {
?>
    <tr class="v-top">
<?php
        foreach ($table_elements as $field_name => $field_values) {
            // -----
            // If the current field name is not present in the orders_status_history
            // table, there's nothing to do.
            //
            if (!array_key_exists($field_name, $osh)) {
                continue;
            }

            // -----
            // Grab the current field's value to improve readability.
            //
            $field_value = $osh[$field_name];

            // -----
            // No show_function?  Then just output the associated field value.
            //
            if (empty($field_values['show_function'])) {
                $display_value = $field_value;
            } else {
                $show_function = $field_values['show_function'];

                // -----
                // Built-in function?  Make sure it's supported and then provide the output for the
                // current field.
                //
                if ($show_function === 'built-in') {
                    switch ($field_name) {
                        case 'orders_status_id':
                            $display_value = $orders_status_array[$field_value];
                            break;
                        case 'comments':
                            $display_value = nl2br(zen_output_string_protected($field_value));
                            break;
                        case 'updated_by':
                            $display_value = $field_value;
                            break;
                        default:
                            trigger_error("Unknown field ($field_name) for built-in function display.", E_USER_ERROR);
                            exit();
                            break;
                    }
                // -----
                // Otherwise, it's a 'specified' show_function.  Make sure it exists and then pass either one or
                // two arguments, depending on the table's configuration.
                //
                } else {
                    $show_function = $field_values['show_function'];
                    if (!function_exists($show_function)) {
                        trigger_error("Function ($show_function) to display '$field_name' does not exist.", E_USER_ERROR);
                        exit();
                    }
                    if (!empty($field_values['include_field_name']) && $field_values['include_field_name'] === true) {
                        $display_value = $show_function($field_value, $field_name);
                    } else {
                        $display_value = $show_function($field_value);
                    }
                }
            }

            // -----
            // Output the current field's display-value if there's an associated header-column.
            //
            if (!empty($field_values['title'])) {
?>
        <td class="smallText<?php echo $field_values['align_class']; ?>"><?php echo $display_value; ?></td>
<?php
            }
        }
?>
    </tr>
<?php
    }
}
?>
</table>
