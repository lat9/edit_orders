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
        $('#ship-net').val(doRound(net, 4));
    }
});
</script>
<?php
}

//- Comparing an array of objects: https://stackoverflow.com/questions/27030/comparing-arrays-of-objects-in-javascript
?>
<script>
$(function() {
    // -----
    // Initialize the variout 'tooltip' elements.
    //
    $('[data-toggle="tooltip"]').tooltip();
<?php
// --------------------
// START ADDRESS-RELATED HANDLING
// --------------------

// -----
// EO **always** provides dropdown states in its various address displays.
//
// Note: The HTML structure that these jQuery methods are 'working with' is
// created by the eo_common_address_format.php module.
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
            countryZones = '<option selected="selected" value="0"><?= addslashes(PLEASE_SELECT) ?><' + '/option><option' + sorted.join('<option');
            $(this).parents('form').first().find('.state-input').val('').parent().hide();
            $(this).parents('form').first().find('.state-select').html(countryZones).prop('disabled', false).parent().show();
        } else {
            $(this).parents('form').first().find('.state-input').parent().show();
            $(this).parents('form').first().find('.state-select').prop('disabled', true).parent().hide();
        }
    });

    $('.state-select').on('change', function() {
        let selectedOption = $(this).val();
        $(this).find('option').prop('selected', false);
        $(this).find('option[value="'+selectedOption+'"]').prop('selected', true);
    });
<?php
}
?>
    // -----
    // When an address' modal is rendered, register for all changes to
    // input and select tags therein.
    //
    // Upon any change to those fields, set a warning-color border on the
    // field and display the form's "Save" button to enable recording
    // the update into EO's session-based changes.
    //
    $('.address-modal').on('shown.bs.modal', function() {
        if ($(this).find('.state-select > option').length > 1) {
            $(this).find('.state-input').parent().hide();
            $(this).find('.state-select').prop('disabled', false).parent().show();
        } else {
            $(this).find('.state-input').parent().show();
            $(this).find('.state-select').prop('disabled', true).parent().hide();
        }

        $(this).find('input:not(:hidden), select').on('change', function() {
            $(this).addClass('border-warning').removeClass('border-danger');
            $(this).siblings('.eo-field-error').remove();
            $(this).parents('form').first().find('.btn-save').show();
        });

        $(this).find('[data-toggle="tooltip"]').tooltip();
    });

    // -----
    // When an address' modal is closed and there haven't been any
    // changes to the associated address, remove all indication
    // of field-changes.
    //
    $('.address-modal').on('hidden.bs.modal', function() {
        if ($(this).find('.eo-changed').first().val() == 0) {
            $(this).find('input, select').removeClass('border-warning');
        }
    });

    // -----
    // When an address' "Save" button is clicked, the admin has
    // indicated that the changes associated with the address
    // are to be saved for the future update to the order.
    //
    $('.address-modal .btn-save').on('click', function() {
        let theButton = $(this);
        let theForm = theButton.parents('form').first();
        let addressType = theForm.find('.eo-addr-type').first().val();

        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=updateAddress',
            data: theForm.serializeArray()
        }).done(function(response) {
            if (response.status === 'ok') {
                theForm.find('.eo-changed').first().val(response.address_changes).trigger('change');
                $('#address-'+addressType).html(response.address);
                if (response.address_changes != 0) {
                    $('#address-'+addressType).addClass('border-warning');
                } else {
                    theForm.find('.border-warning, .border-danger').removeClass('border-warning border-danger');
                    $('#address-'+addressType).removeClass('border-warning');
                    theForm.find('span.eo-field-error').remove();
                }
                theForm.find('.btn-save').hide();
                $('#google-map-link-'+addressType).attr('href', response.google_map_link);
                theButton.parents('.address-modal').modal('hide');
            } else {
                $.each(response.error_messages, function(field_id, message) {
                    $('#'+field_id).addClass('border-danger').after('<span class="eo-field-error text-danger">'+message+'</span>');
                });
            }
        });
    });
<?php
// --------------------
// END ADDRESS-RELATED HANDLING
// --------------------

// --------------------
// START ADDITIONAL INFORMATION HANDLING, uses HTML generated by admin/edit_orders.php
// --------------------
?>
    $('#payment-method').on('change', function() {
        if ($('#payment-method').val() === '<?= TEXT_CREDIT_CARD ?>') {
            $('.cc-field').show().prop('disabled', false);
        } else {
            $('.cc-field').hide().prop('disabled', true);
        }
    });

    $('#eo-addl-info .eo-entry').on('change', function() {
        console.log($('#eo-addl-info :input').serializeArray());
    });
