<?php
/**
 * ot_group_pricing order-total module
 *
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2022 Oct 16 Modified in v1.5.8a $
 */
// -----
// Distributed with Edit Orders v4.7.1
//
class ot_group_pricing extends base
{
    /**
     * $_check is used to check the configuration key set up
     * @var int
     */
    protected $_check;
    /**
     * $code determines the internal 'code' name used to designate "this" order total module
     * @var string
     */
    public $code;
    /**
     * $credit_class flag to indicate order totals method is a credit class
     * @var boolean
     */
    public $credit_class;
    /**
     * $deduction amount of deduction calculated/afforded while being applied to an order
     * @var float|null
     */
    protected $deduction;
    /**
     * $description is a soft name for this order total method
     * @var string 
     */
    public $description;
    /**
     * $include_shipping allow shipping costs to be discounted by coupon if 'true'
     * @var string
     */
    public $include_shipping;
    /**
     * $include_tax allow tax to be discounted by coupon if 'true'
     * @var string
     */
    public $include_tax;
    /**
     * $sort_order is the order priority of this order total module when displayed
     * @var int
     */
    public $sort_order;
    /**
     * $tax_class is the Tax class to be applied to the coupon cost
     * @var
     */
    public $tax_class;
    /**
     * $title is the displayed name for this order total method
     * @var string
     */
    public $title;
    /**
     * $output is an array of the display elements used on checkout pages
     * @var array
     */
    public $output = [];

    public function __construct()
    {
        $this->code = 'ot_group_pricing';
        $this->title = MODULE_ORDER_TOTAL_GROUP_PRICING_TITLE;
        $this->description = MODULE_ORDER_TOTAL_GROUP_PRICING_DESCRIPTION;
        $this->sort_order = defined('MODULE_ORDER_TOTAL_GROUP_PRICING_SORT_ORDER') ? MODULE_ORDER_TOTAL_GROUP_PRICING_SORT_ORDER : null;
        if (null === $this->sort_order) {
            return false;
        }

        $this->include_shipping = (MODULE_ORDER_TOTAL_GROUP_PRICING_INC_SHIPPING === 'true');
        $this->include_tax = (MODULE_ORDER_TOTAL_GROUP_PRICING_INC_TAX === 'true');
        $this->credit_class = true;
    }

    public function process()
    {
        global $order, $currencies;

        if ($order->info['total'] == 0 || !zen_is_logged_in() || zen_in_guest_checkout()) {
            return;
        }

        $deductions = $this->calculate_deductions();
        $this->deduction = $deductions['total'] ?? 0;
        if ($this->deduction > 0) {
            foreach ($order->info['tax_groups'] as $key => $value) {
                $order->info['tax_groups'][$key] -= $deductions['tax_groups'][$key];
            }
            $order->info['total'] -= $deductions['total'] + $deductions['tax'];
            $order->info['tax'] -= $deductions['tax'];
            $order->info['shipping_cost'] -= $deductions['shipping_cost'];
            $order->info['shipping_tax'] -= $deductions['shipping_tax'];
            $shipping_status = ($this->include_shipping === true) ? MODULE_ORDER_TOTAL_GROUP_PRICING_SHIPPING_TEXT : '';
            $tax_status = ($this->include_tax === true) ? MODULE_ORDER_TOTAL_GROUP_PRICING_TAX_REDUCED_TEXT : '';
            $whats_included = MODULE_ORDER_TOTAL_GROUP_PRICING_SUBTOTAL_TEXT . $shipping_status . MODULE_ORDER_TOTAL_GROUP_PRICING_INCL_TEXT;
            $this->output[] = [
                'title' => $this->title . ' (' . $deductions['discount_percentage'] . '%' . $whats_included . $tax_status . '):',
                'text' => '-' . $currencies->format($deductions['total'], true, $order->info['currency'], $order->info['currency_value']),
                'value' => $deductions['total']
            ];
        }
    }

