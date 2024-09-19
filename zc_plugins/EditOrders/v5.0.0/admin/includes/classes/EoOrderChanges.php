<?php
// -----
// A 'somewhat' modified shopping-cart class, used when editing an
// order during admin processing.
//
// Last updated: EO v5.0.0 (new)
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * This session-instantiated class holds the original order
 * and any associated updates. Used by EO's AJAX handlers
 * to provide those updates.
 */
class EoOrderChanges
{
    public \stdClass $original;
    public \stdClass $updated;

    public function __construct(\order $original_order)
    {
        $this->original = new stdClass();
        $this->original->content_type = $original_order->content_type;
        $this->original->info = $original_order->info;
        $this->original->customer = $original_order->customer;
        $this->original->billing = $original_order->billing;
        $this->original->delivery = $original_order->delivery;
        $this->original->products = $original_order->products;
        $this->original->statuses = $original_order->statuses;
        $this->original->totals = $original_order->totals;

        $this->updated = new stdClass();
        $this->updated = $this->original;
    }

    public function updateAddressInfo(string $address_type, array $address_info): bool
    {
        return true;
    }
}
