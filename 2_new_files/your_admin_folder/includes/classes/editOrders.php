<?php
// -----
// Part of the Edit Orders plugin (v4.1.6 and later).
//
class eoHelper extends base
{
    public function __construct ($orders_id)
    {
        $this->eo_action_level = EO_DEBUG_ACTION_LEVEL;

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
    
    public function eoLog ($message) {
        if ($this->eo_action_level != 0) {
            error_log ($message . PHP_EOL, 3, $this->logfile_name);
        }
    }
    
    public function eoOrderIsVirtual ($order)
    {
        global $db;
        $order_is_virtual = false;
        foreach ($order->products as $current_product) {
            $products_id = (int)$current_product['id'];
            $virtual_check = $db->Execute ("SELECT products_virtual, products_model FROM " . TABLE_PRODUCTS . " WHERE products_id = $products_id LIMIT 1");
            $this->eoLog (PHP_EOL . "Checking product ID#$products_id for virtual status" . PHP_EOL . var_export ($virtual_check, true));
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
    
}