<?php
// --------------------
// END ADDITIONAL INFORMATION HANDLING
// --------------------

// --------------------
// START PRODUCTS' HANDLING
// --------------------
?>
    // -----
    // When the "Edit" button associated with an ordered product is clicked,
    // the product's update-modal form is displayed.
    //
    $(document).on('click', 'button.eo-btn-prod-edit', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=getProductUpdateModal',
            data: {
                uprid: $(this).attr('data-uprid'),
                payment_calc_method: $('#calc-method').find(':selected').val()
            }
        }).done(function(response) {
            $('#prod-edit-modal .modal-content').html(response.modal_content);
            $('#prod-edit-modal').modal();
        });
    });

    // -----
    // From a product's update-modal display, when the modal's "Update" button
    // is clicked, the entered information is (a) validated and (b) recorded in
    // the to-be-updated order if all's OK.
    //
    $(document).on('click', '#eo-prod-update', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=updateProduct',
            data: $('#prod-update-form').serializeArray()
        }).done(function(response) {
            if (response.status === 'error') {
                $('#prod-updated').removeClass('border-danger');
                $('#prod-updated span.eo-field-error').remove();
                $('#prod-messages, #attrib-messages').empty();
                $.each(response.messages, function(key, value) {
                    if (key === 'attributes') {
                        $('#attrib-messages').text(value).addClass('border-danger');
                    } else {
                        $('input[name="'+key+'"]').addClass('border-danger').after('<span class="eo-field-error text-danger">'+value+'</span>');
                    }
                });
            } else {
                $('#prod-edit-modal').modal('hide');
                $('#products-listing tr.eo-prod, #products-listing tr.eo-ot').remove();
                $('#products-listing > tbody').append(response.prod_table_html);
                $('#products-listing > tbody').append(response.ot_table_html);
                $('#product-changes').val(response.prod_changes).trigger('change');
                $('#ot-changes').val(response.ot_changes).trigger('change');
            }
        });
    });

    $(document).on('click', '#add-product', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=addNewProductStart',
        }).done(function(response) {
            $('#prod-edit-modal .modal-content').html(response.modal_content);
            $('#prod-edit-modal').modal();
        });
    });

    $(document).on('click', '#search-products', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=newProductSearch',
            data: $(this).closest('form').serializeArray()
        }).done(function(response) {
            $('#search-results').html(response.modal_content);
        });
    });

    $(document).on('change', '#choose-cat', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=getProductsInCategory',
            data: $(this).closest('form').serializeArray()
        }).done(function(response) {
            $('#cat-results').html(response.modal_content);
        });
    });

    $(document).on('click', '.prod-add', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=newProductChosen',
            data: $(this).closest('form').serializeArray()
        }).done(function(response) {
            $('#prod-edit-modal .modal-content').html(response.modal_content);
            $('#prod-edit-modal').modal();
        });
    });

    $(document).on('click', '#recalculate-pricing', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=recalculateNewProduct',
            data: $(this).closest('form').serializeArray()
        }).done(function(response) {
            $('#prod-edit-modal .modal-content').html(response.modal_content);
            $('#prod-edit-modal').modal();
        });
    });

    $(document).on('click', '#add-to-order', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=addNewProduct',
            data: $(this).closest('form').serializeArray()
        }).done(function(response) {
            if (response.status === 'error') {
                $('#prod-add-details').removeClass('border-danger');
                $('#prod-add-details span.eo-field-error').remove();
                $('#prod-messages, #attrib-messages').empty();
                $.each(response.messages, function(key, value) {
                    if (key === 'attributes') {
                        $('#attrib-messages').text(value).addClass('border-danger');
                    } else {
                        $('input[name="'+key+'"]').addClass('border-danger').after('<span class="eo-field-error text-danger">'+value+'</span>');
                    }
                });
            } else {
                $('#prod-edit-modal').modal('hide');
                $('#products-listing tr.eo-prod, #products-listing tr.eo-ot').remove();
                $('#products-listing > tbody').append(response.prod_table_html);
                $('#products-listing > tbody').append(response.ot_table_html);
                $('#product-changes').val(response.prod_changes).trigger('change');
                $('#ot-changes').val(response.ot_changes).trigger('change');
            }
        });
    });
