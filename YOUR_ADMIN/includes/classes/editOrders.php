<?php
// -----
// Part of the Edit Orders plugin (v4.1.6 and later) by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016-2019, Vinos de Frutas Tropicales
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
        $this->product_tax_descriptions = array();
        
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

    public function getOrderInfo($action)
    {
        // -----
        // Note: The order-object is declared global, allowing the various functions to
        // have access to the just-created information.
        //
        global $order;
        $oID = $this->orders_id;

        // -----
        // Retrieve the formatted order, via the storefront order.php class.
        //
        $order = new order($oID);
        $this->eoLog("getOrderInfo($action), on entry:" .  $this->eoFormatTaxInfoForLog(true), 'tax');
        
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
 
        $this->eoLog("getOrderInfo($action), on exit:" . PHP_EOL . $this->eoFormatTaxInfoForLog(), 'tax');
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
            $order->info['shipping_cost'] = $query->fields['value'];
            $_SESSION['shipping'] = array(
                'title' => $order->info['shipping_method'],
                'id' => $order->info['shipping_module_code'] . '_',
                'cost' => $order->info['shipping_cost']
            );
        } else {
            $order->info['shipping_cost'] = 0;
            $_SESSION['shipping'] = array(
                'title' => EO_FREE_SHIPPING,
                'id' => 'free_free',
                'cost' => 0
            );
        }
        return $order;
    }
    
    protected function initializeShippingCostFromPostedValue($order)
    {
        $found_ot_shipping = false;
        $ot_shipping = 'Not found';
        if (isset($_POST['update_total']) && is_array($_POST['update_total'])) {
            foreach ($_POST['update_total'] as $current_total) {
                if ($current_total['code'] == 'ot_shipping') {
                    $ot_shipping = json_encode($current_total);
                    $found_ot_shipping = true;
                    $shipping_module = $current_total['shipping_module'] . '_';
                    $shipping_cost = $current_total['value'];
                    $shipping_title = $current_total['title'];
                    break;
                }
            }
        }
        if ($found_ot_shipping) {
            $order->info['shipping_cost'] = $shipping_cost;
            $_SESSION['shipping'] = array(
                'title' => $shipping_title,
                'id' => $shipping_module,
                'cost' => $shipping_cost
            );
        } else {
            $order->info['shipping_cost'] = 0;
            $_SESSION['shipping'] = array(
                'title' => EO_FREE_SHIPPING,
                'id' => 'free_free',
                'cost' => 0
            );
        }
        $this->eoLog("initializeShippingCostFromPostedValue, ot_shipping: $ot_shipping, shipping cost: {$order->info['shipping_cost']}.");
        return $order;
    }
    
    protected function initializeOrderShippingTax($oID, $action)
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
        if ($tax_rate->EOF || ($tax_rate->fields['shipping_tax_rate'] === null && $action != 'edit')) {
            trigger_error("Sequencing error; order ($oID) not present or shipping tax-rate not initialized for $action action.", E_USER_ERROR);
            exit();
        }
        switch ($action) {
            case 'update_order':
                $this->shipping_tax_rate = $_POST['shipping_tax'];
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
    protected function calculateOrderShippingTax($use_saved_tax_rate = false)
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

        if ($use_saved_tax_rate || $this->shipping_tax_rate !== null) {
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
        $shipping_tax = $this->eoRoundCurrencyValue(zen_calculate_tax($order->info['shipping_cost'], $tax_rate));
        $this->eoLog("calculateOrderShippingTax returning $shipping_tax, rate = " . var_export($tax_rate, true) . ", cost = {$order->info['shipping_cost']}.");
        return $shipping_tax;
    }
    
    // -----
    // Invoked by EO's admin observer-class to override the tax to be applied to any
    // shipping cost, as offered by the ot_shipping module's processing.
    //
    public function eoUpdateOrderShippingTax($tax_updated, &$shipping_tax_rate, &$shipping_tax_description)
    {
        if ($tax_updated === false) {
            $shipping_tax_rate = (isset($this->shipping_tax_rate)) ? $this->shipping_tax_rate : 0;

            if (isset($this->product_tax_descriptions[$shipping_tax_rate])) {
                $shipping_tax_description = $this->product_tax_descriptions[$shipping_tax_rate];
            } else {
                $shipping_tax_description = sprintf(EO_SHIPPING_TAX_DESCRIPTION, (string)$shipping_tax_rate);
            }
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
    
    public function getProductTaxes($product, $shown_price = -1, $add = true)
    {
        global $order;

        $products_tax = $product['tax'];
        $product_final_price = $product['final_price'];
        $product_onetime = $product['onetime_charges'];
        $product_qty = $product['qty'];
        $shown_price = $this->eoRoundCurrencyValue(zen_add_tax($product_final_price, $products_tax)) * $product_qty;
        $shown_price += $this->eoRoundCurrencyValue(zen_add_tax($product_onetime, $products_tax));

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
            } else {
                $products_tax_description = TEXT_UNKNOWN_TAX_RATE . ' (' . zen_display_tax_value($products_tax) . '%)';
            }
        }
        
        // -----
        // Save the association of the current product's tax-rate to its description for possible use by the
        // eoUpdateOrderShippingTax method.  If the order's shipping tax-rate is the same as a product's tax-rate,
        // the shipping tax value will be added to that product-based tax description.
        //
        $this->product_tax_descriptions[$products_tax] = $products_tax_description;
        
        $this->eoLog(PHP_EOL . "getProductTaxes($products_tax_description)\n" . (($query === false) ? 'false' : (($query->EOF) ? 'EOF' : json_encode($query->fields))) . json_encode($product), 'tax');
        
        $totalTaxAdd = 0;
        if (!empty($products_tax_description)) {
            $taxAdd = 0;

            if (DISPLAY_PRICE_WITH_TAX == 'true') {
                $taxAdd = $this->eoRoundCurrencyValue($shown_price / (100 + $products_tax) * $products_tax);
            } else {
                $taxAdd = $this->eoRoundCurrencyValue(zen_calculate_tax($this->eoRoundCurrencyValue($product_final_price) * $product_qty, $products_tax));
                $taxAdd += $this->eoRoundCurrencyValue(zen_calculate_tax($this->eoRoundCurrencyValue($product_onetime), $products_tax));
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
        $this->eoLog("getProductTaxes, returning $totalTaxAdd." . PHP_EOL);
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
                'Shipping Tax-Rate: ' . ((isset($this->shipping_tax_rate)) ? $this->shipping_tax_rate : ' (not set)') . ', ' .
                'Shipping Tax-Description: ' . ((isset($this->shipping_tax_description)) ? $this->shipping_tax_description : ' (not set)') . ', ' .
                'Shipping Tax: ' . ((isset($order->info['shipping_tax'])) ? $order->info['shipping_tax'] : '(not set)') . ', ' .
                'Tax: ' . $order->info['tax'] . ', ' .
                'Total: ' . $order->info['total'] . ', ' .
                'Tax Groups: ' . (!empty($order->info['tax_groups']) ? json_encode($order->info['tax_groups']) : 'None') . PHP_EOL;
                
            $log_info .= "\t" .
                '$_SESSION[\'shipping\']: ' . ((isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping'], true) : '(not set)') . PHP_EOL;
                
            $log_info .= $this->eoFormatOrderTotalsForLog($order);
        }
        return $log_info;
    }
    
    public function eoFormatOrderTotalsForLog($order, $title = '')
    {
        $log_info = ($title === '') ? (PHP_EOL . 'Order Totals' . PHP_EOL) : $title;
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
        $this->eoLog(PHP_EOL . "Checking order for virtual status.  Order contains $product_count unique products, $virtual_products of those are virtual");
        
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
    public function removeTaxFromShippingCost(&$order)
    {
        $shipping_tax_processed = false;
        $this->notify('NOTIFY_EO_REMOVE_SHIPPING_TAX', array(), $order, $shipping_tax_processed);
        if ($shipping_tax_processed === true) {
            $this->eoLog("removeTaxFromShippingCost override, shipping_cost ({$order->info['shipping_cost']}), order tax ({$order->info['tax']})", 'tax');
            return;
        }

        if (DISPLAY_PRICE_WITH_TAX == 'true') {
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
    
    public function eoRoundCurrencyValue($value)
    {
        return $GLOBALS['currencies']->value($value, false, $this->currency, $this->currency_value);
    }
    
    public function eoFormatCurrencyValue($value)
    {
        return $GLOBALS['currencies']->format($this->eoRoundCurrencyValue($value), true, $this->currency, $this->currency_value);
    }
    
    // -----
    // Format an array for output to the debug log.
    //
    public function eoFormatArray($a)
    {
        return str_replace(array('},{', '","'), array('},' . PHP_EOL . '{', '",' . PHP_EOL . '"'), json_encode($a));
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
            $check = $GLOBALS['db']->Execute(
                "SELECT products_quantity
                   FROM " . TABLE_PRODUCTS . "
                  WHERE products_id = " . (int)zen_get_prid($products_id) . "
                  LIMIT 1",
                false,
                false,
                0,
                true
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
    public function eoSetCouponForOrder($oID)
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
    public function eoRecordStatusHistory($oID, $message)
    {
        if (function_exists('zen_update_orders_history')) {
            zen_update_orders_history($oID, $message);
        } else {
            $check_status = $GLOBALS['db']->Execute(
                "SELECT orders_status
                   FROM " . TABLE_ORDERS . "
                  WHERE orders_id = $oID
                  LIMIT 1"
            );
            $osh_sql = array(
                'orders_id' => $oID,
                'orders_status_id' => $check_status->fields['orders_status'],
                'date_added' => 'now()',
                'customer_notified' => -1,
                'comments' => $message
            );
            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $osh_sql);
        }
    }
    
    // -----
    // This method determines the specified order-total's defined sort-order.
    //
    public function eoGetOrderTotalSortOrder($order_total_code)
    {
        $sort_order = false;
        $module_file = $order_total_code . '.php';
        
        $lang_file = zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/order_total/', $module_file, 'false');
        if (@file_exists($lang_file)) {
            include_once $lang_file;
        }
        
        $module_file = DIR_FS_CATALOG . DIR_WS_MODULES . 'order_total/' . $order_total_code . '.php';
        if (@file_exists($module_file)) {
            include_once $module_file;
            $order_total = new $order_total_code();
            $sort_order = $order_total->sort_order;
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
}
