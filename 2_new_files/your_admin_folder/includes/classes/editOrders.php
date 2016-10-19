<?php
// -----
// Part of the Edit Orders plugin (v4.1.6 and later) by lat9 (lat9@vinosdefrutastropicales.com).
// Copyright (C) 2016, Vinos de Frutas Tropicales
//
if (!defined ('EO_DEBUG_TAXES_ONLY')) define ('EO_DEBUG_TAXES_ONLY', 'false');  //-Either 'true' or 'false'
class editOrders extends base
{
    public function __construct ($orders_id)
    {
        global $db, $currencies;
        
        $this->eo_action_level = EO_DEBUG_ACTION_LEVEL;
        $this->orders_id = (int)$orders_id;
        $this->tax_updated = false;
        
        $currency_info = $db->Execute ("SELECT currency, currency_value FROM " . TABLE_ORDERS . " WHERE orders_id = " . $this->orders_id . " LIMIT 1");
        $this->currency = $currency_info->fields['currency'];
        $this->currency_value = $currency_info->fields['currency_value'];
        unset ($currency_info);
        
        if (!isset ($currencies)) {
            if (!class_exists ('currencies')) {
                require (DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php');
            }
            $currencies = new currencies ();
        }
        $_SESSION['currency'] = $this->currency;
        $this->order_currency = $currencies->currencies[$this->currency];

        // -----
        // Create the edit_orders directory, if not already present.
        //
        if ($this->eo_action_level != 0) {
            $log_file_dir = (defined ('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE) . '/edit_orders';
            if (!is_dir ($log_file_dir) && !mkdir ($log_file_dir, 0777, true)) {
                $this->eo_action_level = 0;
                trigger_error ("Failure creating the Edit Orders log-file directory ($log_file_dir); the plugin's debug is disabled until this issue is corrected.", E_USER_WARNING);
            } else {
                $this->logfile_name = $log_file_dir . '/debug_edit_orders_' . $orders_id . '.log';
            }
        }
    }
    
    public function eoLog ($message, $message_type = 'general') {
        if ($this->eo_action_level != 0) {
            if (!(EO_DEBUG_TAXES_ONLY == 'true' && $message_type != 'tax')) {
                error_log ($message . PHP_EOL, 3, $this->logfile_name);
            }
        }
    }
    
    public function eoFormatTaxInfoForLog ($include_caller = false)
    {
        global $order;
        $log_info = PHP_EOL;
        
        if ($include_caller) {
            $trace = debug_backtrace ();
            $log_info = ' Called by ' . $trace[1]['file'] . ' on line #' . $trace[1]['line'] . PHP_EOL;
        }
        
        if (!is_object ($order)) {
            $log_info .= "\t" . 'Order-object is not set.' . PHP_EOL;
        } else {
            $log_info .= "\t" .
                'Subtotal: ' . ((isset ($order->info['subtotal'])) ? $order->info['subtotal'] : '(not set)') . ', ' .
                'Shipping: ' . ((isset ($order->info['shipping_cost'])) ? $order->info['shipping_cost'] : '(not set)') . ', ' .
                'Shipping Tax: ' . ((isset ($order->info['shipping_tax'])) ? $order->info['shipping_tax'] : '(not set)') . ', ' .
                'Tax: ' . $order->info['tax'] . ', ' .
                'Total: ' . $order->info['total'] . PHP_EOL;
                
            $log_info .= "\t" .
                '$_SESSION[\'shipping\']: ' . ((isset ($_SESSION['shipping'])) ? var_export ($_SESSION['shipping'], true) : '(not set)');
                
            foreach ($order->totals as $current_total) {
                $log_info .= "\t\t" . $current_total['class'] . '. Text: ' . $current_total['text'] . ', Value: ' . ((isset ($current_total['value'])) ? $current_total['value'] : '(not set)') . PHP_EOL;
            }
        }
        return $log_info;
    }
    
    public function eoOrderIsVirtual ($order)
    {
        global $db;
        $order_is_virtual = false;
        foreach ($order->products as $current_product) {
            $products_id = (int)$current_product['id'];
            $virtual_check = $db->Execute ("SELECT products_virtual, products_model FROM " . TABLE_PRODUCTS . " WHERE products_id = $products_id LIMIT 1");
            $this->eoLog (PHP_EOL . "Checking product ID#$products_id for virtual status: " . PHP_EOL . var_export ($virtual_check->fields, true));
            if (!$virtual_check->EOF) {
                if ($virtual_check->fields['products_virtual'] == 1 || strpos ($virtual_check->fields['products_model'], 'GIFT') === 0) {
                    $order_is_virtual = true;
                    break;  //-Out of foreach products loop
                }
                
                if (isset ($current_product['attributes'])) {
                    foreach ($current_product['attributes'] as $current_attribute) {
                        $download_check = $db->Execute (
                            "SELECT pa.products_id FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                              WHERE pa.products_id = $products_id
                                AND pa.options_values_id = " . (int)$current_attribute['value_id'] . "
                                AND pa.options_id = " . (int)$current_attribute['option_id'] . "
                                AND pa.products_attributes_id = pad.products_attributes_id
                              LIMIT 1"
                        );
                        $this->eoLog ("\tChecking whether the product's attribute is a download, option_id = " . $current_attribute['option_id'] . ", value_id = " . $current_attribute['value_id'] . ": (" . $download_check->EOF . ")");
                        if (!$download_check->EOF) {
                            $order_is_virtual = true;
                            break;  //-Out of foreach attributes loop
                        }
                    }
                    if ($order_is_virtual) {
                        break;  //-Out of foreach products loop
                    }
                }
            }
        }
        return $order_is_virtual;
    }
    
    // -----
    // Some order-total modules (like ot_cod_fee and ot_loworder_fee) are taxable but there's no built-in
    // way to determine the tax that they're adding to the order.  Since EO "re-builds" the order after a
    // product is added, that results in accumulating order-total taxes on each addition.
    //
    // Unfortunately, the order-object contains only the "formatted" version of the order-total's price,
    // so we need to access the numeric value associated that total from the database.
    //
    public function eoGetOrderTotalTax ($oID, $ot_class)
    {
        global $db, $order;
        $order_total_tax = 0;
        if ($ot_class == 'ot_cod_fee') {
            $ot_tax_class_name = 'MODULE_ORDER_TOTAL_COD_TAX_CLASS';
        } else {
            $ot_tax_class_name = 'MODULE_ORDER_TOTAL_' . strtoupper (str_replace ('ot_', '', $ot_class)) . '_TAX_CLASS';
        }
        $ot_tax_class = constant ($ot_tax_class_name);
        if ($ot_tax_class != null) {
            $tax_location = zen_get_tax_locations ();
            $tax_rate = zen_get_tax_rate ($ot_tax_class, $tax_location['country_id'], $tax_location['zone_id']);
            if ($tax_rate != 0) {
                $ot_value_info = $db->Execute ("SELECT value FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = $oID AND class = '$ot_class' LIMIT 1");
                if (!$ot_value_info->EOF) {
                    $order_total_tax = $GLOBALS['currencies']->value (zen_calculate_tax ($ot_value_info->fields['value'], $tax_rate), false, $order->info['currency'], $order->info['currency_value']);
                }
            }
        }
        $this->eoLog ("Checking taxes for $ot_class: Tax class ($ot_tax_class_name:$ot_tax_class), $order_total_tax", 'tax');
        return $order_total_tax;
    }
     
}
