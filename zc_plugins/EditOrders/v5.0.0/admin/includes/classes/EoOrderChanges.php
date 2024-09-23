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

        $this->updated = clone $this->original;
    }

    public function getOriginalOrder(): \stdClass
    {
        return $this->original;
    }
    
    public function getUpdatedOrder(): \stdClass
    {
        return $this->updated;
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

    public function getChangedValues(): array
    {
        $updated_order = $this->updated;
        $changes = [];
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
}