<?php
// --------------------
// END PRODUCTS' HANDLING
// --------------------

// --------------------
// START ORDER-TOTALS' HANDLING
// --------------------
?>
    $(document).on('click', 'button.eo-btn-ot-edit', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=getOrderTotalUpdateModal',
            data: {
                ot_class: $(this).attr('data-ot-class'),
            }
        }).done(function(response) {
            $('#ot-edit-modal .modal-content').html(response.modal_content);
            $('#ot-edit-modal').modal();
        });
    });

    $(document).on('click', '#eo-add-ot', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=getOrderTotalAddModal',
            data: {
                ot_class: $('#eo-add-ot-code').find(':selected').val()
            }
        }).done(function(response) {
            $('#ot-edit-modal .modal-content').html(response.modal_content);
            $('#ot-edit-modal').modal();
        });
    });

    $(document).on('click', '#eo-ot-add-update', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=addOrUpdateOrderTotal',
            data: $('#ot-edit-modal form').serializeArray()
        }).done(function(response) {
            $('#ot-edit-modal').modal('hide');
            $('#products-listing tr.eo-ot').remove();
            $('#products-listing > tbody').append(response.ot_table_html);
            $('#ot-changes').val(response.ot_changes).trigger('change');
        });
    });
/*
        $('<input>').attr({
            type: 'hidden',
            name: 'ot_changed',
            value: $(this).attr('name')
        }).appendTo(closestRow);
        console.log(closestRow.find(':input').serializeArray());
        $('input[type="hidden"][name="ot_class"], input[type="hidden"][name="ot_changed"').remove();
        console.log(closestRow.find(':input').serializeArray());
    });
*/
    $('#eo-no-shipping').parent().hide();
<?php
// --------------------
// END ORDER-TOTALS' HANDLING
// --------------------

// --------------------
// START COMMENT HANDLING, uses HTML generated by eo_edit_action_osh_table_display.php
// --------------------
?>
    $('#comment-submit').on('click', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=addComment',
            data: $('#comment-form').serializeArray(),
        }).done(function(response) {
            if (response.status === 'ok') {
                $('#comment-added').val('1').trigger('change');
                $('#comment-remove').show();
                $('#add-comment').html('<?= BUTTON_REVIEW_COMMENT ?>').removeClass('btn-info').addClass('btn-warning');
                $('#comment-modal').modal('hide');
            }
        });
    });

    $('#comment-remove').on('click', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=removeComment',
        }).done(function(response) {
            if (response.status === 'ok') {
                $('#comment-added').val('0').trigger('change');
                $('#comment-remove').hide();
                $('#notify-customer input[value="'+response.notify_default+'"]').prop('checked', true);
                $('#comments').val('');
                $('#notify-comments').prop('checked', true);
                $('#new-status').val(response.orders_status).change();
                $('#add-comment').html('<?= BUTTON_ADD_COMMENT ?>').removeClass('btn-warning').addClass('btn-info');
                $('#comment-modal').modal('hide');
            }
        });
    });
<?php
// --------------------
// END COMMENT HANDLING
// --------------------

// --------------------
// START OVERALL HANDLING
// --------------------
?>
    $('#calc-method').on('change', function() {
        if (this.value === 'Manual') {
            $('.price-net, .price-gross').removeAttr('disabled');
        } else {
            $('.price-net, .price-gross').attr('disabled', 'disabled');
        }
    });

    // -----
    // When values in any of the various sections have changed,
    // count up the changes and display/hide the update form.
    //
    $('#eo-main .eo-changed').on('change', function() {
        let changeCount = 0;
        $('.eo-changed').each(function() {
            changeCount += parseInt(this.value);
        });
        if (changeCount !== 0) {
            $('#update-form-wrapper').show();
        } else {
            $('#update-form-wrapper').hide();
        }
    });

    $(document).on('click', '#update-verify', function() {
        zcJS.ajax({
            url: 'ajax.php?act=ajaxEditOrdersAdmin&method=getChangesModal',
            data: $('#update-form').serializeArray(),
        }).done(function(response) {
            if (response.status === 'ok') {
                $('#update-modal').html(response.modal_html).modal('show');
            }
        });
    });
    
    $(document).on('click', '#commit-changes', function() {
        $('#update-form').submit();
    });
<?php
// --------------------
// END OVERALL HANDLING
// --------------------
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
<script src="<?= $js_file ?>"></script>
<?php
        }
    }
}
