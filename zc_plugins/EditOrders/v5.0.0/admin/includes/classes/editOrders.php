<?php
// -----
// Part of the Edit Orders plugin by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016-2024, Vinos de Frutas Tropicales
//
// Last updated: EO v5.0.0
//
class EditOrders extends base
{
    protected int $eo_action_level;
    protected string $logfile_name;
    protected int $orders_id;
    public bool $tax_updated;
    protected array $product_tax_descriptions;
    protected $shipping_tax_rate;
    protected $shipping_tax_description;
    protected int $ot_sort_default;

    protected bool $orderHasShipping;

    protected \order $order;

    public function __construct(int $orders_id)
    {
        $this->eo_action_level = (int)EO_DEBUG_ACTION_LEVEL;
        $this->orders_id = (int)$orders_id;
        $this->tax_updated = false;
        $this->product_tax_descriptions = [];

        // -----
        // Create the edit_orders directory, if not already present.
        //
        if ($this->eo_action_level !== 0) {
            $this->logfile_name = DIR_FS_LOGS . '/eo_debug_' . $orders_id . '.log';
        }

        // -----
        // Load the order-information currently recorded for the order.
        //
        $this->order = new \order($this->orders_id);
        $this->eoLog("queryOrder, initial\n" . json_encode($this->order, JSON_PRETTY_PRINT));

        // -----
        // Save the current value for the session-stored currency (in case the order was
        // placed in a different one).  The value will be restored EO's initialization routine
        // is run.
        $_SESSION['eo_saved_currency'] = $_SESSION['currency'] ?? false;
        $_SESSION['currency'] = $this->order->info['currency'];
    }

    public function isOrderFound(): bool
    {
        return !empty($this->order->info);
    }

    // -----
    // Called close to the start of the main edit_orders page processing. For
    // EO versions prior to 5.0.0, the majority of these checks were in
    // the eo_checks_and_warnings function.
    //
    public function checkEnvironment(): void
    {
        global $db, $messageStack;

        // -----
        // Ensure that some 'base' hidden configuration elements are present; they've been removed at times
        // by plugins' uninstall SQL scripts.
        //
        $reload = (!defined('PRODUCTS_OPTIONS_TYPE_SELECT') || !defined('UPLOAD_PREFIX') || !defined('TEXT_PREFIX'));
        if ($reload === true) {
            $db->Execute(
                "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added)
                 VALUES
                    ('Product option type Select', 'PRODUCTS_OPTIONS_TYPE_SELECT', '0', 'The number representing the Select type of product option.', 6, now()),
                    ('Upload prefix', 'UPLOAD_PREFIX', 'upload_', 'Prefix used to differentiate between upload options and other options', 6, now()),
                    ('Text prefix', 'TEXT_PREFIX', 'txt_', 'Prefix used to differentiate between text option values and other options', 6, now())"
            );
            zen_redirect(zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params()));
        }

