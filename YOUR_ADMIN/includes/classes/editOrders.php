<?php
// -----
// Part of the Edit Orders plugin (v4.1.6 and later) by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016-2018, Vinos de Frutas Tropicales
//
if (!defined('EO_DEBUG_TAXES_ONLY')) define('EO_DEBUG_TAXES_ONLY', 'false');  //-Either 'true' or 'false'
class editOrders extends base
{
    public function __construct($orders_id)
    {
        global $db, $currencies;
        
        $this->eo_action_level = EO_DEBUG_ACTION_LEVEL;
        $this->orders_id = (int)$orders_id;
        $this->tax_updated = false;
        
        $currency_info = $db->Execute(
            "SELECT currency, currency_value 
               FROM " . TABLE_ORDERS . " 
              WHERE orders_id = " . $this->orders_id . " 
              LIMIT 1"
        );
        $this->currency = $currency_info->fields['currency'];
        $this->currency_value = $currency_info->fields['currency_value'];
        unset($currency_info);
        
        if (!isset($currencies)) {
            if (!class_exists('currencies')) {
                require DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php';
            }
            $currencies = new currencies();
        }
        $_SESSION['currency'] = $this->currency;
        $this->order_currency = $currencies->currencies[$this->currency];

        // -----
        // Create the edit_orders directory, if not already present.
        //
        if ($this->eo_action_level != 0) {
            $log_file_dir = (defined('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE) . '/edit_orders';
            if (!is_dir($log_file_dir) && !mkdir($log_file_dir, 0777, true)) {
                $this->eo_action_level = 0;
                trigger_error("Failure creating the Edit Orders log-file directory ($log_file_dir); the plugin's debug is disabled until this issue is corrected.", E_USER_WARNING);
            } else {
                $this->logfile_name = $log_file_dir . '/debug_edit_orders_' . $orders_id . '.log';
            }
        }
    }
    
    public function eoLog($message, $message_type = 'general') 
    {
        if ($this->eo_action_level != 0) {
            if (!(EO_DEBUG_TAXES_ONLY == 'true' && $message_type != 'tax')) {
                error_log($message . PHP_EOL, 3, $this->logfile_name);
            }
        }
    }
    
    public function getOrderInfo()
    {
        // -----
        // Note: The order-object is declared global, allowing the various functions to
        // have access to the just-created information.
        //
        global $order;
        $oID = $this->orders_id;

        // -----
        // Retrieve the formatted order.
        //
        $order = new order($oID);
        $this->eoLog('getOrderInfo, on entry:' . $this->eoFormatTaxInfoForLog(true) . var_export($order->info, true), 'tax');
        
        // -----
        // Add some required customer information for tax calculation.
        // The next method has been modified to add required info to the
        // session and global variables.
        //
        zen_get_tax_locations();

        // -----
        // Cleanup tax_groups in the order (broken code in order.php)
        // Shipping module will automatically add tax if needed.
        //
        $order->info['tax_groups'] = array();
        foreach ($order->products as $product) {
            $this->getProductTaxes($product);
        }

        // -----
        // Correctly add the running subtotal (broken code in older versions of order.php).
        //
        if (!isset($order->info['subtotal'])) {
            $query = $GLOBALS['db']->Execute(
                "SELECT `value` 
                   FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE orders_id = $oID
                    AND `class` = 'ot_subtotal'
                  LIMIT 1"
            );
            if (!$query->EOF) {
                $order->info['subtotal'] = $this->eoRoundCurrencyValue($query->fields['value']);
            }
        }

        // Convert country portion of addresses to same format used in catalog side
        if (isset($order->customer['country'])) {
            $country = eo_get_country($order->customer['country']);
            if ($country !== null) {
                $order->customer['country'] = $country;
                $order->customer['country_id'] = $country['id'];
                $order->customer['zone_id'] = zen_get_zone_id($order->customer['country']['id'], $order->customer['state']);
            }
        }
        if (is_array($order->delivery) && isset($order->delivery['country'])) { //-20150811-lat9-Add is_array since virtual products don't have a delivery address
            $country = eo_get_country($order->delivery['country']);
            if ($country !== null) {
                $order->delivery['country'] = $country;
                $order->delivery['country_id'] = $country['id'];
                $order->delivery['zone_id'] = zen_get_zone_id($order->delivery['country']['id'], $order->delivery['state']);
            }
        }
        if (isset($order->billing['country'])) {
            $country = eo_get_country($order->billing['country']);
            if ($country !== null) {
                $order->billing['country'] = $country;
                $order->billing['country_id'] = $country['id'];
                $order->billing['zone_id'] = zen_get_zone_id($order->billing['country']['id'], $order->billing['state']);
            }
        }
        unset($country);
        
        // -----
        // Some order-totals (notably ot_cod_fee) rely on the payment-module code being present in the session ...
        //
        $_SESSION['payment'] = $order->info['payment_module_code'];
        
        // -----
        // Later versions of Zen Cart's zen_get_tax_rate (on the admin-side anyway) now expect the customer's countries_id and
        // zone_id to be in globally-available variables while earlier versions expect the values to be in session variables.
        //
        // Handle shipping costs (module will automatically handle tax)
        //
        if (!isset($order->info['shipping_cost'])) {
            $query = $GLOBALS['db']->Execute(
                "SELECT `value` 
                   FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE orders_id = $oID
                    AND class = 'ot_shipping'
                  LIMIT 1"
            );
            if (!$query->EOF) {
                $order->info['shipping_cost'] = $this->eoRoundCurrencyValue($query->fields['value']);

                $_SESSION['shipping'] = array(
                    'title' => $order->info['shipping_method'],
                    'id' => $order->info['shipping_module_code'] . '_',
                    'cost' => $order->info['shipping_cost']
                );

                // Load the shopping cart class into the session
                eo_shopping_cart();

                // Load the shipping class into the globals
                require_once DIR_FS_CATALOG . DIR_WS_CLASSES . 'shipping.php';
                $shipping_modules = new shipping($_SESSION['shipping']);
                
                // -----
                // Determine whether the order's shipping-method is taxed and
                // initialize the order's 'shipping_tax' value.
                //
                $order->info['shipping_tax'] = $this->calculateOrderShippingTax();
            }
        }
        
        // -----
        // Determine which portion (if any) of the shipping-cost is associated with the shipping tax, removing that
        // value from the stored shipping-cost and accumulated tax to "present" the order to the various
        // order-total modules in the manner that's done on the storefront.
        //
        $shipping_module = $order->info['shipping_module_code'];
        $this->removeTaxFromShippingCost($order, $shipping_module);
        
        $this->eoLog('getOrderInfo, on exit:' . var_export($GLOBALS[$shipping_module], true) . var_export($order, true) . $this->eoFormatTaxInfoForLog(), 'tax');
        return $order;
    }
    
    protected function calculateOrderShippingTax()
    {
        global $order;
        
        $shipping_tax = 0;
        
        $shipping_module = $order->info['shipping_module_code'];
        $shipping_tax_class_name = 'MODULE_SHIPPING_' . strtoupper($shipping_module) . '_TAX_CLASS';
        $shipping_tax_basis_name = 'MODULE_SHIPPING_' . strtoupper($shipping_module) . '_TAX_BASIS';

        $shipping_tax_class = (defined($shipping_tax_class_name)) ? constant($shipping_tax_class_name) : null;
        $shipping_tax_basis = (defined($shipping_tax_basis_name)) ? constant($shipping_tax_basis_name) : null;
        if ($shipping_tax_class === null || $shipping_tax_basis === null) {
            $this->eoLog("calculateOrderShippingTax, $shipping_module does not provide tax-information.");
        } else {
            $tax_location = zen_get_tax_locations();
            $tax_rate = zen_get_tax_rate($shipping_tax_class, $tax_location['country_id'], $tax_location['zone_id']);
            if ($tax_rate != 0) {
                $shipping_tax = $this->eoRoundCurrencyValue(zen_calculate_tax($order->info['shipping_cost'], $tax_rate));
            }
        }
        
        $this->eoLog("calculateOrderShippingTax returning $shipping_tax.");
        return $shipping_tax;
    }
    
    public function getProductTaxes($product, $shown_price = -1, $add = true)
    {
        global $order;

        $shown_price = $this->eoRoundCurrencyValue($product['final_price'] * $product['qty']);
        $onetime_charges = $this->eoRoundCurrencyValue($product['onetime_charges']);
        if (DISPLAY_PRICE_WITH_TAX == 'true') {
            $shown_price += $this->eoRoundCurrencyValue(zen_calculate_tax($shown_price, $product['tax']));
            $onetime_charges += $this->eoRoundCurrencyValue(zen_calculate_tax($product['onetime_charges'], $product['tax']));
        }
        $shown_price += $onetime_charges;

        $query = false;
        if (isset($product['tax_description'])) {
            $products_tax_description = $product['tax_description'];
        } else {
            $query = $GLOBALS['db']->Execute(
                "SELECT products_tax_class_id 
                   FROM " . TABLE_PRODUCTS . "
                  WHERE products_id = " . (int)$product['id'] . "
                  LIMIT 1"
            );
            if (!$query->EOF) {
                $products_tax_description = zen_get_tax_description($query->fields['products_tax_class_id']);
            } elseif (isset($product['tax'])) {
                $products_tax_description = TEXT_UNKNOWN_TAX_RATE . ' (' . zen_display_tax_value($product['tax']) . '%)';
            }
        }

        $this->eoLog(PHP_EOL . "getProductTaxes($products_tax_description)\n" . (($query === false) ? var_export($query, true) : (($query->EOF) ? 'EOF' : var_export($query->fields, true))) . var_export($product, true));
        
        $totalTaxAdd = 0;
        if (zen_not_null($products_tax_description)) {
            $taxAdd = 0;
            // Done this way to ensure we calculate
            if(DISPLAY_PRICE_WITH_TAX == 'true') {
                $taxAdd = $shown_price - ($shown_price / (($product['tax'] < 10) ? "1.0" . str_replace('.', '', $product['tax']) : "1." . str_replace('.', '', $product['tax'])));
            } else {
                $taxAdd = zen_calculate_tax($shown_price, $product['tax']);
            }
            $taxAdd = $this->eoRoundCurrencyValue($taxAdd);
            if (isset($order->info['tax_groups'][$products_tax_description])) {
                if ($add) {
                    $order->info['tax_groups'][$products_tax_description] += $taxAdd;
                } else {
                    $order->info['tax_groups'][$products_tax_description] -= $taxAdd;
                }
            } elseif ($add) {
                $order->info['tax_groups'][$products_tax_description] = $taxAdd;
            }
            $totalTaxAdd += $taxAdd;
            unset($taxAdd);
        }
        return $totalTaxAdd;
    }
    
    public function eoFormatTaxInfoForLog($include_caller = false)
    {
        global $order;
        $log_info = PHP_EOL;
        
        if ($include_caller) {
            $trace = debug_backtrace();
            $log_info = ' Called by ' . $trace[1]['file'] . ' on line #' . $trace[1]['line'] . PHP_EOL;
        }
        
        if (!is_object($order)) {
            $log_info .= "\t" . 'Order-object is not set.' . PHP_EOL;
        } else {
            $log_info .= "\t" .
                'Subtotal: ' . ((isset($order->info['subtotal'])) ? $order->info['subtotal'] : '(not set)') . ', ' .
                'Shipping: ' . ((isset($order->info['shipping_cost'])) ? $order->info['shipping_cost'] : '(not set)') . ', ' .
                'Shipping Tax: ' . ((isset($order->info['shipping_tax'])) ? $order->info['shipping_tax'] : '(not set)') . ', ' .
                'Tax: ' . $order->info['tax'] . ', ' .
                'Total: ' . $order->info['total'] . PHP_EOL;
                
            $log_info .= "\t" .
                '$_SESSION[\'shipping\']: ' . ((isset($_SESSION['shipping'])) ? var_export($_SESSION['shipping'], true) : '(not set)');
                
            $log_info .= $this->eoFormatOrderTotalsForLog($order);
        }
        return $log_info;
    }
    
    public function eoFormatOrderTotalsForLog($order)
    {
        $log_info = PHP_EOL . 'Order Totals' . PHP_EOL;
        foreach ($order->totals as $current_total) {
            $log_info .= "\t\t" . $current_total['class'] . '. Text: ' . $current_total['text'] . ', Value: ' . ((isset($current_total['value'])) ? $current_total['value'] : '(not set)') . PHP_EOL;
        }
        return $log_info;
     }
    
    public function eoOrderIsVirtual($order)
    {
        $virtual_products = 0;
        foreach ($order->products as $current_product) {
            $products_id = (int)$current_product['id'];
            $virtual_check = $GLOBALS['db']->Execute(
                "SELECT products_virtual, products_model 
                   FROM " . TABLE_PRODUCTS . " 
                  WHERE products_id = $products_id 
                  LIMIT 1"
            );
            if (!$virtual_check->EOF) {
                if ($virtual_check->fields['products_virtual'] == 1 || strpos($virtual_check->fields['products_model'], 'GIFT') === 0) {
                    $virtual_products++;
                } elseif (isset($current_product['attributes'])) {
                    foreach ($current_product['attributes'] as $current_attribute) {
                        $download_check = $GLOBALS['db']->Execute(
                            "SELECT pa.products_id FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                    INNER JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                        ON pad.products_attributes_id = pa.products_attributes_id
                              WHERE pa.products_id = $products_id
                                AND pa.options_values_id = " . (int)$current_attribute['value_id'] . "
                                AND pa.options_id = " . (int)$current_attribute['option_id'] . "
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
        }
        
        $product_count = count($order->products);
        $this->eoLog(PHP_EOL . "Checking order for virtual status.  Order contains $product_count products, $virtual_products of those are virtual");
        
        return ($virtual_products == $product_count);
    }
    
    // -----
    // Some order-total modules (like ot_cod_fee and ot_loworder_fee) are taxable but there's no built-in
    // way to determine the tax that they're adding to the order.  Since EO "re-builds" the order after a
    // product is added, that results in accumulating order-total taxes on each addition.
    //
    // Unfortunately, the order-object contains only the "formatted" version of the order-total's price,
    // so we need to access the numeric value associated that total from the database.
    //
    public function eoGetOrderTotalTax($oID, $ot_class)
    {
        global $db;
        $order_total_tax = 0;
        if ($ot_class == 'ot_cod_fee') {
            $ot_tax_class_name = 'MODULE_ORDER_TOTAL_COD_TAX_CLASS';
        } else {
            $ot_tax_class_name = 'MODULE_ORDER_TOTAL_' . strtoupper(str_replace('ot_', '', $ot_class)) . '_TAX_CLASS';
        }
        $ot_tax_class = (defined($ot_tax_class_name)) ? constant($ot_tax_class_name) : null;
        if ($ot_tax_class != null) {
            $tax_location = zen_get_tax_locations();
            $tax_rate = zen_get_tax_rate($ot_tax_class, $tax_location['country_id'], $tax_location['zone_id']);
            if ($tax_rate != 0) {
                $oID = (int)$oID;
                $ot_value_info = $db->Execute(
                    "SELECT value 
                       FROM " . TABLE_ORDERS_TOTAL . " 
                      WHERE orders_id = $oID 
                        AND class = '$ot_class' 
                      LIMIT 1"
                );
                if (!$ot_value_info->EOF) {
                    $order_total_tax = $this->eoRoundCurrencyValue(zen_calculate_tax($ot_value_info->fields['value'], $tax_rate));
                }
            }
        }
        $this->eoLog("Checking taxes for $ot_class: Tax class ($ot_tax_class_name:$ot_tax_class), $order_total_tax", 'tax');
        return $order_total_tax;
    }
    
    // -----
    // When a store "Displays Prices with Tax" and shipping is taxed, the shipping-cost recorded in the order includes
    // the shipping tax.  This function, called when an EO order is created, backs that tax quantity out of the shipping
    // cost since the order-totals processing will re-calculate that value.
    //
    public function removeTaxFromShippingCost(&$order, $module)
    {
        if (DISPLAY_PRICE_WITH_TAX == 'true' && isset($GLOBALS[$module]) && isset($GLOBALS[$module]->tax_class) && $GLOBALS[$module]->tax_class > 0) {
            $tax_class = $GLOBALS[$module]->tax_class;
            $tax_basis = isset($GLOBALS[$module]->tax_basis) ? $GLOBALS[$module]->tax_basis : STORE_SHIPPING_TAX_BASIS;
            
            $country_id = false;
            switch ($tax_basis) {
                case 'Billing':
                    $country_id = $order->billing['country']['id'];
                    $zone_id = $order->billing['zone_id'];
                    break;
                case 'Shipping':
                    $country_id = $order->delivery['country']['id'];
                    $zone_id = $order->delivery['zone_id'];
                    break;
                default:
                    if (STORE_ZONE == $order->billing['zone_id']) {
                        $country_id = $order->billing['country']['id'];
                        $zone_id = $order->billing['zone_id'];
                    } elseif (STORE_ZONE == $order->delivery['zone_id']) {
                        $country_id = $order->delivery['country']['id'];
                        $zone_id = $order->delivery['zone_id'];
                    }
                    break;
            }
            if ($country_id !== false) {
                $tax_rate = 1 + (zen_get_tax_rate($tax_class, $country_id, $zone_id) / 100);
                $shipping_cost = $order->info['shipping_cost'];
                $shipping_cost_ex = $this->eoRoundCurrencyValue($order->info['shipping_cost'] / $tax_rate);
                $shipping_tax = $this->eoRoundCurrencyValue($shipping_cost - $shipping_cost_ex);
                $order->info['shipping_cost'] = $shipping_cost - $shipping_tax;
                $order->info['tax'] -= $shipping_tax;
                $order->info['shipping_tax'] = 0;
             
                $this->eoLog("removeTaxFromShippingCost(order, $module), $tax_class, $tax_basis, $tax_rate, $shipping_cost, $shipping_cost_ex, $shipping_tax", 'tax');
            }
        }
    }
    
    public function eoRoundCurrencyValue($value)
    {
        return $GLOBALS['currencies']->value($value, false, $this->currency, $this->currency_value);
    }
    
    public function eoFormatCurrencyValue($value)
    {
        return $GLOBALS['currencies']->format($this->eoRoundCurrencyValue($value), true, $this->currency, $this->currency_value);
    }
     
}
