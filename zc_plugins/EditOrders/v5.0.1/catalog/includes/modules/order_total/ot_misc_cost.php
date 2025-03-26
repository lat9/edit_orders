<?php
// -----
// An order-total module to gather any miscellaneous cost associated with an order, created by lat9 (https://vinosdefrutastropicales.com).
//
// Last modified EO v5.0.0
//
class ot_misc_cost
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
    * @var string
    */
    public string $description;

    /**
    * The sort order at which to apply this Order Total Module
    * @var string
    */
    public int|null $sort_order;

    /**
    * Indicates if this module is enabled
    */
    public bool $enabled = false;

    /**
    * Indicates that the module can be added via Edit Orders.
    */
    public bool $credit_class = true;
    public array $eoInfo = [];

    /**
    * The output from this Order Total Module
    */
    public array $output;

    /**
    * The Tax Class for this Order Total Module
    */
    public int $tax_class_id;

    /**
    * Class constructor.
    */
    public function __construct()
    {
        $this->code = 'ot_misc_cost';
        $this->title = MODULE_ORDER_TOTAL_MISC_COST_TITLE;
        $this->description = MODULE_ORDER_TOTAL_MISC_COST_DESCRIPTION;

        $this->sort_order = (defined('MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER')) ? ((int)MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER) : null;
        if (null === $this->sort_order) {
            return;
        }
        $this->tax_class_id = (int)MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS;
        $this->enabled = (IS_ADMIN_FLAG === true);

        $this->eoInfo = [
            'installed' => false,
            'title' => $this->title,
            'value' => 0,
        ];

        $this->output = [];
    }

    public function process(): void
    {
        if ($this->enabled === false || $this->eoInfo['installed'] === false) {
            $this->enabled = false;
            return;
        }

        global $currencies, $order;

        $title = $this->eoInfo['title'];
        $value = $this->eoInfo['value'];

        $order->info['total'] += $value;

        if ($this->tax_class_id !== 0) {
            $tax_rate = zen_get_tax_rate($this->tax_class_id);
            $misc_tax = $currencies->value(zen_calculate_tax($value, $tax_rate), false, $order->info['currency'], $order->info['currency_value']);
            $order->info['total'] += $misc_tax;
            $order->info['tax'] += $misc_tax;

            $tax_description = zen_get_tax_description($this->tax_class_id);
            if (!isset($order->info['tax_groups'][$tax_description])) {
                $order->info['tax_groups'][$tax_description] = $misc_tax;
            } else {
                $order->info['tax_groups'][$tax_description] += $misc_tax;
            }
        }

        $this->output[] = [
            'title' => rtrim($title, ' :') . ':',
            'text' => $currencies->format($value, true, $order->info['currency'], $order->info['currency_value']),
            'value' => $value,
        ];
    }

    public function credit_selection(): array
    {
        if ($this->enabled === false) {
            return [];
        }

        if (MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE === 'true') {
            $fields = [
                [
                    'tag' => 'title-' . $this->code,
                    'field' => zen_draw_input_field('title', $this->eoInfo['title'], 'id="title-' . $this->code . '" class="form-control"'),
                    'title' => TEXT_LABEL_TITLE,
                ],
            ];
        }
        $fields[] = [
            'tag' => 'val-' . $this->code,
            'field' => zen_draw_input_field('value', $this->eoInfo['value'], 'id="val-' . $this->code . '" class="form-control"'),
            'title' => TEXT_LABEL_VALUE,
        ];

        $selection = [
            'id' => $this->code,
            'module' => $this->title,
            'fields' => $fields,
        ];
        return $selection;
    }

    public function collect_posts(): void
    {
        if (($_POST['ot_class'] ?? '') === $this->code) {
            $this->eoInfo['value'] = (strpos($_POST['value'], '.') === false) ? (int)$_POST['value'] : (float)$_POST['value'];
            if (MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE === 'true') {
                $this->eoInfo['title'] = $_POST['title'];
            }
            $this->enabled = ($this->eoInfo['value'] != 0 && $this->eoInfo['title'] !== '');
            $this->eoInfo['installed'] = $this->enabled;
        }
    }

    public function clear_posts(): void
    {
    }

    public function update_credit_account($i): void
    {
    }

    public function apply_credit(): void
    {
    }

    public function check(): bool
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

    public function keys(): array
    {
        return [
            'MODULE_ORDER_TOTAL_MISC_COST_STATUS',
            'MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER',
            'MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS',
            'MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE'
        ];
    }

    public function install(): void
    {
        global $db;
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 
             VALUES 
                ('This module is installed', 'MODULE_ORDER_TOTAL_MISC_COST_STATUS', 'true', '', 6, 1,'zen_cfg_select_option([\'true\'], ', now())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 
             VALUES 
                ('Sort Order', 'MODULE_ORDER_TOTAL_MISC_COST_SORT_ORDER', '410', 'Sort order of display.', 6, 2, now())"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 
             VALUES 
                ('Allow changing the title', 'MODULE_ORDER_TOTAL_MISC_COST_CHANGE_TITLE', 'false', 'Allow changing the title of the module while editing an order', 6, 3, 'zen_cfg_select_option([\'true\',\'false\'], ', now())"
         );
         $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) 
             VALUES 
                ('Tax Class', 'MODULE_ORDER_TOTAL_MISC_COST_TAX_CLASS', '0', 'Use the following tax class for any Miscellaneous Cost. If you are applying a tax to this order-total, remember to:<ol><li>Set its <em>Sort Order</em> to a value greater than that for <code>ot_coupon</code>.</li><li>Set its <em>Sort Order</em> to a value less than that for <code>ot_tax</code>.</li></ol>', 6, 7, 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())"
          );
     }

    public function remove(): void
    {
        global $db;
        $keys = "'" . implode("', '", $this->keys()) . "'";
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN (" . $keys . ")"
        );
    }
}