    protected function calculate_deductions()
    {
        global $db, $order;

        $group_discount = $db->Execute(
            "SELECT gp.group_percentage
               FROM " . TABLE_GROUP_PRICING . " gp
                    INNER JOIN " . TABLE_CUSTOMERS . " c
                        ON gp.group_id = c.customers_group_pricing
              WHERE c.customers_group_pricing != 0
                AND c.customers_id = " . (int)$_SESSION['customer_id'] . "
              LIMIT 1"
        );
        if ($group_discount->EOF) {
            $GLOBALS['group_pricing_return'] = [];
            return [];
        }

        $discount_percentage = $group_discount->fields['group_percentage'] / 100;

        $discount_total = $order->info['subtotal'] - $_SESSION['cart']->gv_only();

        // -----
        // Noting that, like ot_coupon, checks here for a match on the shipping
        // tax-description fails if a tax-class has *multiple* tax components!
        //
        // Taxes, at this point in the order's construction are **presumed** to contain
        // only those for the products' and shipping taxes!
        //
        $shipping_tax_description = $_SESSION['shipping_tax_description'] ?? '';
        $discount_tax_groups = $order->info['tax_groups'];
        $discount_tax = $order->info['tax'];

        if ($this->include_shipping === true) { 
            $discount_total += $order->info['shipping_cost'];
            $discount_shipping_cost = $order->info['shipping_cost'];
            $discount_shipping_tax = $order->info['shipping_tax'];
        } else {
            $discount_shipping_cost = 0;
            $discount_shipping_tax = 0;
            $discount_tax -= $order->info['shipping_tax'];
            foreach ($discount_tax_groups as $key => $value) {
                if ($key === $shipping_tax_description) {
                    $discount_tax_groups[$key] = $value - $order->info['shipping_tax'];
                    break;
                }
            }
        }

        if ($this->include_tax === true) {
            foreach ($discount_tax_groups as $key => $value) {
                $discount_tax_groups[$key] = $value * $discount_percentage;
            }
        } else {
            $discount_tax = 0;
            $discount_shipping_tax = 0;
            foreach ($discount_tax_groups as $key => $value) {
                $discount_tax_groups[$key] = 0;
            }
        }

        // -----
        // Let an observer "know" the order- and configuration-related elements
        // used in this discount calculation, giving the opportunity to
        // override the to-be-returned value.
        //
        $discounts = [
            'total' => $discount_total * $discount_percentage,
            'tax' => $discount_tax * $discount_percentage,
            'tax_groups' => $discount_tax_groups,
            'shipping_cost' => $discount_shipping_cost * $discount_percentage,
            'shipping_tax' => $discount_shipping_tax * $discount_percentage,
            'discount_percentage' => $group_discount->fields['group_percentage'],
        ];
        $this->notify('NOTIFY_OT_GROUP_PRICING_DEDUCTIONS',
            [
                'order' => $order,
                'discount_percentage' => $discount_percentage,
                'include_shipping' => $this->include_shipping,
                'include_taxes' => $this->include_tax,
                'shipping_tax_description' => $shipping_tax_description,
            ],
            $discounts
        );
        return $discounts;
    }

    public function credit_selection()
    {
        return false;
    }

    public function collect_posts()
    {
    }

    public function update_credit_account($i)
    {
    }

    public function apply_credit()
    {
    }

    public function clear_posts()
    {
    }

    public function check()
    {
        global $db;

        if (!isset($this->_check)) {
            $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_ORDER_TOTAL_GROUP_PRICING_STATUS' LIMIT 1");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    public function keys()
    {
        return [
            'MODULE_ORDER_TOTAL_GROUP_PRICING_STATUS',
            'MODULE_ORDER_TOTAL_GROUP_PRICING_SORT_ORDER',
            'MODULE_ORDER_TOTAL_GROUP_PRICING_INC_SHIPPING',
            'MODULE_ORDER_TOTAL_GROUP_PRICING_INC_TAX',
        ];
    }

    public function install()
    {
        global $db;
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('This module is installed', 'MODULE_ORDER_TOTAL_GROUP_PRICING_STATUS', 'true', '', 6, 1,'zen_cfg_select_option([\'true\'], ', now())");

        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_GROUP_PRICING_SORT_ORDER', '290', 'Sort order of display.', 6, 2, now())");

        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function ,date_added) values ('Include Shipping', 'MODULE_ORDER_TOTAL_GROUP_PRICING_INC_SHIPPING', 'false', 'Include Shipping value in amount before discount calculation?', 6, 5, 'zen_cfg_select_option([\'true\', \'false\'], ', now())");

        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function ,date_added) values ('Include Tax', 'MODULE_ORDER_TOTAL_GROUP_PRICING_INC_TAX', 'true', 'Include Tax value in amount before discount calculation?', 6, 6,'zen_cfg_select_option([\'true\', \'false\'], ', now())");
  }

    public function help()
    {
       return ['link' => 'https://docs.zen-cart.com/user/order_total/group_pricing/']; 
    }

    public function remove()
    {
        global $db;

        $keys = array_merge($this->keys(), ['MODULE_ORDER_TOTAL_GROUP_PRICING_CALC_TAX', 'MODULE_ORDER_TOTAL_GROUP_PRICING_TAX_CLASS']);
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $keys) . "')");
    }
}
