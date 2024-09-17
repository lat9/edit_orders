<?php
// -----
// Part of the Edit Orders encapsulated plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003-2024 The zen-cart developers
//
// Last modified v5.0.0
//
// For versions prior to v5.0.0, this code was in-line in /admin/edit_orders.php.
//

if (DISPLAY_PRICE_WITH_TAX === 'true') {
?>
<script>
$(function() {
    $('.price-net, .price-tax').on('keyup', function(e) {
        var opi = $(this).attr('data-opi');
        updateProductGross(opi);
    });

    $('.price-gross').on('keyup', function(e) {
        var opi = $(this).attr('data-opi');
        updateProductNet(opi);
    });

    function doRound(x, places)
    {
        return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
    }

    function getProductTaxRate(opi)
    {
        return getValidatedTaxRate($('input[name="update_products['+opi+'][tax]"]').val());
    }
    function getValidatedTaxRate(taxRate)
    {
        var regex = /(?:\d*\.\d{1,2}|\d+)$/;
        return (regex.test(taxRate)) ? taxRate : 0;
    }

    function updateProductGross(opi)
    {
        var taxRate = getProductTaxRate(opi);
        var gross = $('input[name="update_products['+opi+'][final_price]"]').val();

        if (taxRate > 0) {
            gross = gross * ((taxRate / 100) + 1);
        }
        $('input[name="update_products['+opi+'][gross]"]').val(doRound(gross, 4));
    }

    function updateProductNet(opi)
    {
        var taxRate = getProductTaxRate(opi);
        var net = $('input[name="update_products['+opi+'][gross]"]').val();

        if (taxRate > 0) {
            net = net / ((taxRate / 100) + 1);
        }
        $('input[name="update_products['+opi+'][final_price]"]').val(doRound(net, 4));
    }

    $('#ship-tax, #ship-net').on('keyup', function(e) {
        updateShippingGross();
    });
    $('#ship-gross').on('keyup', function(e) {
        updateShippingNet();
    });

    function getShippingTaxRate()
    {
        return getValidatedTaxRate($('#ship-tax').val());
    }

    function updateShippingGross()
    {
        var taxRate = getShippingTaxRate();
        var gross = $('#ship-net').val();
        if (taxRate > 0) {
            gross = gross * ((taxRate / 100) + 1);
        }
        $('#ship-gross').val(doRound(gross, 4));
    }

    function updateShippingNet()
    {
        var taxRate = getShippingTaxRate();
        var net = $('#ship-gross').val();
        if (taxRate > 0) {
            net = net / ((taxRate / 100) + 1);
        }
        $('#s-n').val(doRound(net, 4));
    }
});
</script>
<?php
}

//- Comparing an array of objects: https://stackoverflow.com/questions/27030/comparing-arrays-of-objects-in-javascript
?>
<script>
$(function() {
    $('#comment-submit').on('click', function() {
        console.log($('#comment-form').serializeArray());
    });

    $('#payment-method').on('change', function() {
        if ($('#payment-method').val() === '<?= TEXT_CREDIT_CARD ?>') {
            $('.cc-field').show();
        } else {
            $('.cc-field').hide();
        }
    });

    $('#calc-method').on('change', function() {
        if (this.value === '3') {
            $('.price-net, .price-gross').removeAttr('disabled');
        } else {
            $('.price-net, .price-gross').attr('disabled', 'disabled');
        }
    });

//    console.log($('#eo-addl-info form').serializeArray());
<?php
// -----
// If the site uses states in its addresses, EO **always** provides dropdown
// states in its various address displays.
//
if (ACCOUNT_STATE === 'true') {
    // -----
    // Create the array that identifies the various zones for the currently-active countries.
    //
    // Derived from the storefront's /jscript/zen_addr_pulldowns.php.
    //
    $c2z = [];

    // -----
    // If the current site has at least one country enabled that uses zones, a JSON-encoded array of
    // countries-to-zones will be created for use by the jQuery section.
    //
    $countries = $db->Execute(
        "SELECT DISTINCT zone_country_id
           FROM " . TABLE_ZONES . "
                INNER JOIN " . TABLE_COUNTRIES . "
                    ON countries_id = zone_country_id
                   AND status = 1
       ORDER BY zone_country_id"
    );
    foreach ($countries as $next_country) {
        $current_country_id = $next_country['zone_country_id'];
        $c2z[$current_country_id] = [];

        $states = zen_get_country_zones($current_country_id);
        foreach ($states as $next_state) {
            $c2z[$current_country_id][$next_state['id']] = $next_state['text'];
        }
    }
?>
    const country_zones = '<?= addslashes(json_encode($c2z)) ?>';

    $('.address-modal').on('shown.bs.modal', function() {
        if ($(this).find('.state-select > option').length > 1) {
            $(this).find('.state-input').hide();
            $(this).find('.state-select').show();
        } else {
            $(this).find('.state-input').show();
            $(this).find('.state-select').hide();
        }
    });

    $('.address-country').on('change', function() {
        var countryHasZones = false;
        var countryZones = '';
        var selected_country = $('option:selected', this).val();
        $.each(JSON.parse(country_zones), function(country_id, country_zones) {
            if (selected_country === country_id) {
                countryHasZones = true;
                $.each(country_zones, function(zone_id, zone_name) {
                    countryZones += '<option label ="' + zone_name + '" value="' + zone_id + '">' + zone_name + '<' + '/option>';
                });
            }
        });

        if (countryHasZones) {
            var split = countryZones.split('<option').filter(function(el) {
                return el.length != 0
            });
            var sorted = split.sort();
            countryZones = '<option selected="selected" value="0"><?php echo addslashes(PLEASE_SELECT); ?><' + '/option><option' + sorted.join('<option');
            $(this).parents('.country-wrapper').first().siblings('.state-wrapper').first().find('.state-input').hide();
            $(this).parents('.country-wrapper').first().siblings('.state-wrapper').first().find('.state-select').html(countryZones).show();
        } else {
            $(this).parents('.country-wrapper').first().siblings('.state-wrapper').first().find('.state-input').show();
            $(this).parents('.country-wrapper').first().siblings('.state-wrapper').first().find('.state-select').hide();
        }
    });
<?php
}
?>
});
</script>
<?php
// -----
// Give a watching observer the opportunity to identify additional .js files, present
// in the /admin/includes/javascript sub-directory, for inclusion in EO's display
// processing.
//
// The observer sets the $addl_js_files value passed to be a comma-separated list
// of file names to be included.
//
// Observer note:  Be sure to add a leading ', ' to any updates if, on receipt of the
// notification, the $addl_js_files (i.e. $p2) is not empty!
//
$addl_js_files = '';
$zco_notifier->notify('EDIT_ORDERS_ADDITIONAL_JS', '', $addl_js_files);
if (!empty($addl_js_files)) {
    $js_files = explode(',', str_replace(' ', '', (string)$addl_js_files));
    foreach ($js_files as $js_filename) {
        if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9\.\-_]*$/', $js_filename)) {
            $eo->eoLog("Additional javascript file ($js_filename) not included, due to filename character mismatch.");
        } else {
            $js_file = DIR_WS_INCLUDES . 'javascript/' . "$js_filename.js";
?>
<script src="<?php echo $js_file; ?>"></script>
<?php
        }
    }
}
