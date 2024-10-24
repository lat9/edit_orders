<?php
// -----
// A 'somewhat' modified shopping-cart class, used when editing an
// order during admin processing.
//
// Last updated: EO v5.0.0 (new)
//
namespace Zencart\Plugins\Admin\EditOrders;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * This session-instantiated class holds the original order
 * and any associated updates; EO's AJAX handler provides
 * the updates.
 */
class EoOrderChanges
{
    protected \stdClass $original;
    protected \stdClass $updated;
    protected array $upridMapping = [];
    protected array $opIdMapping = [];
    protected array $totalsMapping = [];
    protected array $ordersStatuses;

    protected bool $isGuestCheckout;
    protected bool $isWholesale;

    public function __construct(\order $original_order)
    {
        $this->original = new \stdClass();
        $this->original->content_type = $original_order->content_type;
        $this->original->info = $original_order->info;
        $this->original->customer = $original_order->customer;
        $this->original->billing = $original_order->billing;
        $this->original->delivery = $original_order->delivery;
        $this->original->products = $original_order->products;
        $this->original->statuses = $original_order->statuses;
        $this->original->totals = $original_order->totals;
        $this->generateProductMappings();
        $this->generateOrderTotalsMappings();

        $this->updated = clone $this->original;

        global $sniffer, $db;
        $this->isGuestCheckout = false;
        if ($sniffer->field_exists(TABLE_ORDERS, 'is_guest_checkout')) {
            $is_guest_checkout = $db->Execute(
                "SELECT is_guest_checkout
                   FROM " . TABLE_ORDERS . "
                  WHERE orders_id = " . (int)$original_order->info['order_id'] . "
                  LIMIT 1"
            );
            $this->isGuestCheckout = !empty($is_guest_checkout->fields['is_guest_checkout']);
        }

        $this->isWholesale = false;
        if ($sniffer->field_exists(TABLE_ORDERS, 'is_wholesale')) {
            $is_wholesale = $db->Execute(
                "SELECT is_wholesale
                   FROM " . TABLE_ORDERS . "
                  WHERE orders_id = " . (int)$original_order->info['order_id'] . "
                  LIMIT 1"
            );
            $this->isWholesale = !empty($is_wholesale->fields['is_wholesale']);
        }
    }
    protected function generateProductMappings(): void
    {
        for ($i = 0, $n = count($this->original->products); $i < $n; $i++) {
            $this->upridMapping[$this->original->products[$i]['uprid']] = $i;
            $this->opIdMapping[$this->original->products[$i]['orders_products_id']] = $i;
        }
    }

    // -----
    // Note: The totals' mappings are done as an array, since there can be multiple
    // entries for ot_tax, depending on the setting for SHOW_SPLIT_TAX_CHECKOUT, i.e.
    // My Store :: Show Split Tax Lines.
    //
    protected function generateOrderTotalsMappings(): void
    {
        for ($i = 0, $n = count($this->original->totals); $i < $n; $i++) {
            $this->totalsMapping[$this->original->totals[$i]['class']][] = [
                'index' => $i,
                'title' => $this->original->totals[$i]['title'],
            ];
        }
    }

    public function isGuestCheckout(): bool
    {
        return $this->isGuestCheckout;
    }

    public function isWholesale(): bool
    {
        return $this->isWholesale;
    }

    public function getOriginalOrder(): \stdClass
    {
        return $this->original;
    }
    
    public function getUpdatedOrder(): \stdClass
    {
        return $this->updated;
    }

    public function saveOrdersStatuses(array $status_array): void
    {
        $this->ordersStatuses = $status_array;
    }

    public function saveAdditionalAddressFieldLabels(string $address_type, array $additional_labels): void
    {
        $this->original->$address_type['labels'] = $additional_labels;
    }

    public function getAdditionalAddressFieldLabels(string $address_type): array
    {
        return $this->original->$address_type['labels'];
    }

    public function updateAddressInfo(string $address_type, array $address_info, array $field_labels): int
    {
        if (!isset($this->updated->$address_type['changes'])) {
            $this->updated->$address_type['changes'] = [];
        }

        foreach ($address_info as $key => $value) {
            if (!isset($this->original->$address_type[$key])) {
                continue;
            }

            if (isset($this->original->$address_type[$key]) && $this->original->$address_type[$key] != $value) {
                $this->updated->$address_type[$key] = $value;
                $this->updated->$address_type['changes'][$key] = $field_labels[$key];
            } else {
                unset($this->updated->$address_type['changes'][$key]);
            }
        }

        return count($this->updated->$address_type['changes']);
    }

    public function addComment(array $posted_values): void
    {
        $status = (int)$posted_values['status'];
        $this->updated->statuses['changes'] = [];
        foreach ($posted_values as $key => $value) {
            switch ($key) {
                case 'status':
                    $this->updated->statuses['changes']['status'] = $status;
                    break;
                case 'notify':
                    $this->updated->statuses['changes']['notify'] = (int)$value;
                    break;
                case 'comments':
                    $this->updated->statuses['changes']['message'] = $value;
                    break;
                default:
                    $this->updated->statuses['changes'][$key] = $value;
                    break;
            }
        }
        $this->updated->statuses['changes']['notify_comments'] = isset($posted_values['notify_comments']);

        if ($status !== (int)$this->original->info['orders_status']) {
            $this->updated->info['orders_status'] = $status;
            $this->updated->info['changes']['orders_status'] = ENTRY_STATUS;
        }
    }

