<?php
// -----
// Part of the "Edit Orders" plugin by Cindy Merkin
// Copyright (c) 2024 Vinos de Frutas Tropicales
//
// Last updated: v5.0.0 (new)
//
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
        foreach ($_POST as $varname => $value) {
            if (str_ends_with($varname, '_company')) {
                $address['company'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_name')) {
                $first_last = explode(' ', $value);
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
                $address['suburb'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_city')) {
                $address['city'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_state')) {
                $address['state'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_zone_id')) {
                $address['zone_id'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_postcode')) {
                $address['postcode'] = $value;
                continue;
            }

            if (str_ends_with($varname, '_country')) {
                $address['country_id'] = (int)$value;
                continue;
            }
        }


        $zone_id = (int)($address['zone_id'] ?? 0);
        $state = ($zone_id === 0) ? ($address['state'] ?? '') : zen_get_zone_name((int)$address['country_id'], $zone_id);
        $google_map_address = urlencode($address['street_address'] . ',' . $address['city'] . ',' . $state . ',' . $address['postcode']);

        $address_format_id = zen_get_address_format_id($address['country_id']);
        return [
            'status' => $status,
            'address' => zen_address_format($address_format_id, $address, false, '', '<br>'),
            'google_map_link' => 'https://maps.google.com/maps/search/?api=1&amp;query=' . $google_map_address,
        ];
    }
}
