<?php
// -----
// Part of the "Edit Orders" plugin for Zen Cart.
//
// Last updated: EO v5.0.0
//

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
