<?php
// -----
// An order-total module to gather any miscellaneous cost associated with an order, created by lat9 (https://vinosdefrutastropicales.com).
//
class ot_misc_cost 
{
    /**
    * The unique "code" identifying this Order Total Module
    * @var string
    */
    var $code;

    /**
    * The title shown for this Order Total Module
    * @var string
    */
    var $title;

    /**
    * The description shown for this Order Total Module
    * @var string
    */
    var $description;

    /**
    * The sort order at which to apply this Order Total Module
    * @var string
    */
    var $sort_order;

    /**
    * Indicates if this module is enabled
    * @var unknown
    */
    var $enabled;

    /**
    * The output from this Order Total Module
    * @var array
    */
    var $output;

    /**
    * Enter description here...
    *
    * @return ot_coupon
    */
    public function __construct() 
    {
        $this->code = 'ot_misc_cost';
        $this->title = MODULE_ORDER_TOTAL_MISC_COST_TITLE;
        $this->description = MODULE_ORDER_TOTAL_MISC_COST_DESCRIPTION;
        $this->sort_order = (int)MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER;
        $this->output = array();
    }

    public function process() 
    {
        global $db, $currencies, $order;

        if (IS_ADMIN_FLAG === true && isset($_GET['oID'])) {
            $oID = (int)$_GET['oID'];
            $query = $db->Execute(
                "SELECT `title`, `text`, `value` 
                   FROM " . TABLE_ORDERS_TOTAL . "
                  WHERE orders_id = $oID
                    AND class = 'ot_misc_cost'
                  LIMIT 1"
            );
            if (!$query->EOF) {
                // Do we allow changing the "title"?
                if (MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE === 'true') {
                    $this->title = rtrim($query->fields['title'], ':');
                }

                // Apply the cost to the order.
                $discount = (float)$query->fields['value'];
                $order->info['total'] += $discount;

                $products_id = $order->products[0]['id'];
                $products_info = $db->Execute(
                    "SELECT products_tax_class_id 
                       FROM " . TABLE_PRODUCTS . " 
                      WHERE products_id = $products_id 
                      LIMIT 1"
                );
                $products_tax_rate = zen_get_tax_rate($products_info->fields['products_tax_class_id']);
                $order->info['total'] -= $order->info['tax'];
                $order->info['tax'] = zen_calculate_tax($order->info['total'], $products_tax_rate);
                $order->info['total'] += $order->info['tax'];
                foreach ($order->info['tax_groups'] as $tax_description => $tax_value) {
                    $order->info['tax_groups'][$tax_description] = $order->info['tax'];
                }

                // Output the order total information
                $this->output[] = array(
                    'title' => $this->title . ':',
                    'text' => $currencies->format($discount, true, $order->info['currency'], $order->info['currency_value']),
                    'value' => (string)$discount,
                );
                unset($discount);
            }
        }
    }

    public function check() 
    {
        global $db;
        if (!isset($this->enabled)) {
            $check_query = $db->Execute(
                "SELECT configuration_value 
                   FROM " . TABLE_CONFIGURATION . " 
                  WHERE configuration_key = 'MODULE_ORDER_TOTAL_MISC_COST_STATUS'
                  LIMIT 1"
            );
            $this->enabled = $check_query->RecordCount();
        }
        return $this->enabled;
    }

    public function keys() 
    {
        return array(
            'MODULE_ORDER_TOTAL_MISC_COST_STATUS', 
            'MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER', 
            'MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE'
        );
    }

    public function install() 
    {
        global $db;
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('This module is installed', 'MODULE_ORDER_TOTAL_MISC_COST_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Sort Order', 'MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER', '410', 'Sort order of display.', '6', '2', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Allow changing the title', 'MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE', 'false', 'Allow changing the title of the module while editing an order', '6', '3','zen_cfg_select_option(array(\'true\',\'false\'), ', now())");
     }

    public function remove() 
    {
        global $db;
        $keys = '';
        $keys_array = $this->keys();
        for ($i=0; $i<sizeof($keys_array); $i++) {
          $keys .= "'" . $keys_array[$i] . "',";
        }
        $keys = substr($keys, 0, -1);

        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key in (" . $keys . ")");
    }
}