        // -----
        // Check to be sure that the admin's zen_add_tax function has been updated to remove
        // the unwanted pre-rounding that affects EO's calculations, denying
        // the usage of Edit Orders until the issue is resolved.
        //
        $value = zen_add_tax(5.1111, 0);
        if ($value != 5.1111) {
            $messageStack->add_session(ERROR_ZEN_ADD_TAX_ROUNDING, 'error');
            zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params()));
        }

        // -----
        // The site's display of prices with/without tax must be the same on the
        // admin and storefront; otherwise, any update to the order's information
        // would be suspect.
        //
        if (DISPLAY_PRICE_WITH_TAX_ADMIN !== DISPLAY_PRICE_WITH_TAX) {
            $messageStack->add_session(ERROR_DISPLAY_PRICE_WITH_TAX, 'error');
        }

        // -----
        // Issue a notification, allowing other add-ons to add any warnings they might have.
        //
        $this->notify('EDIT_ORDERS_CHECKS_AND_WARNINGS');

        // Check for the installation of "Absolute's Product Attribute Grid"
        if (!defined('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID')) {
            if (defined('CONFIG_ATTRIBUTE_OPTION_GRID_INSTALLED')) {
                define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', '23997');
                $messageStack->add(WARNING_ATTRIBUTE_OPTION_GRID, 'warning');
            } else {
                define('PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID', '-1');
            }
        }

        // Check for the installation of "Potteryhouse's/mc12345678's Stock By Attributes"
        zen_define_default('PRODUCTS_OPTIONS_TYPE_SELECT_SBA', '-1');

        // -----
        // Check for the installation of lat9's "Attribute Image Swapper".
        //
        zen_define_default('PRODUCTS_OPTIONS_TYPE_IMAGE_SWATCH', -1);
    }

    public function getOrder(): \order
    {
        return $this->order;
    }

    public function queryOrder(): bool
    {
        // -----
        // The base order-class' 'query' method processing sets the order's delivery address to
        // (bool)false if the shipping module is 'storepickup'. If that's the case,
        // restore the delivery-address information from the database.
        //
        if ($this->order->delivery === false) {
            global $db;
            $delivery_address = $db->Execute(
                "SELECT delivery_name AS `name`, delivery_company AS `company`, delivery_street_address AS street_address,
                        delivery_suburb AS suburb, delivery_city AS city, delivery_postcode AS postcode, delivery_state as `state`,
                        delivery_country AS country, delivery_address_format_id AS format_id
                   FROM " . TABLE_ORDERS . "
                  WHERE orders_id = " . $this->orders_id . "
                  LIMIT 1"
            );
            if ($delivery_address->EOF) {
                $this->order->delivery = [];
            } else {
                $this->order->delivery = $delivery_address->fields;
            }
        }

        // -----
        // While the addresses in the storefront instance of an 'order' object, created by the
        // order-class' 'cart' method, contain 'state_code', 'zone_id' and 'country_id' elements,
        // the 'query' method doesn't.
        //
        $this->order->customer = $this->orderAddressFixup($this->order->customer);
        $this->order->delivery = $this->orderAddressFixup($this->order->delivery);
        $this->order->billing = $this->orderAddressFixup($this->order->billing);

        // -----
        // The storefront order's processing contains various pricing and flags in a float/int
        // format, while the order's query from the database has these as their string
        // representation.
        //
        // There are also some fields in a storefront order's 'info' array that aren't present
        // in an order's query.
        //
        $this->addOrConvertOrderFields();

        // -----
        // Set the content type for this order.
        //
        $this->setContentType();

        // -----
        // An order's query (as pulled from the database) doesn't match the storefront
        // signature when created from the cart.  Specifically, the order-object's tax_groups
        // aren't filled in for either the info nor products elements.
        //
        $tax_groups_created = $this->createOrderTaxGroups();

        $this->eoLog("queryOrder, on exit\n" . json_encode($this->order, JSON_PRETTY_PRINT));

        return $tax_groups_created;
    }

    // -----
    // For a given order-address, add the elements to the order that are
    // present during checkout processing, but not when the order is queried.
    //
    protected function orderAddressFixup(array $address_info): array
    {
        if (empty($address_info)) {
            return [];
        }

        $country_id = (int)$address_info['country']['id'];
        $address_info['country_id'] = $country_id;

        global $db;
        $state = zen_db_input($address_info['state']);
        $zone_query = $db->Execute(
            "SELECT * 
               FROM " . TABLE_ZONES . " 
              WHERE zone_country_id = $country_id 
                AND (zone_name = '$state' OR zone_code = '$state')
              LIMIT 1"
        );
        if ($zone_query->EOF) {
            $address_info['state_code'] = '';
            $address_info['zone_id'] = '0';
        } else {
            $address_info['state_code'] = $zone_query->fields['zone_code'];
            $address_info['zone_id'] = $zone_query->fields['zone_id'];
        }

        $address_info['format_id'] = (int)$address_info['format_id'];

        return $address_info;
    }

    // -----
    // Add fields that aren't present in a queried order and convert others
    // to align with their value-type during the checkout processing.
    //
    protected function addOrConvertOrderFields(): void
    {
        // -----
        // Add the 'info' array elements that aren't present in an order's query.
        //
        $this->order->info['subtotal'] = $this->getOrderTotalValue('ot_subtotal');

        // -----
        // Update various pricing elements within the order's info array
        // to match a storefront cart-created order's layout.
        //
        $this->order->info['total'] = (float)$this->order->info['total'];
        $this->order->info['tax'] = (float)$this->order->info['tax'];

        // -----
        // The order-class' query of an order doesn't include each product's unique
        // id (the products_prid). Gather that information for use in rebuilding
        // each product's record.
        //
        global $db;
        $uprids_from_db = $db->Execute(
            "SELECT orders_products_id, products_prid
               FROM " . TABLE_ORDERS_PRODUCTS . "
              WHERE orders_id = " . $this->orders_id
        );
        $uprids = [];
        foreach ($uprids_from_db as $next_record) {
            $uprids[$next_record['orders_products_id']] = $next_record['products_prid'];
        }

        // -----
        // Update various fields within the order's products' array to match the
        // format used in the storefront.
        //
        foreach ($this->order->products as &$next_product) {
            $next_product['qty'] = $this->convertToIntOrFloat($next_product['qty']);
            $next_product['tax'] = (float)$next_product['tax'];
            $next_product['final_price'] = (float)$next_product['final_price'];
            $next_product['onetime_charges'] = $this->convertToIntOrFloat($next_product['onetime_charges']);
            $next_product['weight'] = $this->convertToIntOrFloat($next_product['products_weight']);
            $next_product['products_weight'] = (float)$next_product['products_weight'];
            $next_product['products_virtual'] = (int)$next_product['products_virtual'];
            $next_product['product_is_always_free_shipping'] = (int)$next_product['product_is_always_free_shipping'];
            $next_product['products_quantity_order_min'] = (float)$next_product['products_quantity_order_min'];
            $next_product['products_quantity_order_units'] = (float)$next_product['products_quantity_order_units'];
            $next_product['products_quantity_order_max'] = (float)$next_product['products_quantity_order_max'];
            $next_product['products_quantity_mixed'] = (int)$next_product['products_quantity_mixed'];
            $next_product['products_mixed_discount_quantity'] = (int)$next_product['products_mixed_discount_quantity'];
            $next_product['uprid'] = $uprids[$next_product['orders_products_id']];

            if (!isset($next_product['attributes'])) {
                continue;
            }

            for ($i = 0, $n = count($next_product['attributes']); $i < $n; $i++) {
                $next_product['attributes'][$i]['option_id'] = (int)$next_product['attributes'][$i]['option_id'];
            }
        }
    }

    // -----
    // Retrieve an order-total's value for the order.
    //
    protected function getOrderTotalValue(string $class): int|float|null
    {
        foreach ($this->order->totals as $next_total) {
            if ($next_total['class'] === $class) {
                return $this->convertToIntOrFloat($next_total['value']);
            }
        }
        return null;
    }

    // -----
    // Convert a string value to either an int or float, depending on
    // the presence of a '.' in the value.
    //
    protected function convertToIntOrFloat(string $value): int|float
    {
        if (strpos($value, '.') === false) {
            return (int)$value;
        }
        return (float)$value;
    }

    // -----
    // Set the order's 'content_type', checking whether each product is virtual, is
    // a gift-certificate or includes a downloadable product.
    //
    protected function setContentType(): void
    {
        global $db;

        $virtual_products = 0;
        foreach ($this->order->products as $current_product) {
            $products_id = (int)$current_product['id'];
            if ($current_product['products_virtual'] === 1 || strpos($current_product['model'], 'GIFT') === 0) {
                $virtual_products++;
            } elseif (isset($current_product['attributes'])) {
                foreach ($current_product['attributes'] as $current_attribute) {
                    $download_check = $db->Execute(
                        "SELECT opa.orders_products_id
                           FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa
                                INNER JOIN " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " opd
                                    ON opd.products_attributes_id = opa.orders_products_attributes_id
                          WHERE opa.orders_products_id = $products_id
                            AND opa.orders_id = " . (int)$this->orders_id . "
                            AND opa.products_options_values_id = " . (int)$current_attribute['value_id'] . "
                            AND opa.products_options_id = " . (int)$current_attribute['option_id'] . "
                          LIMIT 1"
                    );
                    $this->eoLog("\tChecking whether the product's attribute is a download, option_id = " . $current_attribute['option_id'] . ", value_id = " . $current_attribute['value_id'] . ": (" . $download_check->EOF . ")");
                    if (!$download_check->EOF) {
                        $virtual_products++;
                        break;  //-Out of foreach attributes loop
                    }
                }
            }
        }

        $product_count = count($this->order->products);
        $this->eoLog("\nsetContentType: Order contains $product_count unique products, $virtual_products of those are virtual");

        if ($virtual_products === 0) {
            $this->order->content_type = 'physical';
        } elseif ($virtual_products === $product_count) {
            $this->order->content_type = 'virtual';
        } else {
            $this->order->content_type = 'mixed';
        }
    }

    // -----
    // Attempt to recreate the order's tax-groups, based on the information
    // stored in the order's 'ot_tax' records.
    //
    protected function createOrderTaxGroups(): bool
    {
        // -----
        // First, traverse the recorded 'ot_tax' entries for the order,
        // attempting to reconstruct the storefront 'tax_groups' array.
        //
        if ($this->addTaxGroups() === false) {
            return false;
        }

        // -----
        // Next, determine which (if any) of the tax_group entries is associated
        // with the order's shipping.
        //
        if ($this->addShippingTaxGroup() === false) {
            return false;
        }

        // -----
        // Finally, traverse the products in the order, adding each product's cost to
        // its associated tax-groups.
        //
        foreach ($this->order->products as $product) {
            $tax_description = $this->findTaxGroupNameFromValue($product['tax']);
            if ($tax_description === '') {
                $messageStack->add_session(sprintf(ERROR_NO_PRODUCT_TAX_DESCRIPTION, zen_output_string_protected($product['name']), (string)$product['tax']), 'error');
                return false;
            }

            $this->addCostToTaxGroup($tax_description, ($product['final_price'] * $product['qty']) + $product['onetime_charges']);
        }
        return true;
    }

    // -----
    // Add the tax_groups and tax_subtotals arrays to the order. If the tax groups
    // for the order can't be reliably reconstructed, (bool)false is returned.
    //
    // Notes:
    //
    // 1. If an order includes multiple tax-rates, the order's ot_tax can be recorded
    //    as a single entry (each description separated by ' + ') or as multiple entries,
    //    depending on the setting for 'Configuration :: My Store :: Show Split Tax Lines'.
    // 2. An order might not have any tax recorded, depending on the order's taxation and the
    //    setting for 'Configuration :: My Store :: Sales Tax Display Status'.
    // 3. If an order has multiple tax-groups recorded, each **must** include a textual indication
    //    of the associated tax-rate, e.g. FL Sales Tax (7%).
    //
    protected function addTaxGroups(): bool
    {
        $this->order->info['tax_groups'] = [];
        $this->order->info['tax_subtotals'] = [];

        foreach ($this->order->totals as $next_total) {
            if ($next_total['class'] !== 'ot_tax') {
                continue;
            }

            $tax_location_names = explode(' + ', $next_total['title']);
            foreach ($tax_location_names as $next_name) {
                $next_name = rtrim($next_name, ':');
                $this->order->info['tax_groups'][$next_name] = 0.0;
                if (preg_match('/(\d+\.?\d*%)/', $next_name, $matches) === 1) {
                    $tax_rate = $this->convertToIntOrFloat(rtrim($matches[1], '%'));
                }
                $this->order->info['tax_subtotals'][$next_name] = [
                    'tax_rate' => $tax_rate ?? false,
                    'subtotal' => 0.0,
                ];
            }
        }
        
        // -----
        // If no tax was recorded for the order or if a single tax-type was recorded,
        // the taxes are processable and no further action needs to be done here.
        //
        if (count($this->order->info['tax_groups']) <= 1) {
            return true;
        }

        // -----
        // Create combinations of the now-exploded tax-groups, inspired by
        // https://www.oreilly.com/library/view/php-cookbook/1565926811/ch04s25.html
        //
        $combinations = [[]];
        foreach (array_keys($this->order->info['tax_subtotals']) as $next_tax_group) {
            foreach ($combinations as $combination) {
                array_push($combinations, array_merge([$element], $combination));
            }
        }

        global $messageStack;
        foreach ($combinations as $tax_groups) {
            if (count($tax_groups) <= 1) {
                continue;
            }
            $tax_subtotal = [
                'tax_rate' => 0,
                'subtotal' => 0.0,
                'parent_groups' => [],
            ];
            foreach ($tax_groups as $next_tax_group) {
                if ($this->order->info['tax_subtotals'][$next_tax_group]['tax_rate'] === false) {
                    $messageStack->add_session(ERROR_CANT_DETERMINE_TAX_RATES, 'error');
                    return false;
                }
                $tax_subtotal['tax_rate'] += $this->order->info['tax_subtotals'][$next_tax_group]['tax_rate'];
                $tax_subtotal['parent_groups'][$next_tax_group] = $this->order->info['tax_subtotals'][$next_tax_group];
            }
            $tax_groups_combined = implode(' + ', $tax_groups);
            $this->order->info['tax_subtotals'][$tax_groups_combined] = $tax_subtotal;
        }

        return true;
    }

    // -----
    // Attempts to reconstruct the order's shipping cost and tax-group.
    //
    // Side-effects:
    //
    // - Sets orderHasShipping, depending on whether/not an ot_shipping order-total is present.
    // - Sets the order's shipping_cost, shipping_tax and shipping_tax_description.
    //
    protected function addShippingTaxGroup(): bool
    {
        global $messageStack;

        // -----
        // Determine the shipping cost associated with the order. If the order has no shipping
        // component, just note that fact and indicate that, thus far, the order's OK.
        //
        $shipping_cost = $this->getOrderTotalValue('ot_shipping');
        $this->orderHasShipping = ($shipping_cost !== null);
        if ($shipping_cost === null) {
            return true;
        }

        // -----
        // For the order to be reconstructed, its shipping_tax_rate **must** have been recorded
        // when the order was created.
        //
        if ($this->order->info['shipping_tax_rate'] === null) {
            $messageStack->add_session(ERROR_SHIPPING_TAX_RATE_MISSING, 'error');
            return false;
        }

        $shipping_tax_rate = $this->convertToIntOrFloat($this->order->info['shipping_tax_rate']);
        $shipping_tax_description = $this->findTaxGroupNameFromValue($shipping_tax_rate);
        if ($shipping_tax_description === '') {
            $messageStack->add_session(sprintf(ERROR_NO_SHIPPING_TAX_DESCRIPTION, $this->order->info['shipping_tax_rate']), 'error');
            return false;
        }

        $this->order->info['shipping_tax_rate'] = $shipping_tax_rate;
        $this->order->info['shipping_cost'] = $shipping_cost;
        $this->order->info['shipping_tax'] = zen_calculate_tax($shipping_cost, $shipping_tax_rate);
        $this->order->info['shipping_tax_description'] = $shipping_tax_description;

        $this->addCostToTaxGroup($shipping_tax_description, $shipping_cost);

        return true;
    }
    protected function findTaxGroupNameFromValue(int|float $value): string
    {
        $num_tax_groups = count($this->order->info['tax_subtotals']);
        foreach ($this->order->info['tax_subtotals'] as $group_name => $tax_info) {
            if ($num_tax_groups === 1 || ($tax_info['tax_rate'] !== false && $tax_info['tax_rate'] == $value)) {
                return $group_name;
            }
        }
        return '';
    }
    protected function addCostToTaxGroup(string $tax_group_description, int|float $value): void
    {
        $this->order->info['tax_subtotals'][$tax_group_description]['subtotal'] += $value;

        if (!isset($this->order->info['tax_subtotals'][$tax_group_description]['parent_groups'])) {
            return;
        }

        foreach ($this->order->info['tax_subtotals'][$tax_group_description]['parent_groups'] as $group_name => $subtotals) {
            $parent_group_shipping_tax = zen_add_tax($value, $sub_totals['tax_rate']);
            $this->order->info['tax_subtotals'][$tax_group_description]['parent_groups'][$group_name]['subtotal'] += $parent_group_shipping_tax;
        }
    }

    public function arrayImplode($array_fields, $output_string = '')
    {
        foreach ($array_fields as $key => $value) {
            if (is_array($value)) {
                $output_string = $this->arrayImplode($value, $output_string);
            } else {
                $output_string .= $value . '^';
            }
        }
        return $output_string;
    }

    public function getOrderInfo($action)
    {
        // -----
        // Cleanup tax_groups in the order (broken code in order.php)
        // Shipping module will automatically add tax if needed.
        //
        $this->order->info['tax_groups'] = [];
        foreach ($this->order->products as $product) {
            $this->getProductTaxes($product);
        }

        // -----
        // Correctly add the running subtotal (broken code in older versions of order.php).
        //
        if (!isset($this->order->info['subtotal'])) {
            foreach ($this->order->totals as $next_total) {
                if ($next_total['class'] === 'ot_subtotal') {
                    $this->order->info['subtotal'] = $next_total['value'];
                    break;
                }
            }
        }

        // -----
        // Some order-totals (notably ot_cod_fee) rely on the payment-module code being present in the session ...
        //
        $_SESSION['payment'] = $this->order->info['payment_module_code'];
 
        $this->eoLog("getOrderInfo($action), on exit:\n" . $this->eoFormatTaxInfoForLog(), 'tax');
        return $order;
    }

    public function eoInitializeShipping($oID, $action)
    {
        global $order;
        $this->eoLog("eoInitializeShipping($oID, $action), on entry: " . $this->eoFormatTaxInfoForLog(), 'tax');

        // -----
        // Shipping cost and tax rate initializations are dependent on the current
        // 'action' being performed.
        //
        switch ($action) {
            case 'update_order':
                $order = $this->initializeShippingCostFromPostedValue($order);
                $this->initializeOrderShippingTax($oID, $action);
                break;
            case 'add_prdct':
                $order = $this->initializeShippingCostFromOrder($order);
                $this->initializeOrderShippingTax($oID, $action);
                $this->removeTaxFromShippingCost($order);
                break;
            default:
                $order = $this->initializeShippingCostFromOrder($order);
                $this->initializeOrderShippingTax($oID, $action);
                $this->removeTaxFromShippingCost($order);
                break;
        }
        $this->eoLog("eoInitializeShipping($oID, $action), on exit: " . $this->eoFormatTaxInfoForLog(), 'tax');
    }

    protected function initializeShippingCostFromOrder($order)
    {
        // -----
        // Determine the order's current shipping cost, retrieved from the value present in the
        // shipping order-total's value.
        //
        $query = $GLOBALS['db']->Execute(
            "SELECT `value` 
               FROM " . TABLE_ORDERS_TOTAL . "
              WHERE orders_id = {$this->orders_id}
                AND class = 'ot_shipping'
              LIMIT 1"
        );
        if (!$query->EOF) {
            $this->order->info['shipping_cost'] = $query->fields['value'];
            $_SESSION['shipping'] = [
                'title' => $order->info['shipping_method'],
                'id' => $order->info['shipping_module_code'] . '_',
                'cost' => $order->info['shipping_cost']
            ];
        } else {
            $this->order->info['shipping_cost'] = 0;
            $_SESSION['shipping'] = [
                'title' => EO_FREE_SHIPPING,
                'id' => 'free_free',
                'cost' => 0
            ];
        }
        return $order;
    }

    protected function initializeShippingCostFromPostedValue($order)
    {
        $found_ot_shipping = false;
        $ot_shipping = 'Not found';
        if (isset($_POST['update_total']) && is_array($_POST['update_total'])) {
            foreach ($_POST['update_total'] as $current_total) {
                if ($current_total['code'] === 'ot_shipping') {
                    $ot_shipping = json_encode($current_total);
                    $found_ot_shipping = true;
                    $shipping_module = $current_total['shipping_module'] . '_';
                    $shipping_cost = $this->eoRoundCurrencyValue($current_total['value']);
                    $shipping_title = $current_total['title'];
                    break;
                }
            }
        }
        if ($found_ot_shipping === true) {
            $order->info['shipping_cost'] = $shipping_cost;
            $_SESSION['shipping'] = [
                'title' => $shipping_title,
                'id' => $shipping_module,
                'cost' => $shipping_cost
            ];
        } else {
            $order->info['shipping_cost'] = 0;
            $_SESSION['shipping'] = [
                'title' => EO_FREE_SHIPPING,
                'id' => 'free_free',
                'cost' => 0
            ];
        }
        $this->eoLog("initializeShippingCostFromPostedValue, ot_shipping: $ot_shipping, shipping cost: {$order->info['shipping_cost']}.");
        return $order;
    }

    protected function initializeOrderShippingTax($oID, $action): void
    {
        global $order;

        // -----
        // Determine any previously-recorded shipping tax-rate for the order.
        //
        $tax_rate = $GLOBALS['db']->Execute(
            "SELECT shipping_tax_rate
               FROM " . TABLE_ORDERS . "
              WHERE orders_id = $oID
              LIMIT 1"
        );
        if ($tax_rate->EOF || ($tax_rate->fields['shipping_tax_rate'] === null && $action !== 'edit')) {
            trigger_error("Sequencing error; order ($oID) not present or shipping tax-rate not initialized for $action action.", E_USER_ERROR);
            exit();
        }
        switch ($action) {
            case 'update_order':
                $shipping_tax = $_POST['shipping_tax'] ?? 0;
                $this->shipping_tax_rate = is_numeric($shipping_tax) ? $shipping_tax : 0;
                $order->info['shipping_tax'] = $this->calculateOrderShippingTax(true);
                break;

            case 'add_prdct':
                $this->shipping_tax_rate = $tax_rate->fields['shipping_tax_rate'];
                $order->info['shipping_tax'] = $this->eoRoundCurrencyValue(zen_calculate_tax($order->info['shipping_cost'], $this->shipping_tax_rate));
                break;

            default:
                $this->shipping_tax_rate = $tax_rate->fields['shipping_tax_rate'];
                $order->info['shipping_tax'] = $this->calculateOrderShippingTax(false);
                break;
        }
        $GLOBALS['db']->Execute(
            "UPDATE " . TABLE_ORDERS . "
                SET shipping_tax_rate = " . $this->shipping_tax_rate . "
              WHERE orders_id = $oID
              LIMIT 1"
        );
        $order->info['shipping_tax_rate'] = $this->shipping_tax_rate;
    }

    // -----
    // Determine the tax-rate and associated tax for the order's shipping, giving a watching
    // observer the opportunity to override the calculations.
    //
    protected function calculateOrderShippingTax(bool $use_saved_tax_rate = false)
    {
        global $order;

        $shipping_tax = false;
        $shipping_tax_rate = false;
        $this->notify('NOTIFY_EO_GET_ORDER_SHIPPING_TAX', $order, $shipping_tax, $shipping_tax_rate);
        if ($shipping_tax !== false && $shipping_tax_rate !== false) {
            $this->eoLog("calculateOrderShippingTax, override returning $shipping_tax, rate = $shipping_tax_rate.");
            $this->shipping_tax_rate = $shipping_tax_rate;
            return $shipping_tax;
        }

        if ($use_saved_tax_rate === true || $this->shipping_tax_rate !== null) {
            $tax_rate = $this->shipping_tax_rate;
        } else {
            eo_shopping_cart();
            require_once DIR_FS_CATALOG . DIR_WS_CLASSES . 'shipping.php';
            $shipping_modules = new shipping();

            $tax_rate = 0;
            $shipping_module = $order->info['shipping_module_code'];
            if (!empty($GLOBALS[$shipping_module]) && is_object($GLOBALS[$shipping_module]) && !empty($GLOBALS[$shipping_module]->tax_class)) {
                $tax_location = zen_get_tax_locations();
                $tax_rate = zen_get_tax_rate($GLOBALS[$shipping_module]->tax_class, $tax_location['country_id'], $tax_location['zone_id']);
            }
        }
        $this->shipping_tax_rate = $tax_rate;
        $shipping_tax = $this->eoRoundCurrencyValue(zen_calculate_tax((float)$order->info['shipping_cost'], (float)$tax_rate));
        $this->eoLog("calculateOrderShippingTax returning $shipping_tax, rate = " . var_export($tax_rate, true) . ", cost = {$order->info['shipping_cost']}.");
        return $shipping_tax;
    }

    // -----
    // Invoked by EO's admin observer-class to override the tax to be applied to any
    // shipping cost, as offered by the ot_shipping module's processing.
    //
    public function eoUpdateOrderShippingTax(bool $tax_updated, &$shipping_tax_rate, &$shipping_tax_description): void
    {
        if ($tax_updated === false) {
            $shipping_tax_rate = $this->shipping_tax_rate ?? 0;

            $shipping_tax_description = $this->product_tax_descriptions[$shipping_tax_rate] ?? sprintf(EO_SHIPPING_TAX_DESCRIPTION, (string)$shipping_tax_rate);
        }
        $this->shipping_tax_description = $shipping_tax_description;
        $this->eoLog("eoUpdateOrderShippingTax($tax_updated, $shipping_tax_rate, $shipping_tax_description): " . json_encode($this->product_tax_descriptions), 'tax');

        if (isset($GLOBALS['order']) && is_object($GLOBALS['order'])) {
            if (!isset($GLOBALS['order']->info['tax_groups'][$shipping_tax_description])) {
                $GLOBALS['order']->info['tax_groups'][$shipping_tax_description] = 0;
            }
            if (!isset($GLOBALS['order']->info['shipping_tax'])) {
                $GLOBALS['order']->info['shipping_tax'] = 0;
            }
        }
    }

    public function eoGetShippingTaxRate($order)
    {
        $shipping_tax_rate = false;
        $this->notify('NOTIFY_EO_GET_ORDER_SHIPPING_TAX_RATE', $order, $shipping_tax_rate);
        if ($shipping_tax_rate !== false) {
            $this->eoLog("eoGetShippingTaxRate, override returning rate = $shipping_tax_rate.", 'tax');
            return (empty($shipping_tax_rate)) ? 0 : $shipping_tax_rate;
        }

        $tax_rate = 0;
        $shipping_module = $order->info['shipping_module_code'];
        if (isset($this->shipping_tax_rate)) {
            $tax_rate = $this->shipping_tax_rate;
        } elseif (!empty($GLOBALS[$shipping_module]) && is_object($GLOBALS[$shipping_module]) && !empty($GLOBALS[$shipping_module]->tax_class)) {
            $tax_location = zen_get_tax_locations();
            $tax_rate = zen_get_tax_rate($GLOBALS[$shipping_module]->tax_class, $tax_location['country_id'], $tax_location['zone_id']);
        }
        return (empty($tax_rate)) ? 0 : $tax_rate;
    }

    public function eoFormatTaxInfoForLog(bool $include_caller = false): string
    {
        $log_info = "\n";

        if ($include_caller === true) {
            $trace = debug_backtrace();
            $log_info = ' Called by ' . $trace[1]['file'] . ' on line #' . $trace[1]['line'] . "\n";
        }

        $log_info .= "\t" .
            'Subtotal: ' . ($this->order->info['subtotal'] ?? '(not set)') . ', ' .
            'Shipping: ' . ($this->order->info['shipping_cost'] ?? '(not set)') . ', ' .
            'Shipping Tax-Rate: ' . ($this->order->info['shipping_tax_rate'] ?? ' (not set)') . ', ' .
            'Shipping Tax-Description: ' . ($this->shipping_tax_description ?? ' (not set)') . ', ' .
            'Shipping Tax: ' . ($this->order->info['shipping_tax'] ?? '(not set)') . ', ' .
            'Tax: ' . $this->order->info['tax'] . ', ' .
            'Total: ' . $this->order->info['total'] . ', ' .
            'Tax Groups: ' . (!empty($this->order->info['tax_groups']) ? json_encode($this->order->info['tax_groups'], JSON_PRETTY_PRINT) : 'None') . "\n";

        $log_info .= "\t" .
            '$_SESSION[\'shipping\']: ' . ((isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping'], JSON_PRETTY_PRINT) : '(not set)') . "\n";

        $log_info .= $this->eoFormatOrderTotalsForLog();

        return $log_info;
    }

    public function eoFormatOrderTotalsForLog(string $title = ''): string
    {
        $log_info = ($title === '') ? ("\nOrder Totals\n") : $title;
        $log_info .= json_encode($this->order->totals, JSON_PRETTY_PRINT);
        return $log_info;
    }

    public function eoOrderIsVirtual(): bool
    {
        return ($this->order->content_type === 'virtual');
    }

    // -----
    // When a store "Displays Prices with Tax" and shipping is taxed, the shipping-cost recorded in the order includes
    // the shipping tax.  This function, called when an EO order is created, backs that tax quantity out of the shipping
    // cost since the order-totals processing will re-calculate that value.
    //
    public function removeTaxFromShippingCost(&$order): void
    {
        $shipping_tax_processed = false;
        $this->notify('NOTIFY_EO_REMOVE_SHIPPING_TAX', [], $order, $shipping_tax_processed);
        if ($shipping_tax_processed === true) {
            $this->eoLog("removeTaxFromShippingCost override, shipping_cost ({$order->info['shipping_cost']}), order tax ({$order->info['tax']})", 'tax');
            return;
        }

        if (DISPLAY_PRICE_WITH_TAX === 'true') {
            $tax_rate = 1 + $this->shipping_tax_rate / 100;
            $shipping_cost = $order->info['shipping_cost'];
            $shipping_cost_ex = $order->info['shipping_cost'] / $tax_rate;
            $shipping_tax = $shipping_cost - $shipping_cost_ex;
            $order->info['shipping_cost'] = $shipping_cost - $shipping_tax;
            $order->info['tax'] -= $shipping_tax;
            $order->info['shipping_tax'] = 0;

            $this->eoLog("removeTaxFromShippingCost, updated: $tax_rate, $shipping_cost, $shipping_cost_ex, $shipping_tax", 'tax');
        }
    }

    // -----
    // Convert a currency value in the database's decimal(15,4) format, in string format.  This
    // should help in the penny-off rounding calculations.
    //
    public function eoRoundCurrencyValue($value)
    {
        return $value;
    }

    public function eoFormatCurrencyValue($value)
    {
        return $GLOBALS['currencies']->format($this->eoRoundCurrencyValue($value), true, $this->info['currency'], $this->info['currency_value']);
    }

    // -----
    // Format an array for output to the debug log.
    //
    public function eoFormatArray(array $a): string
    {
        return json_encode($a, JSON_PRETTY_PRINT);
    }

    // -----
    // This class function mimics the zen_get_products_stock function, present in /includes/functions/functions_lookups.php.
    //
    public function getProductsStock($products_id)
    {
        $stock_handled = false;
        $stock_quantity = 0;
        $this->notify('NOTIFY_EO_GET_PRODUCTS_STOCK', $products_id, $stock_quantity, $stock_handled);
        if (!$stock_handled) {
            $check = $GLOBALS['db']->ExecuteNoCache(
                "SELECT products_quantity
                   FROM " . TABLE_PRODUCTS . "
                  WHERE products_id = " . (int)zen_get_prid($products_id) . "
                  LIMIT 1"
            );
            $stock_quantity = ($check->EOF) ? 0 : $check->fields['products_quantity'];
        }
        return $stock_quantity;
    }

    // -----
    // This method, called during a product addition, records the coupon-id associated
    // with the order into the session, so that the coupon is processed during that
    // addition.
    //
    public function eoSetCouponForOrder($oID): void
    {
        unset($_SESSION['cc_id']);
        $oID = (int)$oID;
        
        $check = $GLOBALS['db']->Execute(
            "SELECT c.coupon_id
               FROM " . TABLE_ORDERS . " o
                    INNER JOIN " . TABLE_COUPONS . " c
                        ON o.coupon_code = c.coupon_code
              WHERE o.orders_id = $oID
              LIMIT 1"
        );
        if (!$check->EOF) {
            $_SESSION['cc_id'] = $check->fields['coupon_id'];
        }
    }

    // -----
    // This method creates a hidden record in the order's status history.
    //
    public function eoRecordStatusHistory($oID, $message): void
    {
        zen_update_orders_history($oID, $message);
    }

    public function loadModuleLanguageFile(string $module_name, string $module_type): bool
    {
        global $languageLoader;

        return $languageLoader->loadModuleLanguageFile($module_name, $module_type);
    }

    // -----
    // This method determines the specified order-total's defined sort-order.
    //
    public function eoGetOrderTotalSortOrder(string $order_total_code): int
    {
        global $languageLoader;

        $sort_order = false;
        $module_file = $order_total_code . '.php';

        if ($this->loadModuleLanguageFile($module_file, 'order_total') === true) {
            require_once DIR_FS_CATALOG_MODULES . 'order_total/' . $module_file;
            $order_total = new $order_total_code();
            $sort_order = (int)$order_total->sort_order;
        }

        if ($sort_order === false) {
            if (!isset($this->ot_sort_default)) {
                $this->ot_sort_default = 0;
            }
            $sort_order = $this->ot_sort_default;
            $this->ot_sort_default++;
        }
        return $sort_order;
    }

    public function eoLog($message, $message_type = 'general')
    {
        if ($this->eo_action_level !== 0) {
            if (!(EO_DEBUG_TAXES_ONLY === 'true' && $message_type !== 'tax')) {
                error_log("$message\n", 3, $this->logfile_name);
            }
        }
    }
}
