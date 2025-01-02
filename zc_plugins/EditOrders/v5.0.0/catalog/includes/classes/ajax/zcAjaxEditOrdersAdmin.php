<?php
// -----
// Part of the "Edit Orders" plugin by Cindy Merkin
// Copyright (c) 2024-2025 Vinos de Frutas Tropicales
//
// Last updated: v5.0.0 (new)
//
use Zencart\Plugins\Admin\EditOrders\EditOrders;
use Zencart\Plugins\Admin\EditOrders\EoAttributes;
use Zencart\Plugins\Admin\EditOrders\EoOrderChanges;
use Zencart\Traits\InteractsWithPlugins;
use Zencart\Traits\NotifierManager;

class zcAjaxEditOrdersAdmin
{
    use InteractsWithPlugins;
    use NotifierManager;

    // -----
    // Update one of the order's addresses, returning the HTML to
    // be placed into the address's <address> tag as well as the
    // link to the Google Map locator for the updated address.
    //
    public function updateAddress(): array
    {
        $form_fields = $_POST;

        $non_builtin_fields = [];
        $builtin_names2labels = $this->getBuiltInAddressFields();
        $builtin_address_names = array_keys($builtin_names2labels);
        $error = false;
        $builtin_errors = [];

        $address = [];
        $labels = [];
        $address_type_prefix = $form_fields['address_type'] . '_';
        foreach ($form_fields as $posted_varname => $value) {
            $varname = str_replace($address_type_prefix, '', $posted_varname);

            // -----
            // Check for EO's built-in address elements ...
            //
            if ($varname === 'address_type' || $varname === 'changed') {
                continue;
            }

            if (in_array($varname, $builtin_address_names)) {
                if ($varname === 'country') {
                    $address['country_id'] = (int)$value;
                    $labels['country_id'] = $builtin_names2labels['country'];
                } else {
                    $address[$varname] = trim($value);
                    $labels[$varname] = $builtin_names2labels[$varname];
                }
                continue;
            }

            // -----
            // Still here? An observer has added fields to the current address.
            // Save the field's name for follow-on check for any label.
            //
            $non_builtin_fields[] = $posted_varname;
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
        $address_type = $form_fields['address_type'];

        $non_builtin_errors = [];
        if (count($non_builtin_fields) !== 0) {
            $this->notify('NOTIFY_EO_ADDRESS_SAVE', $form_fields, $non_builtin_errors);
        }

        $status = ($error === true || count($non_builtin_errors) !== 0) ? 'error' : 'ok';
        if ($status === 'ok') {
            $non_builtin_labels = $_SESSION['eoChanges']->getAdditionalAddressFieldLabels($address_type);
            foreach ($non_builtin_fields as $next_field) {
                if (!empty($non_builtin_labels[$next_field])) {
                    $address[$next_field] = $form_fields[$next_field];
                    $labels[$next_field] = $non_builtin_labels[$next_field];
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
        $changes = $_SESSION['eoChanges']->getChangedValues();

        $modal_html = '';
        foreach ($changes as $title => $fields_changed) {
            if ($title === 'osh_info') {
                $modal_html .= $this->getOshChangesModal($fields_changed);
                continue;
            }

            if ($title === 'order_totals') {
                $modal_html .= $this->getOrderTotalsChangesModal($fields_changed);
                continue;
            }

            if ($title === 'products') {
                $modal_html .= $this->getProductsChangesModal($fields_changed);
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
    protected function getOshChangesModal(array $fields_changed): string
    {
        $modal_html = '';
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

        return $modal_html;
    }
    protected function getProductsChangesModal(array $fields_changed): string
    {
        $modal_html =
            '<div class="panel panel-default">' .
                '<div class="panel-heading">' . TEXT_PRODUCT_CHANGES . '</div>' .
                '<div class="panel-body">' .
                    '<ul class="list-group my-0">' . "\n";

        foreach ($fields_changed as $uprid => $next_change) {
            $modal_html .= '<li class="list-group-item">' . $next_change['label'] . '</li>';
        }

        $modal_html .=
                    '</ul>' .
                '</div>' .
            '</div>';
        return $modal_html;
    }

    protected function getOrderTotalsChangesModal(array $fields_changed): string
    {
        $modal_html =
            '<div class="panel panel-default">' .
                '<div class="panel-heading">' . TEXT_OT_CHANGES . '</div>' .
                '<div class="panel-body">' .
                    '<ul class="list-group my-0">' . "\n";

        foreach ($fields_changed as $next_change) {
            $original_value = '<code>' . ($next_change['original'] ?? '') . '</code>';
            $updated_value = '<code>' . ($next_change['updated'] ?? '') . '</code>';
            $label = '<strong>' . rtrim($next_change['label'], ':') . '</strong>';
            switch ($next_change['status']) {
                case 'removed':
                    $changes_text = sprintf(TEXT_ORDER_TOTAL_REMOVED, $label, $original_value);
                    break;
                case 'added':
                    $changes_text = sprintf(TEXT_ORDER_TOTAL_ADDED, $label, $updated_value);
                    break;
                default:
                    $changes_text = sprintf(TEXT_VALUE_CHANGED, $label, $original_value, $updated_value);
                    break;
            }
            $modal_html .= '<li class="list-group-item">' . $changes_text . '</li>';
        }

        $modal_html .=
                    '</ul>' .
                '</div>' .
            '</div>';
        return $modal_html;
    }

    protected function getBuiltInAddressFields(): array
    {
        return [
            'company' => ENTRY_CUSTOMER_COMPANY,
            'name' => ENTRY_CUSTOMER_NAME,
            'street_address' => ENTRY_CUSTOMER_ADDRESS,
            'suburb' => ENTRY_CUSTOMER_SUBURB,
            'city' => ENTRY_CUSTOMER_CITY,
            'postcode' => ENTRY_CUSTOMER_POSTCODE,
            'country' => ENTRY_CUSTOMER_COUNTRY,
            'zone_id' => ENTRY_CUSTOMER_STATE,
            'state' => ENTRY_CUSTOMER_STATE,
            'telephone' => ENTRY_TELEPHONE_NUMBER,
            'email_address' => ENTRY_EMAIL_ADDRESS,
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

    // -----
    // Get the modal form contents for an order-total's edit.
    //
    public function getOrderTotalUpdateModal(): array
    {
        return $this->getModalContent('eo_ot_update_modal.php');
    }

    // -----
    // Get the modal form contents for an order-total's addition to the order.
    //
    public function getOrderTotalAddModal(): array
    {
        return $this->getModalContent('eo_ot_add_modal.php');
    }

    // -----
    // Get the modal form contents for a product's edit.
    //
    public function getProductUpdateModal(): array
    {
        return $this->getModalContent('eo_prod_update_modal.php');
    }

    // -----
    // Get the modal form contents to start a new product's addition to the order. This
    // is where the initial product selection occurs.
    //
    public function addNewProductStart(): array
    {
        $parameters = [
            'prid' => 0,
        ];
        return $this->getModalContent('eo_prod_add_modal.php', $parameters);
    }

    // -----
    // Retrieve any products that match a new-product search string.
    //
    public function newProductSearch(): array
    {
        global $currencies;
        if (!class_exists('currencies')) {
            require DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php';
        }
        $currencies ??= new \currencies();

        $pulldown = new \productPulldown();
        $pulldown->showModel(true)->showPrice(true)->onlyActive(true)->showID(true);
        $product_dropdown = $pulldown->generatePulldownHtml('prid', 'id="select-search-prid" class="form-control" size="15"');
        $matching_products = substr_count($product_dropdown, '</option>');
        if ($matching_products === 0) {
            $product_dropdown = '';
        } elseif ($matching_products < 15) {
            $product_dropdown = str_replace('size="15"', 'size="' . $matching_products . '"', $product_dropdown);
        }

        $parameters = [
            'product_dropdown' => $product_dropdown,
            'hidden_fields' => ['choose_form', 'keywords'],
        ];

        return $this->getModalContent('eo_new_product_select.php', $parameters);
    }

    // -----
    // Retrieve any products that match a new-product search string.
    //
    public function getProductsInCategory(): array
    {
        global $currencies;
        if (!class_exists('currencies')) {
            require DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php';
        }
        $currencies ??= new \currencies();

        $pulldown = new \productPulldown();
        $pulldown->showModel(true)->showPrice(true)->onlyActive(true)->setCategory((int)($_POST['categories_id'] ?? '0'))->showID(true);
        $product_dropdown = $pulldown->generatePulldownHtml('prid', 'id="select-cat-prid" class="form-control" size="15"');
        $matching_products = substr_count($product_dropdown, '</option>');
        if ($matching_products === 0) {
            $product_dropdown = '';
        } elseif ($matching_products < 15) {
            $product_dropdown = str_replace('size="15"', 'size="' . $matching_products . '"', $product_dropdown);
        }

        $parameters = [
            'product_dropdown' => $product_dropdown,
            'hidden_fields' => ['choose_form', 'categories_id'],
        ];

        return $this->getModalContent('eo_new_product_select.php', $parameters);
    }

    // -----
    // Get the modal form contents for a new product's addition to the order,
    // once a product's ID has been chosen.
    //
    public function newProductChosen(): array
    {
        global $eo, $order;
        $eo = new EditOrders($_SESSION['eoChanges']->getOrderId());

        $prid = (int)$_POST['prid'];
        $choose_form = $_POST['choose_form'];
        $price_entry_disabled = 'disabled';
        $modal_variables = ['prid', 'price_entry_disabled', 'choose_form'];
        if ($prid !== 0) {
            $attributes = $this->getNewProductDefaultAttributes($prid);
            if (count($attributes) !== 0) {
                $uprid = zen_get_uprid($prid, $attributes);
                $modal_variables += ['uprid', 'attributes'];
            }
        }

        $product = [
            'qty' => '1',
            'attributes' => $attributes ?? [],
        ];
        $eo->setProductBeingAdded(true);
        $cart_product = $eo->addProductToCart($uprid ?? $prid, $product);

        $eo->createOrderFromCart();
        $eo->setProductBeingAdded(false);

        foreach ($order->products as $next_product) {
            if ($next_product['id'] != ($uprid ?? $prid)) {
                continue;
            }
            $new_product = $next_product;
            $new_product['uprid'] = $next_product['id'];
            $modal_variables[] = 'new_product';
            break;
        }

        $parameters = compact($modal_variables);
        return $this->getModalContent('eo_prod_add_modal.php', $parameters);
    }
    protected function getNewProductDefaultAttributes($prid): array
    {
        $attribs = new EoAttributes($prid);
        $options_values = $attribs->getOptionsValues();
        if (count($options_values) === 0) {
            return [];
        }

        $default_attributes = [];
        foreach ($options_values as $option_id => $option_values) {
            if (in_array($option_values['type'], [PRODUCTS_OPTIONS_TYPE_TEXT, PRODUCTS_OPTIONS_TYPE_FILE])) {
                continue;
            }

            $is_checkbox_option = ($option_values['type'] === PRODUCTS_OPTIONS_TYPE_CHECKBOX);
            unset($default);
            foreach ($option_values['values'] as $option_value_id => $value_info) {
                if ($is_checkbox_option === true) {
                    if ($value_info['attributes_default'] === '1') {
                        $default_attributes[$option_id . '_chk' . $option_value_id] = $option_value_id;
                    }
                } else {
                    $default ??= $option_value_id;
                    if ($value_info['attributes_default'] === '1') {
                        $default = $option_value_id;
                        break;
                    }
                }
            }
            if (isset($default)) {
                $default_attributes[$option_id] = $default;
            }
        }
        return $default_attributes;
    }

    // -----
    // Re-calculate pricing and taxes for a to-be-added product.
    //
    public function recalculateNewProduct(): array
    {
        global $eo, $order;
        $eo = new EditOrders($_SESSION['eoChanges']->getOrderId());

        $prid = (int)$_POST['prid'];
        $choose_form = $_POST['choose_form'];
        $price_entry_disabled = 'disabled';

        $product = $this->getPostedProduct();
        $attributes = $product['attributes'] ?? [];
        $uprid = zen_get_uprid($prid, $attributes);

        $modal_variables = ['prid', 'price_entry_disabled', 'choose_form', 'uprid', 'attributes'];

        $eo->setProductBeingAdded(true);
        $cart_product = $eo->addProductToCart($uprid, $product);

        $eo->createOrderFromCart();
        $eo->setProductBeingAdded(false);

        foreach ($order->products as $next_product) {
            if ($next_product['id'] != $uprid) {
                continue;
            }
            $new_product = $next_product;
            $new_product['uprid'] = $next_product['id'];
            $modal_variables[] = 'new_product';
            break;
        }

        $parameters = compact($modal_variables);
        return $this->getModalContent('eo_prod_add_modal.php', $parameters);
    }

    // -----
    // Add the new product to the order.
    //
    public function addNewProduct(): array
    {
        global $eo, $order;
        $eo = new EditOrders($_SESSION['eoChanges']->getOrderId());

        $zero_qty_ok = false;
        $messages = $this->updateProductCheckInputs($eo, $zero_qty_ok);
        if (count($messages) !== 0) {
            return [
                'status' => 'error',
                'messages' => $messages,
            ];
        }

        $_SESSION['eoChanges']->addNewProductToOrder($_POST['prid'], $this->getPostedProduct());

        return $this->processOrderUpdate();
    }

    // -----
    // Common method to load a specified modal template.
    //
    protected function getModalContent(string $modal_filename, array $parameters = []): array
    {
        // -----
        // Extract the modal's parameters, for use by the specified modal template.
        //
        extract($parameters);

        // -----
        // Use the base trait to determine this plugin's directory location.
        //
        $this->detectZcPluginDetails(__DIR__);

        global $eo;
        $eo ??= new EditOrders($_SESSION['eoChanges']->getOrderId());

        $this->disableGzip();
        ob_start();
        require $this->pluginManagerInstalledVersionDirectory . 'admin/' . DIR_WS_MODULES . $modal_filename;
        $modal_content = ob_get_clean();

        return [
            'status' => 'ok',
            'modal_content' => $modal_content,
        ];
    }

    // -----
    // Adding or updating an order-total.
    //
    public function addOrUpdateOrderTotal(): array
    {
        $_POST['title'] = rtrim($_POST['title'], ' :');
        switch ($_POST['ot_class']) {
            case 'ot_shipping':
                $_SESSION['eoChanges']->updateShippingInfo(
                    $_POST['module'],
                    $_POST['title'],
                    $_POST['value'],
                    $_POST['tax']
                );
                $_SESSION['shipping'] = [
                    'id' => $_POST['module'] . '_',
                    'title' => $_POST['title'],
                    'cost' => $_POST['value'],
                ];
                break;

            default:
                break;
        }
        return $this->processOrderUpdate();
    }

    // -----
    // Update an existing product in the order.
    //
    public function updateProduct(): array
    {
        global $eo;
        $eo = new EditOrders($_SESSION['eoChanges']->getOrderId());

        $zero_qty_ok = true;
        $messages = $this->updateProductCheckInputs($eo, $zero_qty_ok);
        if (count($messages) !== 0) {
            return [
                'status' => 'error',
                'messages' => $messages,
            ];
        }

        $_SESSION['eoChanges']->updateProductInOrder($_POST['uprid'], $this->getPostedProduct());

        return $this->processOrderUpdate();
    }
    protected function updateProductCheckInputs(EditOrders $eo, bool $zero_qty_ok): array
    {
        $messages = [];

        $updated_qty = $_POST['qty'];
        if (!is_numeric($updated_qty) || $updated_qty < 0 || ($zero_qty_ok === false && $updated_qty == 0)) {
            $messages['qty'] = ERROR_QTY_INVALID;
        }

        if (isset($_POST['tax'])) {
            $tax = $_POST['tax'];
            if (!is_numeric($tax) || $tax < 0 || $tax > 100) {
                $messages['tax'] = ERROR_TAX_RATE_INVALID;
            }
        }

        $model = $_POST['model'];
        if (strlen($model) > zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model')) {
            $messages['model'] = sprintf(ERROR_MODEL_TOO_LONG, zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model'));
        }

        $name = $_POST['name'];
        if (strlen($name) > zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name')) {
            $messages['name'] = sprintf(ERROR_NAME_TOO_LONG, zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name'));
        }

        if (isset($_POST['final_price'])) {
            if (!is_numeric($_POST['final_price']) || $_POST['final_price'] < 0) {
                $messages['final_price'] = ERROR_PRICE_INVALID;
            }
        }

        if (isset($_POST['onetime_charges'])) {
            if (!is_numeric($_POST['onetime_charges']) || $_POST['onetime_charges'] < 0) {
                $messages['onetime_charges'] = ERROR_PRICE_INVALID;
            }
        }

        if (!is_numeric($_POST['qty']) || $_POST['qty'] < 0) {
            $messages['qty'] = ERROR_QTY_INVALID;
        } elseif (STOCK_ALLOW_CHECKOUT === 'false') {
            $original_product = $_SESSION['eoChanges']->getOriginalProductByUprid($_POST['uprid']);
            $original_qty = $original_product['qty'] ?? 0;
            $qty_required = $eo->convertToIntOrFloat($_POST['qty']) - $original_qty;
            $available_qty = $eo->getProductsAvailableStock($_POST['uprid'], $_POST['id'] ?? []);
            if ($qty_required > $available_qty) {
                $messages['qty'] = sprintf(ERROR_QTY_INSUFFICIENT, (string)$available_qty);
            }
        }

        $additional_messages = [];
        $this->notify('NOTIFY_EO_PRODUCT_CHECK_INPUTS', ['messages' => $messages, 'post' => $_POST], $additional_messages);
        if (is_array($additional_messages)) {
            $messages = array_merge($messages, $additional_messages);
        }

        return $messages;
    }
    protected function getPostedProduct(): array
    {
        global $eo;

        $product = [
            'qty' => $eo->convertToIntOrFloat($_POST['qty']),
            'model' => $_POST['model'],
            'name' => $_POST['name'],
        ];
        if (isset($_POST['tax'])) {
            $product['tax'] = $eo->convertToIntOrFloat($_POST['tax']);
        }
        if (isset($_POST['final_price'])) {
            $product['final_price'] = $eo->convertToIntOrFloat($_POST['final_price']);
        }
        if (isset($_POST['onetime_charges'])) {
            $product['onetime_charges'] = $eo->convertToIntOrFloat($_POST['onetime_charges']);
        }
        if (isset($_POST['id'])) {
            $product['attributes'] = $_POST['id'];
        }
        return $product;
    }

    protected function processOrderUpdate(): array
    {
        global $currencies, $order, $eo;

        $eo ??= new EditOrders($_SESSION['eoChanges']->getOrderId());

        // -----
        // 'Create' the order (in global scope) from EO's cart. Note that the order-class doesn't include
        // a product's uprid (that's present in a product's 'id' element), so the id will
        // be copied to the uprid for the rest of EO's processing.
        //
        // Note: Depending on the current product-pricing method and potential
        // changes in a product's tax, some of the product's information might
        // have changed during the order's creation. Each updated product is submitted
        // to EO's change-monitor for those checks.
        //
        $eo->createOrderFromCart();
        foreach ($order->products as $index => $product) {
            $order->products[$index]['uprid'] = $order->products[$index]['id'];
            $_SESSION['eoChanges']->recordCreatedProductChanges($order->products[$index]['uprid'], $order->products[$index]);
        }

        $eo->eoLog("processOrderUpdate, products:\n" . $eo->eoFormatArray($order->products));

        $order_total_modules = $eo->getOrderTotalsObject();
        if (isset($_POST['dc_redeem_code'], $GLOBALS['ot_coupon']) && $_POST['dc_redeem_code'] !== $order->info['coupon_code']) {
            if (strtoupper($_POST['dc_redeem_code']) === TEXT_COMMAND_TO_DELETE_CURRENT_COUPON_FROM_ORDER) {
                unset($_SESSION['cc_id']);
                $order->info['coupon_code'] = '';
            } else {
                $coupon_id = $GLOBALS['ot_coupon']->performValidations($_POST['dc_redeem_code']);
                $coupon_errors = $GLOBALS['ot_coupon']->getValidationErrors();
                if (count($coupon_errors) === 0) {
                    $_SESSION['cc_id'] = $coupon_id;
                    $order->info['coupon_code'] = $_POST['dc_redeem_code'];
                } else {
                    $messages = [];
                    foreach ($coupon_errors as $next_message) {
                        $messages[] = ['params' => 'messageStackAlert alert alert-warning', 'text' => '<i class="fa-solid fa-2x fa-hand-stop-o"></i> ' . $next_message];
                    }
                    $table_block = new boxTableBlock();
                    return [
                        'status' => 'error',
                        'message_html' => $table_block->tableBlock($messages),
                    ];
                }
            }
        }
        $order->totals = $order_total_modules->process();

        // -----
        // Remove trailing underscore from shipping-module's code.
        //
        $order->info['shipping_module_code'] = rtrim($order->info['shipping_module_code'], '_');

        // -----
        // Record any changes to the order's info and totals.
        //
        $ot_changes = $_SESSION['eoChanges']->saveOrderInfoChanges($order->info);
        $ot_changes += $_SESSION['eoChanges']->saveOrderTotalsChanges($order->totals);
        $eo->eoLog(
            "Order totals updated.\nOriginal:\n" .
            $eo->eoFormatArray($_SESSION['eoChanges']->getOriginalOrder()->totals) .
            "\nUpdated:\n" .
            $eo->eoFormatArray($_SESSION['eoChanges']->getUpdatedOrder()->totals) .
            "\nChanges:\n" .
            $eo->eoFormatArray($_SESSION['eoChanges']->getTotalsChanges()) .
            "\not-totals:\n" .
            $eo->eoFormatArray($_SESSION['eo-totals'] ?? []) .
            "\nOriginal Info:\n" .
            $eo->eoFormatArray($_SESSION['eoChanges']->getOriginalOrder()->info) .
            "\nUpdated Info:\n" .
            $eo->eoFormatArray($_SESSION['eoChanges']->getUpdatedOrder()->info),
            'with-date'
        );

        // -----
        // Use the base trait to determine this plugin's directory location.
        //
        $this->detectZcPluginDetails(__DIR__);

        $this->disableGzip();
        ob_start();
        require $this->pluginManagerInstalledVersionDirectory . 'admin/' . DIR_WS_MODULES . 'eo_prod_table_display.php';
        $prod_table_html = ob_get_clean();

        ob_start();
        require $this->pluginManagerInstalledVersionDirectory . 'admin/' . DIR_WS_MODULES . 'eo_edit_action_ot_table_display.php';
        $ot_table_html = ob_get_clean();

        return [
            'status' => 'ok',
            'ot_changes' => $ot_changes,
            'prod_changes' => $_SESSION['eoChanges']->getProductsChangeCount(),
            'ot_table_html' => $ot_table_html,
            'prod_table_html' => $prod_table_html,
        ];
    }

    // -----
    // Gzip compression can "get in the way" of the AJAX requests on current versions of IE and
    // Chrome.
    //
    // This internal method sets that compression "off" for the AJAX responses.
    //
    protected function disableGzip()
    {
        ob_end_clean();
        ini_set('zlib.output_compression', '0');
    }
}