    public function removeComment(): array
    {
        $this->updated->info['orders_status'] = $this->original->info['orders_status'];
        unset($this->updated->statuses['changes'], $this->updated->info['changes']['orders_status']);
        switch (EO_CUSTOMER_NOTIFICATION_DEFAULT) {
            case 'Hidden':
                $notify_default = '-1';
                break;
            case 'No Email':
                $notify_default = '0';
                break;
            default:
                $notify_default = '1';
                break;
        }
        return [
            'notify_default' => $notify_default,
            'orders_status' => $this->original->info['orders_status'],
        ];
    }

    public function getChangedValues(): array
    {
        $updated_order = $this->updated;
        $changes = [];
        if (!empty($updated_order->info['changes'])) {
            $changes[TEXT_PANEL_HEADER_ADDL_INFO] = $this->getOrderInfoChanges();
        }

        // -----
        // A comment-addition is reported differently.
        //
        if (!empty($updated_order->statuses['changes'])) {
            $changes['osh_info'][] = [
                'label' => TEXT_COMMENT_ADDED,
                'updated' => $this->updated->statuses['changes'],
            ];
        }

        if (!empty($updated_order->customer['changes'])) {
            $changes[ENTRY_CUSTOMER] = $this->getAddressChangedValues('customer');
        }

        if (!empty($updated_order->shipping['changes'])) {
            $changes[ENTRY_SHIPPING_ADDRESS] = $this->getAddressChangedValues('delivery');
        }

        if (!empty($updated_order->billing['changes'])) {
            $changes[ENTRY_BILLING_ADDRESS] = $this->getAddressChangedValues('billing');
        }

        return $changes;
    }

    protected function getOrderInfoChanges(): array
    {
        $info_changes = [];
        foreach ($this->updated->info['changes'] as $key => $label) {
            switch ($key) {
                case 'orders_status':
                    $original_status = $this->original->info['orders_status'];
                    $updated_status = $this->updated->info['orders_status'];
                    $info_changes[] = [
                        'label' => $label,
                        'original' => $this->ordersStatuses[$original_status] ?? ('Unknown [' . $original_status . ']'),
                        'updated' => $this->ordersStatuses[$updated_status] ?? ('Unknown [' . $updated_status . ']'),
                    ];
                    break;
                default:
                    $info_changes[] = [
                        'label' => $label,
                        'original' => $this->original->info[$key],
                        'updated' => $this->updated->info[$key],
                    ];
                    break;
            }
        }

        return $info_changes;
    }

    protected function getAddressChangedValues(string $address_type): array
    {
        $address_changes = [];
        foreach ($this->updated->$address_type['changes'] as $next_field => $label) {
            $original_value = $this->original->$address_type[$next_field];
            $updated_value = $this->updated->$address_type[$next_field];
            if ($next_field === 'country_id') {
                $original_value = zen_get_country_name((int)$this->original->$address_type['country_id']);
                $updated_value = zen_get_country_name((int)$this->updated->$address_type['country_id']);
            } elseif ($next_field === 'zone_id') {
                if (isset($this->updated->$address_type['changes']['country_id'])) {
                    $country_id = (int)$this->updated->$address_type['country_id'];
                } else {
                    $country_id = (int)$this->original->$address_type['country_id'];
                }
                $updated_value = zen_get_zone_name($country_id, (int)$this->updated->$address_type['zone_id']);
                $original_value = zen_get_zone_name((int)$this->original->$address_type['country_id'], (int)$this->original->$address_type['zone_id']);
            }
            $address_changes[] = [
                'label' => $label,
                'original' => $original_value,
                'updated' => $updated_value,
            ];
        }
        return $address_changes;
    }

    public function updateShippingInfo(string $shipping_module_code, string $shipping_method, string $shipping_cost, string $shipping_tax_rate): void
    {
        $shipping_info_fields = ['shipping_method', 'shipping_module_code', 'shipping_cost', 'shipping_tax_rate'];
        foreach ($shipping_info_fields as $field_name) {
            $value = $$field_name;
            if ($field_name === 'shipping_method' || $field_name === 'shipping_module_code') {
                if ($this->original->info[$field_name] === $value) {
                    unset($this->updated->info['changes'][$field_name]);
                } else {
                    $this->updated->info[$field_name] = $value;
                    $this->updated->info['changes'][$field_name] = $field_name;
                }
                continue;
            }

            $value = $this->convertToIntOrFloat($value);
            if ($this->original->info[$field_name] == $value) {
                unset($this->updated->info['changes'][$field_name]);
            } else {
                $this->updated->info[$field_name] = $value;
                $this->updated->info['changes'][$field_name] = $field_name;
            }
        }
    }

    public function saveTotalsInfoChanges(array $order_info): int
    {
        $info_total_fields = ['subtotal', 'total', 'tax', 'order_weight', 'coupon_code'];
        foreach ($info_total_fields as $field_name) {
            if ($this->original->info[$field_name] == $order_info[$field_name]) {
                unset($this->updated->info['changes'][$field_name]);
                continue;
            }
            $this->updated->info[$field_name] = $order_info[$field_name];
            $this->updated->info['changes'][$field_name] = $field_name;
        }
        return count($this->updated->info['changes']);
    }

    // -----
    // Convert a string value to either an int or float, depending on
    // the presence of a '.' in the value.
    //
    protected function convertToIntOrFloat(string $value): int|float
    {
        if (strpos($value, '.') === false) {
            return (int)$value;
        }
        return (float)$value;
    }
}
