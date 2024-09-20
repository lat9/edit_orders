<?php
// -----
// Part of the "Edit Orders" plugin by Cindy Merkin
// Copyright (c) 2024 Vinos de Frutas Tropicales
//
// Last updated: v5.0.0 (new)
//
use Zencart\Plugins\Admin\EditOrders\EoOrderChanges;

class zcAjaxEditOrdersAdmin
{
    // -----
    // Update one of the order's addresses, returning the HTML to
    // be placed into the address's <address> tag as well as the
    // link to the Google Map locator for the updated address.
    //
    public function updateAddress(): array
    {
        $status = 'ok';
        $address = [];
        $non_builtin_address_fields = 0;
        foreach ($_POST as $varname => $value) {
            // -----
            // Check for EO's built-in address elements ...
            //
            if ($varname === 'address_type' || str_ends_with($varname, '_changed')) {
                continue;
            }

            if (str_ends_with($varname, '_company')) {
                $address['company'] = trim($value);
                continue;
            }

            if (str_ends_with($varname, '_name')) {
                $first_last = explode(' ', trim($value));
                $address['firstname'] = $first_last[0];
                unset($first_last[0]);
                $address['lastname'] = implode(' ', $first_last);
                continue;
            }

            if (str_ends_with($varname, '_street_address')) {
                $address['street_address'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_suburb')) {
                $address['suburb'] = trim($value);
                continue;
            }

            if (str_ends_with($varname, '_city')) {
                $address['city'] = trim($value);
                continue;
            }

            if (str_ends_with($varname, '_state')) {
                $address['state'] = trim($value);
                continue;
            }

            if (str_ends_with($varname, '_zone_id')) {
                $address['zone_id'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_postcode')) {
                $address['postcode'] = trim($value);
                continue;
            }

            if (str_ends_with($varname, '_country')) {
                $address['country_id'] = (int)$value;
                continue;
            }

            // -----
            // Still here? An observer has added fields to the current address.
            // Count them up here for determination as to whether a notification
            // needs to be issued.
            //
            $non_builtin_address_fields++;
        }

        // -----
        // If observers have added fields to the address, issue a notification to let
        // them validate those fields and supply any to-be-recorded updates to the
        // order itself.
        //
        if ($non_builtin_address_fields !== 0) {
        }

        $zone_id = (int)($address['zone_id'] ?? 0);
        $state = ($zone_id === 0) ? ($address['state'] ?? '') : zen_get_zone_name((int)$address['country_id'], $zone_id);
        $google_map_address = urlencode($address['street_address'] . ',' . $address['city'] . ',' . $state . ',' . $address['postcode']);

        $address_format_id = zen_get_address_format_id($address['country_id']);
        return [
            'status' => $status,
            'address' => zen_address_format($address_format_id, $address, false, '', '<br>'),
            'google_map_link' => 'https://maps.google.com/maps/search/?api=1&amp;query=' . $google_map_address,
            'address_changes' => $_SESSION['eoChanges']->updateAddressInfo($_POST['address_type'], $address),
        ];
    }
}
