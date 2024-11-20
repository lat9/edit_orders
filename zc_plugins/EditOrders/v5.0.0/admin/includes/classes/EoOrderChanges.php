<?php
// -----
// A 'somewhat' modified shopping-cart class, used when editing an
// order during admin processing.
//
// Last updated: EO v5.0.0 (new)
//
namespace Zencart\Plugins\Admin\EditOrders;

use Zencart\Plugins\Admin\EditOrders\EditOrders;

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
    public \stdClass $updated;
    protected int $orderId;
    public array $upridMapping = [];
    protected array $opIdMapping = [];
    protected array $totalsChanges = [];
    protected array $ordersStatuses;

    protected bool $isGuestCheckout;
    protected bool $isWholesale;

    public function __construct(\order $original_order)
    {
        $this->original = new \stdClass();
        $this->original->content_type = $original_order->content_type;
        $this->original->info = $original_order->info;
        $this->orderId = $original_order->info['order_id'];
        $this->original->customer = $original_order->customer;
        $this->original->billing = $original_order->billing;
        $this->original->delivery = $original_order->delivery;
        $this->original->products = $original_order->products;
        $this->original->statuses = $original_order->statuses;
        $this->original->totals = $original_order->totals;

        $this->generateProductMappings();

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

    public function getOrderId(): int
    {
        return $this->orderId;
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

    public function getTotalsChanges(): array
    {
        return $this->totalsChanges;
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

        if (!empty($this->totalsChanges)) {
            $changes['order_totals'] = $this->getOrderTotalsChangedValues();
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

    protected function getOrderTotalsChangedValues(): array
    {
        $ot_changes = [];
        foreach ($this->totalsChanges as $ot_index => $ot_status) {
            if ($ot_status === 'removed') {
                $ot_changes[$ot_index] = [
                    'status' => 'removed',
                    'label' => $this->original->totals[$ot_index]['class'],
                    'original' => $this->formatOrderTotalChanges($this->original->totals[$ot_index]),
                ];
                continue;
            }

            if ($ot_status === 'added') {
                $ot_changes[$ot_index] = [
                    'status' => 'added',
                    'label' => $this->updated->totals[$ot_index]['code'],
                    'updated' => $this->formatOrderTotalChanges($this->updated->totals[$ot_index]),
                ];
                continue;
            }

            $ot_changes[$ot_index] = [
                'status' => 'updated',
                'label' => $this->updated->totals[$ot_index]['class'],
                'original' => $this->formatOrderTotalChanges($this->original->totals[$ot_index]),
                'updated' => $this->formatOrderTotalChanges($this->updated->totals[$ot_index]),
            ];
        }
        return $ot_changes;
    }
    protected function formatOrderTotalChanges(array $total_info): string
    {
        return $total_info['title'] . '/' . $total_info['text'] . '/' . $total_info['value'];
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

    public function updateShippingInfo(string $shipping_module_code, string $shipping_method, string $shipping_cost, string $shipping_tax_rate): array
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
                if ($field_name !== 'shipping_cost') {
                    $this->updated->info['changes'][$field_name] = $field_name;
                }
            }
        }
        return $this->updated->info;
    }

    public function saveOrderInfoChanges(array $order_info): int
    {
        $info_total_fields = ['subtotal', 'total', 'tax', 'order_weight', 'coupon_code', 'shipping_method', 'shipping_module_code', 'shipping_tax_rate'];
        foreach ($info_total_fields as $field_name) {
            if ($this->original->info[$field_name] == $order_info[$field_name]) {
                unset($this->updated->info['changes'][$field_name]);
                continue;
            }
            $this->updated->info[$field_name] = $order_info[$field_name];
            $this->updated->info['changes'][$field_name] = $field_name;
        }
        return count($this->updated->info['changes'] ?? []);
    }

    public function saveOrderTotalsChanges(array $order_totals): int
    {
        $eo = new EditOrders($this->orderId);

        $remaining_totals = $this->updated->totals;
        foreach ($order_totals as $next_total) {
            $ot_index = $this->getOrderTotalIndex($next_total);

            // -----
            // If the current order-total has been added, record its values in
            // the updated order and note the fact that it's been added.
            //
            if ($ot_index === -1) {
                $ot_index = count($this->updated->totals);
                $this->updated->totals[] = $next_total;
                $this->totalsChanges[$ot_index] = 'added';
                continue;
            }

            // -----
            // Remove this *existing* order-total from the list of those 'remaining'.
            // That array's used as a final step to see if any order-totals have
            // been removed.
            //
            unset($remaining_totals[$ot_index]);

            // -----
            // If this order-total was previously added, simply record its
            // updated values.  Using null-coalesce since the current might not
            // have been previously changed!
            //
            if (($this->totalsChanges[$ot_index] ?? '') === 'added') {
                $this->updated->totals[$ot_index]['title'] = $next_total['title'];
                $this->updated->totals[$ot_index]['text'] = $next_total['text'];
                $this->updated->totals[$ot_index]['value'] = $next_total['value'];
                continue;
            }

            // -----
            // If the total's unchanged from its original value, reset the updated values
            // and remove any previously-recorded change for this total.
            //
            $original_total = $this->original->totals[$ot_index];
            if ($original_total['title'] === $next_total['title'] && $original_total['text'] === $next_total['text'] && $original_total['value'] == $next_total['value']) {
                $this->updated->totals[$ot_index] = $this->original->totals[$ot_index];
                unset($this->totalsChanges[$ot_index]);
                continue;
            }

            // -----
            // The total's changed from its original value.  Record the updated value(s)
            // and note that this total has been updated from the order's original.
            //
            $this->updated->totals[$ot_index]['title'] = $next_total['title'];
            $this->updated->totals[$ot_index]['text'] = $next_total['text'];
            $this->updated->totals[$ot_index]['value'] = $next_total['value'];
            $this->totalsChanges[$ot_index] = 'updated';
        }

        // -----
        // Finally, check to see if any totals have been removed. This could, for instance,
        // be an ot_tax type (if 'split tax lines' are in effect) or a minimum-order
        // fee.
        //
        // Note: If an order-total was previously added and is now removed, its removal
        // provides no change to the order!
        //
        foreach ($remaining_totals as $ot_index => $removed_total) {
            if (($this->totalsChanges[$ot_index] ?? '') === 'added') {
                unset($this->totalsChanges[$ot_index]);
                continue;
            }
            $this->totalsChanges[$ot_index] = 'removed';
        }

        return count($this->totalsChanges);
    }

    protected function getOrderTotalIndex(array $order_total): int
    {
        $index = -1;
        $ot_code = $order_total['code'] ?? $order_total['class'];
        foreach ($this->updated->totals as $ot_index => $next_total) {
            if ($ot_code !== 'ot_tax') {
                if (($next_total['class'] ?? $next_total['code']) === $ot_code) {
                    $index = $ot_index;
                    break;
                }
            } elseif ($next_total['title'] === $order_total['title']) {
                $index = $ot_index;
                break;
            }
        }
        return $index;
    }

    public function getOriginalProductByUprid(string $uprid): array
    {
        $index = $this->upridMapping[$uprid] ?? null;
        return ($index === null) ? [] : $this->original->products[$index];
    }
    public function getUpdatedProductByUprid(string $uprid): array
    {
        $index = $this->upridMapping[$uprid] ?? null;
        if ($index === null) {
            trigger_error("Requested product ($uprid) not present in the updated order.", E_USER_NOTICE);
            return [];
        }
        return $this->updated->products[$index];
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
