<?php
// -----
// Part of the Edit Orders plugin by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016-2024, Vinos de Frutas Tropicales
//
// Last updated: EO v5.0.0
//
namespace Zencart\Plugins\Admin\EditOrders;
use Zencart\Traits\NotifierManager;

class EditOrders
{
    use NotifierManager;

    protected int $eo_action_level;
    protected string $logfile_name;
    protected int $orders_id;
    public bool $tax_updated;
    protected array $product_tax_descriptions;
    protected int $ot_sort_default;

    protected bool $orderHasShipping;

    protected \order $order;
    protected \order_total $orderTotals;

    public function __construct(int $orders_id)
    {
        $this->eo_action_level = (int)EO_DEBUG_ACTION_LEVEL;
        $this->logfile_name = DIR_FS_LOGS . '/eo_debug_' . $orders_id . date('_Ymd') . '.log';

        $this->orders_id = (int)$orders_id;
        $this->tax_updated = false;
        $this->product_tax_descriptions = [];
    }

    public function getOrderTotalsObject(): \order_total
    {
        if (!isset($this->orderTotals)) {
            $this->orderTotals = new \order_total();
        }
        return $this->orderTotals;
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
        $order = $this->order;
        unset($this->order);
        return $order;
    }

    public function queryOrder(\order $order): bool
    {
        // -----
        // Register the order within a *temporary* copy in this class;
        // it'll be removed on edit_orders.php's subsequent call to
        // the getOrder method, above.
        //
        $this->order = $order;

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
                $sql =
                    "SELECT countries_id AS `id`, countries_name AS `title`, countries_iso_code_2 AS iso_code_2, countries_iso_code_3 AS iso_code_3
                       FROM " . TABLE_COUNTRIES . "
                      WHERE countries_name = :country:
                        AND status = 1
                      LIMIT 1";
                $sql = $db->bindVars($sql, ':country:', $this->order->delivery['country'], 'string');
                $country_info = $db->Execute($sql);
                if (!$country_info->EOF) {
                    $this->order->delivery['country'] = $country_info->fields;
                } else {
                    $this->order->delivery['country'] = [
                        'id' => 0,
                        'title' => $order->delivery['country'],
                        'iso_code_2' => '',
                        'iso_code_3' => '',
                    ];
                }
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
        // aren't filled in for either the info or products elements.
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

        global $db;

        $country_id = (int)($address_info['country']['id'] ?? 0);
        $address_info['country_id'] = $country_id;

        $country_query = $db->Execute(
            "SELECT countries_id
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_id = $country_id
                AND status = 1
              LIMIT 1"
        );
        if ($country_query->EOF) {
            $address_info['country_id'] = 0;
            return $address_info;
        }

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
        // id (the products_prid) or its tax_class_id. Gather that information for use in rebuilding
        // each product's record.
        //
        global $db;
        $uprids_from_db = $db->Execute(
            "SELECT op.orders_products_id, op.products_prid, p.products_tax_class_id
               FROM " . TABLE_ORDERS_PRODUCTS . " op
                    LEFT JOIN " . TABLE_PRODUCTS . " p
                        ON p.products_id = op.products_id
              WHERE orders_id = " . $this->orders_id
        );
        $uprids = [];
        $tax_class_ids = [];
        foreach ($uprids_from_db as $next_record) {
            $uprids[$next_record['orders_products_id']] = $next_record['products_prid'];
            $tax_class_ids[$next_record['orders_products_id']] = $next_record['products_tax_class_id'];
        }

        // -----
        // Update various fields within the order's products' array to match the
        // format used in the storefront.
        //
        foreach ($this->order->products as &$next_product) {
            $next_product['qty'] = $this->convertToIntOrFloat($next_product['qty']);
            $next_product['tax_class_id'] = $tax_class_ids[$next_product['orders_products_id']];    //- Will be null if the product no longer exists!
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
            $products_id = (int)$current_product['orders_products_id'];
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
                    $this->eoLog("Checking whether the product's attribute is a download, option_id = " . $current_attribute['option_id'] . ", value_id = " . $current_attribute['value_id'] . ": (" . $download_check->EOF . ")");
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
        foreach ($this->order->products as &$product) {
            $tax_description = $this->findTaxGroupNameFromValue($product['tax']);
            if ($tax_description === '') {
                global $messageStack;
                $messageStack->add_session(
                    sprintf(ERROR_NO_PRODUCT_TAX_DESCRIPTION,
                        $this->orders_id,
                        zen_output_string_protected($product['name']),
                        (string)$product['tax']
                    ),
                    'error'
                );
                return false;
            }

            $product['tax_description'] = $tax_description;
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

        $ot_tax_count = 0;
        foreach ($this->order->totals as $next_total) {
            if ($next_total['class'] !== 'ot_tax') {
                continue;
            }

            $ot_tax_count++;

            $tax_location_names = explode(' + ', $next_total['title']);
            foreach ($tax_location_names as $next_name) {
                $next_name = rtrim($next_name, ':');
                $this->order->info['tax_groups'][$next_name] = 0.0;
                if (preg_match('/(\d+\.?\d*%)/', $next_name, $matches) === 1) {
                    $tax_rate = $this->convertToIntOrFloat(rtrim($matches[1], '%'));
                } elseif ($next_total['value'] == 0) {
                    $tax_rate = 0;
                }
                $this->order->info['tax_subtotals'][$next_name] = [
                    'tax_rate' => $tax_rate ?? false,
                    'subtotal' => 0.0,
                ];
            }
        }

        // -----
        // If no ot_tax records were found and the order's tax is 0 (using a
        // loose comparison!), then the order falls under Note 2 above. Set
        // a default 0-value tax rate tax-group for follow-on processing.
        //
        if ($ot_tax_count === 0 && $this->order->info['tax'] == 0) {
            $this->order->info['tax_groups'][TEXT_UNKNOWN_TAX_RATE] = 0.0;
            $this->order->info['tax_subtotals'][TEXT_UNKNOWN_TAX_RATE] = [
                'tax_rate' => 0,
                'subtotal' => 0.0,
            ];
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
                array_push($combinations, array_merge([$next_tax_group], $combination));
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
                    $messageStack->add_session(sprintf(ERROR_CANT_DETERMINE_TAX_RATES, $this->orders_id), 'error');
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
        // The storefront free-shipping determination doesn't set the order's shipping_tax_rate; if that's
        // the case, set the tax-rate to 0.
        //
        if ($this->order->info['shipping_module_code'] === 'free') {
            $this->order->info['shipping_tax_rate'] = 0;
        }

        // -----
        // For the order to be reconstructed, its shipping_tax_rate **must** have been recorded
        // when the order was created.
        //
        if ($this->order->info['shipping_tax_rate'] === null) {
            $messageStack->add_session(sprintf(ERROR_SHIPPING_TAX_RATE_MISSING, $this->orders_id), 'error');
            return false;
        }

        $shipping_tax_rate = $this->convertToIntOrFloat($this->order->info['shipping_tax_rate']);
        $shipping_tax_description = $this->findTaxGroupNameFromValue($shipping_tax_rate);
        if ($shipping_tax_description === '') {
            $messageStack->add_session(sprintf(ERROR_NO_SHIPPING_TAX_DESCRIPTION, $this->orders_id, $this->order->info['shipping_tax_rate']), 'error');
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

        // -----
        // Special-case a tax rate of 0%, adding a record to the order's
        // tax-groups and tax-subtotals into which these 0-value taxed
        // products (or shipping) can be accumulated.
        //
        if ($value == 0) {
            $group_name = sprintf(TEXT_UNKNOWN_TAX_RATE, '0');
            $this->order->info['tax_groups'][$group_name] = 0;
            $this->order->info['tax_subtotals'][$group_name] = [
                'tax_rate' => 0,
                'subtotal' => 0,
            ];
            return $group_name;
        }

        return '';
    }
    protected function addCostToTaxGroup(string $tax_group_description, int|float $value): void
    {
        $this->order->info['tax_subtotals'][$tax_group_description]['subtotal'] += $value;
        $this->order->info['tax_groups'][$tax_group_description] += $value * $this->order->info['tax_subtotals'][$tax_group_description]['tax_rate'] / 100;

        if (!isset($this->order->info['tax_subtotals'][$tax_group_description]['parent_groups'])) {
            return;
        }

        foreach ($this->order->info['tax_subtotals'][$tax_group_description]['parent_groups'] as $group_name => $subtotals) {
            $parent_group_shipping_tax = zen_add_tax($value, $subtotals['tax_rate']);
            $this->order->info['tax_subtotals'][$tax_group_description]['parent_groups'][$group_name]['subtotal'] += $parent_group_shipping_tax;
        }
    }

    // -----
    // For EO versions prior to 5.0.0, provided by the eo_get_available_shipping_modules
    // function.
    //
    public function getAvailableShippingModules(\order|\stdClass $order): array
    {
        $order_shipping_module = $order->info['shipping_module_code'];
        if ($order_shipping_module === '') {
            $shipping_unknown = [];
        } else {
            $shipping_unknown = [
                [
                    'id' => $order_shipping_module,
                    'text' => sprintf(TEXT_VALUE_UNKNOWN, $order_shipping_module),
                ],
            ];
        }

        if (!defined('MODULE_SHIPPING_INSTALLED') || empty(MODULE_SHIPPING_INSTALLED)) {
            return $shipping_unknown;
        }

        $use_strip_tags = (defined('EO_SHIPPING_DROPDOWN_STRIP_TAGS') && EO_SHIPPING_DROPDOWN_STRIP_TAGS === 'true');
        $module_selections = [];
        $shipping_modules = new \shipping();
        foreach ($shipping_modules->modules as $module) {
            $class = pathinfo($module, PATHINFO_FILENAME);
            if ($class === $order_shipping_module) {
                $shipping_unknown = [];
            }
            if (isset($GLOBALS[$class])) {
                $module_selections[] = [
                    'id' => $GLOBALS[$class]->code,
                    'text' => ($use_strip_tags === true) ? strip_tags($GLOBALS[$class]->title) : $GLOBALS[$class]->title,
                ];
            }
        }

        return array_merge($shipping_unknown, $module_selections);
    }

    // -----
    // Processing based on eo_get_available_order_totals_class_values for EO versions
    // prior to v5.0.0.
    //
    public function getUnusedOrderTotalModules(\order|\stdClass $order): array
    {
        $order_totals = $this->getOrderTotalsObject();

        $totals_to_skip = ['ot_group_pricing', 'ot_tax', 'ot_loworderfee', 'ot_purchaseorder', 'ot_gv', 'ot_voucher', 'ot_cod_fee'];
        foreach ($order->totals as $next_ot) {
            $class = $next_ot['class'] ?? $next_ot['code'];
            $totals_to_skip[] = $class;
            if (!empty($GLOBALS[$class]->eoCanBeAdded)) {
                $_SESSION['eo-totals'][$class] = ['title' => $next_ot['title'], 'value' => $next_ot['value'],];
            }
        }

        $module_list = explode(';', str_replace('.php', '', MODULE_ORDER_TOTAL_INSTALLED));
        $unused_totals = [];
        foreach ($module_list as $class) {
            if (in_array($class, $totals_to_skip)) {
                continue;
            }

            if ($class === 'ot_coupon' || !empty($GLOBALS[$class]->eoCanBeAdded)) {
                $unused_totals[] = [
                    'id' => $class,
                    'text' => $GLOBALS[$class]->title,
                ];
            }
        }

        return $unused_totals;
    }

    public function arrayImplode(array $array_fields, string $output_string = ''): string
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
        return $this->order;
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
            $shipping_modules = new \shipping();

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

    public function eoGetShippingTaxRate($order)
    {
        $shipping_tax_rate = false;
        $this->notify('NOTIFY_EO_GET_ORDER_SHIPPING_TAX_RATE', $order, $shipping_tax_rate);
        if ($shipping_tax_rate !== false) {
            $this->eoLog("eoGetShippingTaxRate, override returning rate = $shipping_tax_rate.", 'tax');
            return (empty($shipping_tax_rate)) ? 0 : $shipping_tax_rate;
        }

        return $_SESSION['eoChanges']->getUpdatedOrder()->info['shipping_tax_rate'];
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
        global $currencies;

        return $currencies->format($this->eoRoundCurrencyValue($value), true, $this->info['currency'], $this->info['currency_value']);
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

    public function getOrderInfoUpdateSql(array $original_values, array $updated_values): array
    {
        $order_info_updates = [];
        $updated_fields = array_keys($updated_values['changes']);
        foreach ($updated_fields as $key) {
            switch ($key) {
                case 'orders_status':
                    $order_info_updates[] = ['fieldName' => $key, 'value' => $updated_values[$key], 'type' => 'integer',];
                    break;
                case 'total':
                    $order_info_updates[] = ['fieldName' => 'order_total', 'value' => $updated_values[$key], 'type' => 'float',];
                    break;
                case 'tax':
                    $order_info_updates[] = ['fieldName' => 'order_tax', 'value' => $updated_values[$key], 'type' => 'float',];
                    break;
                case 'shipping_tax_rate':
                case 'order_weight':
                    $order_info_updates[] = ['fieldName' => $key, 'value' => $updated_values[$key], 'type' => 'float',];
                    break;
                default:
                    $order_info_updates[] = ['fieldName' => $key, 'value' => $updated_values[$key], 'type' => 'stringIgnoreNull',];
                    break;
            }
        }

        return $order_info_updates;
    }

    public function getAddressUpdateSql(string $field_prefix, array $original_values, array $updated_values): array
    {
        $address_updates = [];
        $updated_fields = array_keys($updated_values['changes']);
        $country_change = false;
        $state_zone_change = false;
        foreach ($updated_fields as $key) {
            switch ($key) {
                case 'country_id':
                    $country_change = true;
                    $country_id = (int)$updated_values['country_id'];
                    break;

                case 'zone_id':
                    $state_zone_change = true;
                    $zone_id = (int)$updated_values['zone_id'];
                    break;

                case 'state':
                    $state_zone_change = true;
                    $state = $updated_values['state'];
                    break;

                case 'company':
                case 'name':
                case 'street_address':
                case 'suburb':
                case 'city':
                case 'postcode':
                    $address_updates[] = ['fieldName' => $field_prefix . $key, 'value' => $updated_values[$key], 'type' => 'stringIgnoreNull',];
                    break;

                default:
                    $key_change = null;
                    $key_type = null;
                    $this->notify(
                        'NOTIFY_EO_UPDATING_ADDR_FIELD',
                        [
                            'original' => $original_values,
                            'field_prefix' => $field_prefix,
                            'field_name' => $key,
                            'updated_value' => $updated_values[$key],
                        ],
                        $key_change,
                        $key_type
                    );
                    $address_updates[] = ['fieldName' => $key_change ?? $key, 'value' => $updated_values[$key], 'type' => $key_type ?? 'stringIgnoreNull',];
                    break;
            }
        }

        if ($country_change === true) {
            $address_updates[] = ['fieldName' => $field_prefix . 'country', 'value' => zen_get_country_name($country_id), 'type' => 'stringIgnoreNull',];
            $address_updates[] = ['fieldName' => $field_prefix . 'address_format_id', 'value' => zen_get_address_format_id($country_id), 'type' => 'integer',];
        }

        if ($state_zone_change === true) {
            $country_id ??= $original_values['country_id'];
            $zone_name = zen_get_zone_name($country_id, $zone_id, 'no-zone');
            if ($zone_name === 'no-zone') {
                $zone_name = $state ?? '';
            }
            $address_updates[] = ['fieldName' => $field_prefix . 'state', 'value' => $zone_name, 'type' => 'stringIgnoreNull',];
        }

        return $address_updates;
    }

    public function eoLog($message, $message_type = 'general')
    {
        if ($this->eo_action_level !== 0) {
            error_log(date('Ymd H:i:s ') . "$message\n", 3, $this->logfile_name);
        }
    }
}
