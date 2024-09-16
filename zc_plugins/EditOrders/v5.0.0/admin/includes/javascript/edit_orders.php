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

    console.log($('#eo-addl-info form').serializeArray());
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
