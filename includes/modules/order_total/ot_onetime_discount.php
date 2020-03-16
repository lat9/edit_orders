<?php
/*
 * This file is part of the "Onetime Discount" order total module for Zen Cart.
 *
 * "Onetime Discount" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation version 2 of the License.
 *
 * "Onetime Discount" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with "Onetime Discount". If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL V2.0
 * @author Andrew Ballanger
 */

class ot_onetime_discount 
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
    function __construct() 
    {
        $this->code = 'ot_onetime_discount';
        $this->title = MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_TITLE;
        $this->description = MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DESCRIPTION;
        $this->sort_order = (defined('MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER')) ? (int)MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER : null;
        $this->output = array();
    }

    function process() 
    {
        global $db, $currencies, $order;
        
        if (!defined('MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY')) {
            define('MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY', 'true');
        }

        if (IS_ADMIN_FLAG === true && isset($_GET['oID'])) {
            $query = $db->Execute(
                'SELECT `title`, `text`, `value` ' .
                'FROM `' . TABLE_ORDERS_TOTAL . '` ' .
                'WHERE orders_id = \'' . (int)$_GET['oID'] . '\' ' .
                'AND class = \'' . zen_output_string($this->code, false, true) . '\''
            );
            if (!$query->EOF) {
                // Do we allow changing the "title"?
                if (MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_CHANGE_TITLE === 'true') {
                    $this->title = rtrim($query->fields['title'], ':');
                }

                // Apply the discount to the order.
                $discount = (float)$query->fields['value'];
                if ($discount > 0 && MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY == 'true') {
                    $discount = $discount * -1;
                }
                $order->info['total'] += $discount;

                // Output the order total information
                $this->output[] = array(
                    'title' => $this->title . ':',
                    'text' => $currencies->format($discount, true, $order->info['currency'], $order->info['currency_value']),
                    'value' => $discount,
                );
                unset($discount);
            }
        }
    }

    function check() 
    {
        global $db;
        if (!isset($this->enabled)) {
            $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_STATUS' LIMIT 1");
            $this->enabled = $check_query->RecordCount();
        }
        return $this->enabled;
    }

    function keys() 
    {
        return array(
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_STATUS', 
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER', 
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_CHANGE_TITLE',
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY'
        );
    }

    function install() 
    {
        global $db;
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('This module is installed', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Sort Order', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER', '410', 'Sort order of display.', '6', '2', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Allow changing the title', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_CHANGE_TITLE', 'false', 'Allow changing the title of the module while editing an order', '6', '3','zen_cfg_select_option(array(\'true\',\'false\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable deductions only?', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY', 'true', 'Should the order-total enable <em>only</em> deductions from the order\'s value?  If set to <b>true</b>, then any value entered (whether positive or negative) deducts from the order; otherwise, you can use the order-total to both add to and deduct from an order\'s value. Default: <em>true</em>.', '6', '3','zen_cfg_select_option(array(\'true\',\'false\'), ', now())");    
    }

    function remove() 
    {
        global $db;
        $keys = '';
        $keys_array = $this->keys();
        for ($i=0; $i<count($keys_array); $i++) {
            $keys .= "'" . $keys_array[$i] . "',";
        }
        $keys = substr($keys, 0, -1);
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN (" . $keys . ")");
    }
}
