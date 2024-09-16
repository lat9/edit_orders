<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003-2024 The zen-cart developers
//
// Last modified v5.0.0
//
?>
<hr class="my-2">
<div class="row">
    <h2><i class="fa fa-comments fa-lg"></i>&nbsp;<?= TABLE_HEADING_STATUS_HISTORY; ?></h2>
<?php
if (empty($order->statuses)) {
?>
    <div class="text-center h2"><?= TEXT_NO_ORDER_HISTORY ?></div>
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
            'include_field_name' => false,
        ],
        'customer_notified' => [
            'title' => TABLE_HEADING_CUSTOMER_NOTIFIED,
            'show_function' => 'eo_display_customers_notifications_icon',
            'align' => 'center',
            'include_field_name' => false,
        ],
        'orders_status_id' => [
            'title' => TABLE_HEADING_STATUS,
            'show_function' => 'built-in',
        ],
        'comments' => [
            'title' => TABLE_HEADING_COMMENTS,
            'show_function' => 'built-in',
        ],
        'updated_by' => [
            'title' => TABLE_HEADING_UPDATED_BY,
            'align' => 'center',
            'show_function' => 'built-in',
        ],
    ];

    $zco_notifier->notify('EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT', $oID, $table_elements);
    if (!is_array($table_elements) || count($table_elements) === 0) {
        trigger_error('Non-array value returned from EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT: ' . json_encode($table_elements), E_USER_WARNING);
        global $messageStack;
        $messageStack->add_session('An issue was detected by <em>Edit Orders</em>; see generated log for details.', 'error');
        zen_redirect(zen_href_link(FILENAME_ORDERS));
    }

    $eo->eoLog('Preparing to display status history: ' . $eo->eoFormatArray($table_elements));
?>
    <table class="table-condensed table-striped table-bordered">
        <thead>
<?php
    // -----
    // Create the table's header, based on the current table-elements ...
    //
?>
            <tr class="dataTableHeadingRow">
<?php
    foreach ($table_elements as $field_name => $field_values) {
        if (empty($field_values['title'])) {
            continue;
        }

        $align_class = '';
        if (isset($field_values['align'])) {
            switch ($field_values['align']) {
                case 'right':
                    $align_class = ' text-right';
                    break;
                case 'center':
                    $align_class = ' text-center';
                    break;
                default:
                    $align_class = '';
                    break;
            }
        }
        $table_elements[$field_name]['align_class'] = $align_class;
?>
                    <th class="dataTableHeadingContent<?php echo $align_class; ?>">
                        <?php echo $field_values['title']; ?>
                    </th>
<?php
    }
?>
            </tr>
        </thead>
        <tbody>
<?php
    // -----
    // Loop through each of the order's history records, displaying the columns as
    // identified in the current table elements, sorting based on the configuration setting added in v4.4.0.
    //
    $order->statuses[0]['protected_record'] = true;
    $order_statuses = (EO_STATUS_HISTORY_DISPLAY_ORDER === 'Desc') ? array_reverse($order->statuses) : $order->statuses;
    foreach ($order_statuses as $osh) {
?>
            <tr>
<?php
        foreach ($table_elements as $field_name => $field_values) {
            // -----
            // If the current field name is not present in the order's recorded
            // status-history table, nothing further to do.
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
                            if (isset($osh['protected_record'])) {
                                $display_value = nl2br(zen_output_string_protected($field_value ?? ''));
                            } else {
                                $display_value = nl2br($field_value ?? '');
                            }
                            break;

                        case 'updated_by':
                            $display_value = $field_value;
                            break;

                        default:
                            break;
                    }
                // -----
                // Otherwise, it's a 'specified' show_function, pass either one or
                // two arguments, depending on the table's configuration.
                //
                } else {
                    $show_function = $field_values['show_function'];
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
                <td class="<?php echo $field_values['align_class']; ?>"><?php echo $display_value; ?></td>
<?php
            }
        }
?>
            </tr>
<?php
    }
?>
        </tbody>
    </table>
<?php
}
?>
    <div class="row mt-4">
        <button id="add-comment" class="btn btn-warning" data-toggle="modal" data-target="#comment-modal" title="<?= BUTTON_ADD_COMMENT_ALT ?>">
            <?= BUTTON_ADD_COMMENT ?>
        </button>
    </div>

    <div id="comment-modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <form id="comment-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title text-center"><?= BUTTON_ADD_COMMENT_ALT ?></h4>
                    </div>

                    <div id="comment-form" class="modal-body">
                        <div class="form-group">
                            <label for="comments"><?= TABLE_HEADING_COMMENTS ?></label>
                            <?= zen_draw_textarea_field('comments', 'soft', '60', '5', '', 'id="comments" class="form-control"') ?>
                        </div>
<?php
// -----
// Give an observer the opportunity to add additional content to the status-history form.
//
// The additional-content array is numerically-indexed and provides the HTML to be included.
//
$additional_osh_content = [];
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_OSH_CONTENT', $order, $additional_osh_content);
if (is_array($additional_osh_content) && count($additional_osh_content) !== 0) {
    foreach ($additional_osh_content as $osh_content) {
?>
                        <div><?= $osh_content ?></div>
<?php
    }
}
?>
                        <div><?= ENTRY_CURRENT_STATUS . ' ' . $orders_status_array[$order->info['orders_status']] ?></div>

                        <div class="form-group">
                            <label for="new-status"><?= ENTRY_STATUS ?></label>
                            <?= zen_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status'], 'class="form-control"') ?>
                        </div>
<?php
// -----
// Determine the default setting for the customer notification, based on the configuration
// setting added in v4.4.0.
//
switch (EO_CUSTOMER_NOTIFICATION_DEFAULT) {
    case 'Hidden':
        $notify_email = false;
        $notify_no_email = false;
        $notify_hidden = true;
        break;
    case 'No Email':
        $notify_email = false;
        $notify_no_email = true;
        $notify_hidden = false;
        break;
    default:
        $notify_email = true;
        $notify_no_email = false;
        $notify_hidden = false;
        break;
}
?>
                        <div class="form-group">
                            <div class="control-label font-weight-bold"><?php echo ENTRY_NOTIFY_CUSTOMER; ?></div>
                            <label class="radio-inline"><?php echo zen_draw_radio_field('notify', '1', $notify_email) . TEXT_EMAIL; ?></label>
                            <label class="radio-inline"><?php echo zen_draw_radio_field('notify', '0', $notify_no_email) . TEXT_NOEMAIL; ?></label>
                            <label class="radio-inline"><?php echo zen_draw_radio_field('notify', '-1', $notify_hidden) . TEXT_HIDE; ?></label>
                        </div>

                        <div class="checkbox">
                            <label>
                                <?= zen_draw_checkbox_field('notify_comments', '', true) . '&nbsp;' . ENTRY_NOTIFY_COMMENTS ?>
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="btn-group btn-group-justified">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?= IMAGE_CANCEL ?></button>
                            </div>
                            <div class="btn-group">
                                <button id="comment-submit" type="button" class="btn btn-danger ms-2"><?= IMAGE_CONFIRM ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<hr>
