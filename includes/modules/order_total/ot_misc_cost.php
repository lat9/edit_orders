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
        if (IS_ADMIN_FLAG === true) {
            if (defined('MODULE_ORDER_TOTAL_MISC_COST_STATUS') && !defined('MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS')) {
                $current_page = $GLOBALS['current_page'];
                if ($current_page == FILENAME_MODULES . '.php') {
                    $this->title .= ' <span class="alert">' . MODULE_ORDER_TOTAL_MISC_COST_KEYS_MISSING . '</span>';
                } else {
                    $GLOBALS['messageStack']->add_session(MODULE_ORDER_TOTAL_MISC_COST_MISSING_TAXCLASS, 'warning');
                }
            }
        }
        $this->description = MODULE_ORDER_TOTAL_MISC_COST_DESCRIPTION;
        $this->sort_order = (defined('MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER')) ? ((int)MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER) : '';
        $this->tax_class_id = (defined('MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS')) ? ((int)MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS) : 0;
        
        $this->output = array();
    }

    public function process() 
    {
        global $db, $currencies, $order;
        if (IS_ADMIN_FLAG === true) {
            for ($i = 0, $n = count($order->totals); $i < $n; $i++) {
                if ($order->totals[$i]['class'] == 'ot_misc_cost') {
                    $value = (is_numeric($order->totals[$i]['value'])) ? $order->totals[$i]['value'] : 0;
                    if ($value != 0) {
                        if (MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE == 'false') {
                            $order->totals[$i]['title'] = $this->title;
                        } else {
                            $order->totals[$i]['title'] = rtrim($order->totals[$i]['title'], ' :');
                        }
                        
                        $order->totals[$i]['value'] = $value;
                        $order->info['total'] += $value;
                        
                        if ($this->tax_class_id != 0) {
                            $tax_rate = zen_get_tax_rate($this->tax_class_id);
                            $misc_tax = $GLOBALS['currencies']->value(zen_calculate_tax($value, $tax_rate), false, $order->info['currency'], $order->info['currency_value']);
                            $order->info['total'] += $misc_tax;
                            $order->info['tax'] += $misc_tax;
                            
                            $tax_description = zen_get_tax_description($this->tax_class_id);
                            if (!isset($order->info['tax_groups'][$tax_description])) {
                                $order->info['tax_groups'][$tax_description] = $misc_tax;
                            } else {
                                $order->info['tax_groups'][$tax_description] += $misc_tax;
                            }
                        }
                    }
                    $this->output[] = array(
                        'title' => $order->totals[$i]['title'] . ':',
                        'text' => $currencies->format($value, true, $order->info['currency'], $order->info['currency_value']),
                        'value' => $value
                    );
                    break;
                }
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
            $this->enabled = !$check_query->EOF;
        }
        return $this->enabled;
    }

    public function keys() 
    {
        return array(
            'MODULE_ORDER_TOTAL_MISC_COST_STATUS', 
            'MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER',
            'MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS',
            'MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE'
        );
    }

    public function install() 
    {
        global $db;
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 
             VALUES 
                ('This module is installed', 'MODULE_ORDER_TOTAL_MISC_COST_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
             VALUES 
                ('Sort Order', 'MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER', '410', 'Sort order of display.', '6', '2', now())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 
             VALUES 
                ('Allow changing the title', 'MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE', 'false', 'Allow changing the title of the module while editing an order', '6', '3','zen_cfg_select_option(array(\'true\',\'false\'), ', now())"
         );
         $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) 
             VALUES 
                ('Tax Class', 'MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS', '0', 'Use the following tax class for any Miscellaneous Cost. If you are applying a tax to this order-total, remember to:<ol><li>Set its <em>Sort Order</em> to a value greater than that for <code>ot_coupon</code>.</li><li>Set its <em>Sort Order</em> to a value less than that for <code>ot_tax</code>.</li></ol>', '6', '7', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())"
          );
     }

    public function remove() 
    {
        global $db;
        $keys = "'" . implode("', '", $this->keys()) . "'";
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . " 
              WHERE configuration_key IN (" . $keys . ")");
    }
}
