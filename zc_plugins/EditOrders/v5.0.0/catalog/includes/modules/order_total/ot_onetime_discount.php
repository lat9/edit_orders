<?php
/*
 * This file is part of the "Onetime Discount" order total module for Zen Cart.
 *
 * Last updated: EO 5.0.0
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
     */
    public string $code;

    /**
     * The title shown for this Order Total Module
     */
    public string $title;

    /**
     * The description shown for this Order Total Module
     */
    public string $description;

    /**
     * The sort order at which to apply this Order Total Module
     */
    public int|null $sort_order;

    /**
     * Indicates if this module is enabled
     */
    public bool $enabled = false;

    /**
    * Indicates that the module can be added via Edit Orders.
    */
    public bool $eoCanBeAdded = true;

    /**
     * The output from this Order Total Module
     */
    public array $output;

    /**
     * Enter description here...
     *
     * @return ot_coupon
     */
    public function __construct()
    {
        global $current_page;

        $this->code = 'ot_onetime_discount';
        $this->title = MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_TITLE;
        $this->description = MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DESCRIPTION;
        $this->sort_order = (defined('MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER')) ? (int)MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER : null;
        if (null === $this->sort_order) {
            return;
        }

        $this->enabled = (IS_ADMIN_FLAG === true && $current_page === 'edit_orders.php');
        $this->output = [];
    }

    public function process()
    {
        if ($this->enabled === false) {
            return;
        }

        global $currencies, $order;

        if (isset($_POST['ot_class']) && $_POST['ot_class'] === $this->code) {
            $value = is_numeric($_POST['value']) ? $_POST['value'] : 0;
            if (MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY === 'true' && $value > 0) {
                $value *= -1;
            }

            if (MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_CHANGE_TITLE === 'false') {
                $title = $this->title;
            } else {
                $title = $_POST['title'];
            }
            if ($value == 0 || $title === '') {
                unset($_SESSION['eo-totals']['ot_onetime_discount']);
                return;
            }
            $_SESSION['eo-totals']['ot_onetime_discount'] = ['value' => $value, 'title' => $title,];
        }

        if (!isset($_SESSION['eo-totals']['ot_onetime_discount'])) {
            return;
        }

        $order->info['total'] += $_SESSION['eo-totals']['ot_onetime_discount']['value'];

        // Output the order total information
        $this->output[] = [
            'title' => rtrim($_SESSION['eo-totals']['ot_onetime_discount']['title'], ' :') . ':',
            'text' => $currencies->format($_SESSION['eo-totals']['ot_onetime_discount']['value'], true, $order->info['currency'], $order->info['currency_value']),
            'value' => $_SESSION['eo-totals']['ot_onetime_discount']['value'],
        ];
    }

    public function check()
    {
        global $db;
        if (!isset($this->enabled)) {
            $check_query = $db->Execute(
                "SELECT configuration_value
                   FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_STATUS'
                  LIMIT 1"
            );
            $this->enabled = !$check_query->EOF;
        }
        return $this->enabled;
    }

    public function keys()
    {
        return [
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_STATUS',
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER',
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_CHANGE_TITLE',
            'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY'
        ];
    }

    public function install()
    {
        global $db;
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
             VALUES
                ('This module is installed', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_STATUS', 'true', '', 6, 1, 'zen_cfg_select_option([\'true\'], ', now())"
        );

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
             VALUES
                ('Sort Order', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_SORT_ORDER', '410', 'Sort order of display.', 6, 2, now())"
        );

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
             VALUES
                ('Allow changing the title', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_CHANGE_TITLE', 'false', 'Allow changing the title of the module while editing an order', 6, 3, 'zen_cfg_select_option([\'true\',\'false\'], ', now())"
        );

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
             VALUES
                ('Enable deductions only?', 'MODULE_ORDER_TOTAL_ONETIME_DISCOUNT_DEDUCTION_ONLY', 'true', 'Should the order-total enable <em>only</em> deductions from the order\'s value? If set to <b>true</b>, then any value entered (whether positive or negative) deducts from the order; otherwise, you can use the order-total to both add to and deduct from an order\'s value. Default: <em>true</em>.', 6, 4, 'zen_cfg_select_option([\'true\',\'false\'], ', now())"
        );
    }

    public function remove()
    {
        global $db;
        $keys = "'" . implode("', '", $this->keys()) . "'";
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN (" . $keys . ")");
    }
}
