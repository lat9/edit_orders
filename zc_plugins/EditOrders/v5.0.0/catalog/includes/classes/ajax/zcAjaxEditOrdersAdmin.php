<?php
// -----
// Part of the "Edit Orders" plugin by Cindy Merkin
// Copyright (c) 2024 Vinos de Frutas Tropicales
//
// Last updated: v5.0.0 (new)
//
use Zencart\Plugins\Admin\EditOrders\EoOrderChanges;
use Zencart\Traits\NotifierManager;

class zcAjaxEditOrdersAdmin
{
    use NotifierManager;

    // -----
    // Update one of the order's addresses, returning the HTML to
    // be placed into the address's <address> tag as well as the
    // link to the Google Map locator for the updated address.
    //
    public function updateAddress(): array
    {
        $form_fields = $this->postedJsonToArray($_POST['form_fields']);
        $form_labels = $this->postedJsonToArray($_POST['form_labels']);

        $non_builtin_fields_in_address = 0;
        $builtin_address_fields = $this->getBuiltInAddressFields();
        $error = false;
        $builtin_errors = [];

        $address = [];
        $labels = [];
        $address_type_prefix = 'update_' . $form_fields['address_type'] . '_';
        foreach ($form_fields as $posted_varname => $value) {
            $varname = str_replace($address_type_prefix, '', $posted_varname);

            // -----
            // Check for EO's built-in address elements ...
            //
            if ($varname === 'address_type' || $varname === 'changed') {
                continue;
            }

            if (in_array($varname, $builtin_address_fields)) {
                if ($varname === 'zone_id') {
                    $address['zone_id'] = $value;
                    $labels['zone_id'] = $form_labels[$address_type_prefix . 'state'];
                } elseif ($varname === 'country') {
                    $address['country_id'] = (int)$value;
                    $labels['country_id'] = $form_labels[$address_type_prefix . 'country'];
                } else {
                    $address[$varname] = trim($value);
                    $labels[$varname] = $form_labels[$posted_varname];
                }
                continue;
            }

            // -----
            // Still here? An observer has added fields to the current address.
            // Count them up here for determination as to whether a notification
            // needs to be issued.
            //
            $non_builtin_fields_in_address++;
        }

        // -----
        // If observers have added fields to the address, issue a notification to let
        // them validate those fields and supply any to-be-recorded updates to the
        // order itself.  The notification's parameters:
        //
        // 1. Albeit redundant, a copy of the form variables posted for this address.
        // 2. A associated array to contain any form-field error(s) found by
        //    the observer, in the format:
        //
        // $non_builtin_errors = [
        //     'field_id' => 'message',
        //     ...
        // ];
        //
        // ... where
        // - 'field_id' ... The HTML id= attribute associated with the errant field (no leading '#')
        // - 'message' .... The message to display for the field.
        //
        // 3. An associative array that maps the name of a posted variable to its name when stored
        //    in the database. The value-mapping is set to (bool)false if the field is not
        //    stored in the order.
        //
        //    For example, VAT4EU uses 'vat_number' as the form-field that is stored in the
        //    order as 'billing_vat_number' and includes a field named 'current_vat_number'
        //    that is not stored in the order. Here's how that plugin returns that information:
        //
        // $post_to_order_mapping = [
        //     'vat_number' => 'billing_vat_number',
        //     'vat_number_override' => false,
        //     'current_vat_number' => false,
        //     'current_vat_validated' => false,
        // ];
        //
        $address_type = $form_fields['address_type'];

        $non_builtin_errors = [];
        $post_to_order_mapping = [];
        if ($non_builtin_fields_in_address !== 0) {
            $this->notify('NOTIFY_EO_ADDRESS_SAVE', $form_fields, $non_builtin_errors, $post_to_order_mapping);
         }

        $status = ($error === true|| count($non_builtin_errors) !== 0) ? 'error' : 'ok';
        if ($status === 'ok') {
            foreach ($post_to_order_mapping as $key => $order_key) {
                if ($order_key !== false) {
                    $address[$order_key] = $form_fields[$key];
                    if (isset($form_labels[$key])) {
                        $labels[$order_key] = $form_labels[$key];
                    }
                }
            }
        }

        $address_changes = [];
        if ($status === 'ok') {
            $address_changes = $_SESSION['eoChanges']->updateAddressInfo($address_type, $address, $labels);
        }

        $zone_id = (int)($address['zone_id'] ?? 0);
        $state = ($zone_id === 0) ? ($address['state'] ?? '') : zen_get_zone_name((int)$address['country_id'], $zone_id);
        $google_map_address = urlencode($address['street_address'] . ',' . $address['city'] . ',' . $state . ',' . $address['postcode']);

        $address_format_id = zen_get_address_format_id($address['country_id']);
        return [
            'status' => $status,
            'address' => zen_address_format($address_format_id, $address, false, '', '<br>'),
            'google_map_link' => 'https://maps.google.com/maps/search/?api=1&amp;query=' . $google_map_address,
            'address_changes' => $address_changes,
            'error_messages' => array_merge($builtin_errors, $non_builtin_errors),
        ];
    }

