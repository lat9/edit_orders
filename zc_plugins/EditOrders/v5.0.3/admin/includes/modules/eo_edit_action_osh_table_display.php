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
    <div class="row">
        <h2 class="d-inline"><i class="fa fa-comments fa-lg"></i>&nbsp;<?= TABLE_HEADING_STATUS_HISTORY ?></h2>
        <button id="add-comment" type="button" class="btn btn-info mb-2 ms-4 d-inline" data-toggle="modal" data-target="#comment-modal" title="<?= BUTTON_ADD_COMMENT_ALT ?>">
            <?= BUTTON_ADD_COMMENT ?>
        </button>
    </div>
<?php
if (empty($order->statuses)) {
?>
    <div class="text-center h2"><?= TEXT_NO_ORDER_HISTORY ?></div>
<?php
} else {
?>
    <table class="table-condensed table-striped table-bordered">
        <thead>
            <tr>
                <th class="text-center"><?= TABLE_HEADING_DATE_ADDED ?></th>
                <th class="text-center"><?= TABLE_HEADING_CUSTOMER_NOTIFIED ?></th>
                <th class="text-center"><?= TABLE_HEADING_STATUS ?></th>
<?php
    // -----
    // A watching observer can provide an associative array in the form:
    //
    // $extra_headings = array(
    //     array(
    //       'align' => $alignment,    // One of 'center', 'right', or 'left' (optional)
    //       'text' => $value
    //     ),
    // );
    //
    // Observer note:  Be sure to check that the $p2/$extra_headings value is specifically (bool)false before initializing, since
    // multiple observers might be injecting content!
    //
    $extra_headings = false;
    $zco_notifier->notify('NOTIFY_ADMIN_ORDERS_STATUS_HISTORY_EXTRA_COLUMN_HEADING', [], $extra_headings);
    if (is_array($extra_headings)) {
      foreach ($extra_headings as $heading_info) {
          $align = (isset($heading_info['align'])) ? (' class="text-' . $heading_info['align'] . '"') : '';
?>
                <th<?= $align ?>><?= $heading_info['text'] ?></th>
<?php
      }
  }
?>
                <th class="text-center"><?= TABLE_HEADING_COMMENTS ?></th>
                <th class="text-center"><?= TABLE_HEADING_UPDATED_BY ?></th>
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
    foreach ($order_statuses as $item) {
        switch ($item['customer_notified']) {
            case '1':
                $notify_icon = zen_icon('tick', TEXT_YES, 'lg');
                break;
            case '-1':
                $notify_icon = zen_icon('locked', TEXT_HIDDEN, 'lg');
                break;
            default:
                $notify_icon = zen_icon('unlocked', TEXT_VISIBLE, 'lg');
                break;
        }
?>
            <tr>
                <td class="text-center"><?= zen_datetime_short($item['date_added']) ?></td>
                <td class="text-center"><?= $notify_icon ?></td>
                <td><?= $orders_status_array[$item['orders_status_id']] ?? '' ?></td>
<?php
        // -----
        // A watching observer can provide an associative array in the form:
        //
        // $extra_data = array(
        //     array(
        //       'align' => $alignment,    // One of 'center', 'right' or 'left' (optional)
        //       'text' => $value
        //     ),
        // );
        //
        // Observer note:  Be sure to check that the $p2/$extra_data value is specifically (bool)false before initializing, since
        // multiple observers might be injecting content!
        //
        $extra_data = false;
        $zco_notifier->notify('NOTIFY_ADMIN_ORDERS_STATUS_HISTORY_EXTRA_COLUMN_DATA', $item, $extra_data);
        if (is_array($extra_data)) {
            foreach ($extra_data as $data_info) {
                $align = (isset($data_info['align'])) ? (' text-' . $data_info['align']) : '';
?>
                <td class="smallText<?= $align ?>"><?= $data_info['text'] ?></td>
<?php
            }
        }

        if (isset($item['protected_record'])) {
            $display_value = nl2br(zen_output_string_protected($item['comments']));
        } else {
            $display_value = nl2br($item['comments']);
        }
?>
                <td><?= $display_value ?></td>
                <td class="text-center"><?= (!empty($item['updated_by'])) ? $item['updated_by'] : '&nbsp;' ?></td>
            </tr>
<?php
    }
?>
        </tbody>
    </table>
<?php
}
?>
    <div id="comment-modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <form id="comment-form">
                <?= zen_draw_hidden_field('comment_added', '0', 'id="comment-added" class="eo-changed"') ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title text-center"><?= BUTTON_ADD_COMMENT_ALT ?></h4>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="comments"><?= TABLE_HEADING_COMMENTS ?></label>
                            <?= zen_draw_textarea_field('comments', 'soft', '60', '5', '', 'id="comments" class="form-control"') ?>
                        </div>

                        <div class="mb-4"><strong><?= ENTRY_CURRENT_STATUS ?></strong>&nbsp;<?= $orders_status_array[$order->info['orders_status']] ?></div>
<?php
// -----
// Give an observer the opportunity to add additional content to the status-history form.
//
$zco_notifier->notify('NOTIFY_ADMIN_ORDERS_ADDL_HISTORY_INPUTS', []);
?>
                        <div class="form-group">
                            <label for="new-status"><?= ENTRY_STATUS ?></label>
                            <?= zen_draw_pull_down_menu('status', $orders_statuses, $order->info['orders_status'], 'id="new-status" class="form-control"') ?>
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
                        <div id="notify-customer" class="form-group">
                            <div class="control-label font-weight-bold"><?= ENTRY_NOTIFY_CUSTOMER ?></div>
                            <label class="radio-inline"><?= zen_draw_radio_field('notify', '1', $notify_email) . TEXT_EMAIL ?></label>
                            <label class="radio-inline"><?= zen_draw_radio_field('notify', '0', $notify_no_email) . TEXT_NOEMAIL ?></label>
                            <label class="radio-inline"><?= zen_draw_radio_field('notify', '-1', $notify_hidden) . TEXT_HIDE ?></label>
                        </div>

                        <div class="checkbox">
                            <label>
                                <?= zen_draw_checkbox_field('notify_comments', '', true, '', 'id="notify-comments"') . '&nbsp;' . ENTRY_NOTIFY_COMMENTS ?>
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button id="comment-remove" type="button" class="btn btn-danger d-none"><?= BUTTON_REMOVE ?></button>
                        <button id="comment-submit" type="button" class="btn btn-warning mx-2"><?= IMAGE_SAVE ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= IMAGE_CANCEL ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<hr>
