<?php
// -----
// A 'somewhat' modified shopping-cart class, used when editing an
// order during admin processing.
//
// Last updated: EO v5.0.0 (new)
//
namespace Zencart\Plugins\Admin\EditOrders;

use Zencart\Plugins\Admin\EditOrders\EditOrders;
use Zencart\Plugins\Admin\EditOrders\EoAttributes;
use Zencart\Traits\NotifierManager;

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
    use NotifierManager;

    protected \stdClass $original;
    public \stdClass $updated;
    protected int $orderId;

    protected array $upridMapping = [];
    protected array $productsChanges = [];
    protected bool $productBeingAdded = false;

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
        foreach ($this->original->totals as $index => $values) {
            $this->original->totals[$index]['value'] = $this->convertToIntOrFloat($values['value']);
        }

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

    public function productAddInProcess(): bool
    {
        return $this->productBeingAdded;
    }

    public function getOriginalOrder(): \stdClass
    {
        return clone $this->original;
    }

    public function getUpdatedOrder(): \stdClass
    {
        return clone $this->updated;
    }

    public function getUpdatedOrdersProducts(): array
    {
        $products = [];
        foreach ($this->updated->products as $next_product) {
            if (!empty($next_product) && ($this->productsChanges[$next_product['uprid']] ?? '') !== 'removed') {
                $products[] = $next_product;
            }
        }
        return $products;
    }

    public function saveCartContents(int $index, array $cart_contents): void
    {
        $this->original->products[$index]['cart_contents'] = $cart_contents;
        $this->updated->products[$index]['cart_contents'] = $cart_contents;
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

        if (!empty($updated_order->customer['changes'])) {
            $changes[ENTRY_CUSTOMER] = $this->getAddressChangedValues('customer');
        }

        if (!empty($updated_order->shipping['changes'])) {
            $changes[ENTRY_SHIPPING_ADDRESS] = $this->getAddressChangedValues('delivery');
        }

        if (!empty($updated_order->billing['changes'])) {
            $changes[ENTRY_BILLING_ADDRESS] = $this->getAddressChangedValues('billing');
        }

        if (!empty($this->totalsChanges)) {
            $changes['order_totals'] = $this->getOrderTotalsChangedValues();
        }

        if (!empty($this->productsChanges)) {
            $changes['products'] = $this->getProductsChangedValues();
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

    protected function getProductsChangedValues(): array
    {
        $product_changes = [];
        foreach ($this->productsChanges as $uprid => $change_status) {
            $index = $this->upridMapping[$uprid] ?? null;
            if ($index === null) {
                trigger_error(
                    "Unrecognized product ($uprid) change recorded.\nproductsChanges:\n" . var_export($this->productsChanges, true) .
                        "\nupridMapping:\n" . var_export($this->upridMapping, true)
                );
                continue;
            }

            switch ($change_status) {
                case 'removed':
                    $product = $this->original->products[$index];
                    $product_changes[$uprid] = [
                        'status' => 'removed',
                        'label' => sprintf(
                            TEXT_STATUS_PRODUCT_REMOVED,
                            (string)$product['qty'],
                            $this->getChangedProductName($product),
                            $product['model'],
                            (string)$product['final_price'],
                            (string)$product['tax']
                        ),
                        'original' => $product,
                    ];
                    break;
                case 'added':
                    $product = $this->updated->products[$index];
                    $product_changes[$uprid] = [
                        'status' => 'added',
                        'label' => sprintf(
                            TEXT_STATUS_PRODUCT_ADDED,
                            (string)$product['qty'],
                            $this->getChangedProductName($product),
                            $product['model'],
                            (string)$product['final_price'],
                            (string)$product['tax']
                        ),
                        'updated' => $product,
                    ];
                    break;
                default:
                    $product = $this->original->products[$index];

                    $original_qty = $this->original->products[$index]['qty'];
                    $updated_qty = $this->updated->products[$index]['qty'];
                    $changed_qty = $updated_qty - $original_qty;
                    $changed_message = ($changed_qty == 0) ? TEXT_STATUS_PRODUCT_CHANGED : (($changed_qty < 0) ? TEXT_STATUS_PRODUCT_REMOVED : TEXT_STATUS_PRODUCT_ADDED);
                    $product_changes[$uprid] = [
                        'status' => 'updated',
                        'changed_qty' => $changed_qty,
                        'label' => sprintf(
                            $changed_message,
                            ($changed_qty == 0) ? 'n/a' : (string)abs($changed_qty),
                            $this->getChangedProductName($product),
                            $product['model'],
                            (string)$product['final_price'],
                            (string)$product['tax']
                        ),
                        'original' => $this->original->products[$index],
                        'updated' => $this->updated->products[$index],
                    ];
                    break;
            }
        }
        return $product_changes;
    }
    protected function getChangedProductName(array $product): string
    {
        $name = $product['name'];
        if (!empty($product['attributes'])) {
            $attributes_display = [];
            foreach ($product['attributes'] as $next_attr) {
                $attributes_display[] = $next_attr['option'] . ': ' . nl2br(zen_output_string_protected($next_attr['value']));
            }
            $name .= ' <small>(' . implode(', ', $attributes_display) . ')</small>';
        }
        return $name;
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
            if (is_float($order_info[$field_name])) {
                $order_info[$field_name] = round($order_info[$field_name], 4);
            }
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

            if (is_string($next_total['value'])) {
                $next_total['value'] = $this->convertToIntOrFloat($next_total['value']);
            } elseif (is_float($next_total['value'])) {
                $next_total['value'] = round($next_total['value'], 4);
            }

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
        return ($index === null || !isset($this->original->products[$index])) ? [] : $this->original->products[$index];
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
    // Update a product/product-variant that already exists in the order.
    //
    // The $original_uprid identifies the pre-existing product being updated and $product_updates
    // contains the updated fields posted via the modal product-update form.
    //
    public function updateProductInOrder(string $original_uprid, array $product_updates): void
    {
        $index = $this->upridMapping[$original_uprid] ?? null;
        if ($index !== null) {
            $changes = 0;
            $is_removal = false;
            $is_variant_change = false;
            foreach ($product_updates as $field => $value) {
                if ($field === 'attributes') {
                    $updated_uprid = zen_get_uprid((int)$original_uprid, $this->reformatPostedAttributes($value));
                    if ($original_uprid !== $updated_uprid) {
                        $changes++;
                        $is_variant_change = true;
                    }
                    continue;
                }

                if (($this->original->products[$index][$field] ?? $this->updated->products[$index][$field]) != $value) {
                    $changes++;
                }

                if ($field === 'qty' && $value == 0) {
                    $is_removal = true;
                }
                $this->updated->products[$index][$field] = $value;
            }

            if ($changes === 0 && $is_variant_change === false && $is_removal === false) {
                if (($_GET['method'] ?? '') !== 'addNewProduct' && $this->isProductAdded($original_uprid) === false) {
                    unset($this->productsChanges[$original_uprid]);
                }
                $_SESSION['cart']->calculateTotalAndWeight($this->updated->products);

            } elseif ($is_variant_change === true) {
                $this->changeProductVariant($index, $original_uprid, $updated_uprid, $product_updates, $is_removal);

            } else {
                $this->recordProductChanges($changes, $index, $original_uprid, $is_removal);
            }
        }
    }

    // -----
    // Add a new product to the order. Note that while the new-product path has been
    // taken by the admin, it's possible that the specified product already exists in
    // the order!
    //
    public function addNewProductToOrder(string $prid, array $product_updates): void
    {
        $uprid = zen_get_uprid((int)$prid, $this->reformatPostedAttributes($product_updates['attributes'] ?? []));

        $index = $this->upridMapping[$uprid] ?? null;
        if ($index !== null) {
            $this->updateProductInOrder($uprid, $product_updates);
            return;
        }

        $index = $this->addProductToOrder($uprid, $product_updates);

        $this->recordProductChanges(0, $index, $uprid, false);
    }

    protected function isProductAdded(string $uprid): bool
    {
        return (($this->productsChanges[$uprid] ?? '') === 'added');
    }

    // -----
    // The attribute values from a product's update or addition are posted in a
    // associative array similar to:
    //
    //  'id' => [
    //    1 => '26',
    //    2 => '21',
    //    5 => '24',
    //    6 => '23',
    //    '13_chk35' => 'on',
    //    'file_8' => '1. cake-concert-0.jpg',
    //    'file_7' => '',
    //    'txt_10' => 'Some text stuff',
    //    'txt_9' => '',
    //    'txt_11' => '',
    //  ]
    //
    // To match the footprint used on the storefront when calculating a
    // product's uprid:
    //
    // 1. The xx_chkyy attributes need to be converted into
    //    an array of arrays with the attribute-value.
    // 2. The file_xx attributes need to be the last elements of the
    //    modified attributes' array and renamed to use a 'txt_' prefix.
    //
    // Note: Admin sanitization 'complexities' made using the xx_chkyy format instead
    // of the array-of-arrays much less complicated!
    //
    protected function reformatPostedAttributes(array $posted_attributes): array
    {
        $reformatted_attributes = [];
        $file_type_attributes = [];
        foreach ($posted_attributes as $key => $value) {
            if (str_contains($key, '_chk')) {
                [$option_id, $values_id] = explode('_chk', $key);
                $reformatted_attributes[$option_id][$values_id] = $values_id;
                continue;
            }

            if (str_starts_with($key, 'file_')) {
                $txt_key = str_replace('file_', 'txt_', $key);
                $file_type_attributes[$txt_key] = $value;
                continue;
            }

            $reformatted_attributes[(string)$key] = (string)$value;
        }

        return $reformatted_attributes + $file_type_attributes;
    }

    protected function recordProductChanges(int $changes, int $index, string $uprid, bool $is_removal): void
    {
        if ($is_removal === false) {
            if ($changes !== 0) {
                $this->productsChanges[$uprid] ??= 'updated';
            }
            $this->notify('NOTIFY_EO_RECORD_CHANGES',
                [
                    'uprid' => $uprid,
                    'original_product' => ($this->original->products[$index] ?? []),
                ],
                $this->updated->products[$index]
            );
            $_SESSION['cart']->calculateTotalAndWeight($this->updated->products);

        } elseif ($this->isProductAdded($uprid) === true) {
            unset($this->productsChanges[$uprid]);
            $_SESSION['cart']->removeProduct($uprid, $this->updated->products[$index]);

        } else {
            $this->productsChanges[$uprid] = 'removed';
            $_SESSION['cart']->removeProduct($uprid, $this->updated->products[$index]);
        }

        $eo = new EditOrders();
        $this->updated->content_type = $eo->setContentType($this->updated->products);
    }

    // -----
    // During an order's 'creation', it's possible that a product's tax, onetime-charge or
    // final-price have changed. If so, record those updates in the updated order.
    //
    public function recordCreatedProductChanges(string $uprid, array $product): void
    {
        $updated_fields = [];
        foreach (['tax', 'final_price', 'onetime_charges'] as $field) {
            $updated_fields[$field] = round((float)$product[$field], 6);
        }
        $this->updateProductInOrder($uprid, $updated_fields);
    }

    // -----
    // If an ordered product's attributes are changed, EO treats that as if the
    // original product-variant was removed from the order and the updated
    // product-variant was updated/added.
    //
    // Note: This method is called **only** when a product's original and updated uprid
    // are not the same, implying a change in the product's variant.
    //
    protected function changeProductVariant(int $original_index, string $original_uprid, string $updated_uprid, array $product_updates, bool $is_removal): void
    {
        // -----
        // First, deal with the original product-variant's removal.  If the variant
        // was added during this order-edit, it's simply removed from the
        // updated order.  Otherwise, the product-variant is marked as to-be-removed.
        //
        if ($this->isProductAdded($original_uprid) === true) {
            $_SESSION['cart']->removeProduct($original_uprid, $this->updated->products[$original_index]);
            $this->updated->products[$original_index] = [];
            unset($this->productsChanges[$original_uprid], $this->upridMapping[$original_uprid]);
        } else {
            $this->productsChanges[$original_uprid] = 'removed';
            $_SESSION['cart']->removeProduct($original_uprid, $this->original->products[$original_index]);
        }

        // -----
        // Next, either add the 'new' product-variant to the edited order or update
        // information associated with the existing variant.
        //
        // Start by checking to see if the product-variant has ever been in the
        // order for this edit session.
        //
        $updated_index = $this->upridMapping[$updated_uprid] ?? null;

        // -----
        // If this is a brand-new variant for the cart, add it.
        //
        if ($updated_index === null) {
            $this->addProductToOrder($updated_uprid, $product_updates);
            return;
        }

        // -----
        // If the to-be-updated variant was previously removed from the order, remove
        // that status-change.
        //
        if (($this->productsChanges[$updated_uprid] ?? '') === 'removed') {
            unset($this->productsChanges[$updated_uprid]);
        }

        // -----
        // Compare the to-be-updated product-variant to the posted changes, continuing
        // only if non-attribute-related changes are present.
        //
        $changes = 0;
        foreach ($product_updates as $field => $value) {
            if ($field === 'attributes' || $this->updated->products[$updated_index][$field] == $value) {
                continue;
            }

            $changes++;
            $this->updated->products[$updated_index][$field] = $value;
        }

        if ($changes !== 0 || $is_removal === true) {
            $this->recordProductChanges($changes, $updated_index, $updated_uprid, $is_removal);
        }
    }

    protected function addProductToOrder(string $uprid, array $product): int
    {
        global $order, $currencies, $eo;

        $eo ??= new EditOrders($this->getOrderId());

        $eo->setProductBeingAdded(true);
        $cart_product = $eo->addProductToCart($uprid, $product);

        $index = count($this->updated->products);
        $this->upridMapping[$uprid] = $index;

        $this->updated->products[] = $cart_product;
        $this->productsChanges[$uprid] = 'added';

        $eo->createOrderFromCart();
        $eo->setProductBeingAdded(false);

        foreach ($order->products as $next_product) {
            if ($next_product['id'] != $uprid) {
                continue;
            }

            $this->updated->products[$index] = array_merge($this->updated->products[$index], $next_product);
            return $index;
        }
    }

    public function getProductsChangeCount(): int
    {
        return count($this->productsChanges);
    }

    public function getProductsChanges(): array
    {
        return $this->productsChanges;
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