    public function addComment(): array
    {
        $_SESSION['eoChanges']->addComment($_POST);

        return [
            'status' => 'ok',
        ];
    }

    public function removeComment(): array
    {
        $return = $_SESSION['eoChanges']->removeComment();
        $return['status'] = 'ok';
        return $return;
    }

    public function getChangesModal(): array
    {
        $original_order = $_SESSION['eoChanges']->getOriginalOrder();
        $updated_order = $_SESSION['eoChanges']->getUpdatedOrder();
        $changes = $_SESSION['eoChanges']->getChangedValues();

        $modal_html = '';
        foreach ($changes as $title => $fields_changed) {
            if ($title === 'osh_info') {
                $additional_inputs = '';
                foreach ($fields_changed[0]['updated'] as $key => $value) {
                    if (in_array($key, ['comment_added', 'status', 'notify_comments'])) {
                        continue;
                    }
                    switch ($key) {
                        case 'notify':
                            switch ($value) {
                                case 0:
                                    $customer_notified = TEXT_NO;
                                    break;
                                case 1:
                                    $customer_notified = TEXT_YES;
                                    break;
                                default:
                                    $customer_notified = TEXT_HIDDEN;
                                    break;
                            }
                            break;
                        case 'message':
                            if (!empty($value)) {
                                $message = '<br><br><code>' . $value . '</code>';
                            }
                            break;
                        default:
                            if (!empty($value)) {
                                $additional_inputs .= '<br><br><code>' . $key . '</code>: <code>' . $value . '</code>';
                            }
                            break;
                    }
                }

                $modal_html .=
                    '<div class="panel panel-default">' .
                        '<div class="panel-heading">' . TEXT_COMMENT_ADDED . '</div>' .
                        '<div class="panel-body">' .
                            '<ul class="list-group my-0">' .
                                '<li class="list-group-item">' .
                                    '<strong>' . ENTRY_NOTIFY_CUSTOMER . '</strong> ' . $customer_notified . ($message ?? '') . $additional_inputs .
                                '</li>' .
                            '</ul>' .
                        '</div>' .
                    '</div>';
                continue;
            }

            $modal_html .=
                '<div class="panel panel-default">' .
                    '<div class="panel-heading">' . $title . '</div>' .
                    '<div class="panel-body">' .
                        '<ul class="list-group my-0">' . "\n";

            foreach ($fields_changed as $next_change) {
                $original_value = '<code>' . $next_change['original'] . '</code>';
                $updated_value = '<code>' . $next_change['updated'] . '</code>';
                $label = '<strong>' . rtrim($next_change['label'], ':') . '</strong>';
                $modal_html .=
                            '<li class="list-group-item">' .
                                sprintf(TEXT_VALUE_CHANGED, $label, $original_value, $updated_value) .
                            '</li>';
            }

            $modal_html .=
                        '</ul>' .
                    '</div>' .
                '</div>';
        }

        $status = ($modal_html === '') ? 'error' : 'ok';
        if ($status === 'ok') {
            $modal_html =
                '<div class="modal-dialog">' .
                    '<div class="modal-content">' .
                        '<div class="modal-header">' .
                            '<button type="button" class="close" data-dismiss="modal">&times;</button>' .
                            '<h4 class="modal-title">Modal Header</h4>' .
                        '</div>' .
                        '<div class="modal-body">' .
                            $modal_html .
                        '</div>' .
                        '<div class="modal-footer">' .
                            '<button id="commit-changes" type="button" class="btn btn-danger">' . BUTTON_COMMIT_CHANGES . '</button>&nbsp;' .
                            '<button type="button" class="btn btn-default" data-dismiss="modal">' . BUTTON_CLOSE . '</button>' .
                        '</div>' .
                    '</div>' .
                '</div>';
        }
        return [
            'status' => $status,
            'modal_html' => $modal_html,
        ];
    }

    protected function getBuiltInAddressFields(): array
    {
        return [
            'country',
            'name',
            'street_address',
            'suburb',
            'city',
            'state',
            'postcode',
            'zone_id',
            'country',
            'telephone',
            'email_address',
        ];
    }

    protected function postedJsonToArray(string $json): array
    {
        // -----
        // The admin sanitization changes the double-quotes to &quot; ...
        // change them back prior to the json_decode.
        //
        $json_array = json_decode(str_replace('&quot;', '"', $json), true);

        $return_array = [];
        foreach ($json_array as $next_entry) {
            $return_array[$next_entry['name']] = $next_entry['value'];
        }
        return $return_array;
    }
}
