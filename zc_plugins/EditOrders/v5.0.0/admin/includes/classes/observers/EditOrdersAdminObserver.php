<?php
// -----
// Admin-level observer class, adds "Edit Orders" buttons and links to Customers->Orders processing.
// Copyright (C) 2017-2024, Vinos de Frutas Tropicales.
//
// Last updated: EO v5.0.0
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class EditOrdersAdminObserver extends base
{
    public function __construct()
    {
        global $current_page;

        $current_page_base = basename($current_page, '.php');

        // -----
        // If on the 'orders' page, watch for events pertinent to that page's processing.
        //
        if ($current_page_base === FILENAME_ORDERS) {
            $orders_page_notifications = [
                'NOTIFY_ADMIN_ORDERS_EDIT_BUTTONS',
            ];
            if (EO_SHOW_EDIT_ORDER_ICON === 'Yes') {
                $orders_page_notifications[] = 'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE';
            }
            if (EO_SHOW_EDIT_ORDER_BUTTON === 'Both' || EO_SHOW_EDIT_ORDER_BUTTON === 'Top Only') {
                $orders_page_notifications[] = 'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS';
            }
            if (EO_SHOW_EDIT_ORDER_BUTTON === 'Both' || EO_SHOW_EDIT_ORDER_BUTTON === 'Bottom Only') {
                $orders_page_notifications[] = 'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END';
            }
            $this->attach($this, $orders_page_notifications);
        // -----
        // If on the 'edit_orders' page, watch for events pertinent to that page's processing.
        //
        } elseif ($current_page_base === FILENAME_EDIT_ORDERS) {
            $this->attach(
                $this,
                [
                    /* From /includes/functions/functions_taxes.php */
                    'ZEN_GET_TAX_LOCATIONS',

                    /* From /includes/modules/order_total/ot_shipping.php */
                    'NOTIFY_OT_SHIPPING_TAX_CALCS',

                    /* From admin/includes/functions/functions_help.php */
                    'NOTIFIER_PLUGIN_HELP_PAGE_URL_LOOKUP',
                ]
            );
        }
    }

    // -----
    // Issued during the orders-listing sidebar generation, after the upper button-list has been created.
    //
    // $oInfo ... Contains the current $oInfo object, which contains the orders-id.
    // $contents ... A reference to the current $contents array; the 'Edit' button will be added on its own line.
    //
    protected function notify_admin_orders_menu_buttons(&$class, string $e, $oInfo, array &$contents): void
    {
        $contents[] = [
            'align' => 'text-center',
            'text' => $this->addEditOrderButton((int)$oInfo->orders_id),
        ];
    }

    // -----
    // Issued during the orders-listing sidebar generation, after the lower-button-list has been created.
    //
    // $oInfo ... Contains the current $oInfo object (could be empty), which contains the orders-id.
    // $contents ... A reference to the current $contents array; the 'Edit' button will be added on its own line.
    //
    protected function notify_admin_orders_menu_buttons_end(&$class, string $e, $oInfo, array &$contents): void
    {
        if (!empty($oInfo) && (!isset($_GET['action']) || $_GET['action'] !== 'delete')) {
            $contents[] = [
                'align' => 'text-center',
                'text' => $this->addEditOrderButton((int)$oInfo->orders_id),
            ];
        }
    }

    // -----
    // Issued during the orders-listing generation for each order, gives us a chance to add the icon to
    // quickly edit the associated order. Displayed only if so-configured.
    //
    protected function notify_admin_orders_show_order_difference(&$class, string $e, $x, array &$orders_fields, &$x2, string &$extra_action_icons): void
    {
        $extra_action_icons .= $this->createEditOrdersLink(
            (int)$orders_fields['orders_id'],
            '',
            '<i class="fa fa-wrench fa-sm overlay" title="' . EO_ICON_DETAILS . '"></i>'
        );
    }

    // -----
    // Issued during an order's detailed display, allows the insertion of the "edit" button to link
    // the order to the "Edit Orders" processing.
    //
    protected function notify_admin_orders_edit_buttons(&$class, string $e, $orders_id, &$x, string &$extra_buttons_string): void
    {
        $extra_buttons_string .= '&nbsp;' . $this->addEditOrderButton((int)$orders_id);
    }

    // -----
    // Issued during the order-totals' construction by the ot_shipping module, giving observers the chance
    // to override the shipping tax-related calculations.
    //
    // NOTE: The auto-loader has positioned the load of this class at 999, hopefully as the last watching observer
    // to load.  That allows this processing to 'assume' that it should provide that value if no other watcher
    // has intervened.
    //
    // Final Notes: 
    // 1) The ot_shipping module is actually loaded twice, first to get its sort-order and next to record its
    //    values in the database.  So that we don't double-up any shipping taxes, once those taxes (if any) 
    //    are applied to the order, we'll 'detach' from watching further issuances of this notification.
    // 2) The base EO processing doesn't instantiate the shipping method's class, so we'll install a fake one to indicate
    //    that there's no tax on shipping.  That way, an admin can continue to override the shipping tax.  Previous
    //    processing resulted in a PHP Notice from the ot_shipping class since its constructor is 'looking' to see
    //    what tax-rate to apply.
    //
    protected function notify_ot_shipping_tax_calcs(&$class, string $e, $x, bool &$external_shipping_tax_handler, &$shipping_tax, string &$shipping_tax_description): void
    {
        global $eo;

        $eo->eoUpdateOrderShippingTax($external_shipping_tax_handler, $shipping_tax, $shipping_tax_description);
        $external_shipping_tax_handler = true;
        $this->detach($this, ['NOTIFY_OT_SHIPPING_TAX_CALCS']);

        $module = (isset($_SESSION['shipping']['id'])) ? substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_')) : '';
        if ($module !== '' && $module !== 'free') {
            require DIR_WS_CLASSES . 'EditOrdersOtShippingStub.php';
            $GLOBALS[$module] = new EditOrdersOtShippingStub();
        }
    }

    protected function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5)
    {
        switch ($eventID) {
            // -----
            // Added v4.7.0, replacing function override on EO page.
            //
            // Note: For EO v5.0.0 and later, the order's initialization has
            // guaranteed that each of the billing/shipping addresses' 'country'
            // elements are arrays and that the addresses' 'zone_id' is set.
            //
            // On entry:
            //
            // $p1 ... (r/o) An associative array containing the 'store_country' and 'store_zone'.
            // $p2 ... (r/w) A reference to the $tax_address array to be returned (containing a 'country_id' and 'zone_id').
            //
            case 'ZEN_GET_TAX_LOCATIONS':
                global $order, $customer_country_id, $customer_zone_id, $eo;

                if (STORE_PRODUCT_TAX_BASIS === 'Store') {
                    $customer_country_id = STORE_COUNTRY;
                    $customer_zone_id = STORE_ZONE;
                } elseif (STORE_PRODUCT_TAX_BASIS === 'Billing') {
                    $customer_country_id = $order->billing['country']['id'];
                    $customer_zone_id = $order->billing['zone_id'];
                } elseif ($eo->eoOrderIsVirtual()) {
                    $customer_country_id = $order->billing['country']['id'];
                    $customer_zone_id = $order->billing['zone_id'];
                } else {
                    $customer_country_id = $order->delivery['country']['id'];
                    $customer_zone_id = $order->delivery['zone_id'];
                }

                $_SESSION['customer_country_id'] = $customer_country_id;
                $_SESSION['customer_zone_id'] = $customer_zone_id;

                $p2 = [
                    'zone_id' => $customer_zone_id,
                    'country_id' => $customer_country_id,
                ];
                break;

            // -----
            // Hooked only on the "Edit Orders" page, allows the insertion of a help-link
            // back to EO's GitHub wiki.
            //
            case 'NOTIFIER_PLUGIN_HELP_PAGE_URL_LOOKUP':
                $p2 = 'https://github.com/lat9/edit_orders/wiki';
                break;

            default:
                break;
        }
    }

    protected function addEditOrderButton(int $orders_id): string
    {
        return $this->createEditOrdersLink($orders_id, 'button', IMAGE_EDIT);
    }

    protected function createEditOrdersLink(int $orders_id, string $link_button, string $link_text): string
    {
        if ($link_button !== '') {
            $link_params = ' class="btn btn-primary" role="button"';
        } else {
            $link_params = ' class="btn btn-default btn-sm btn-edit" role="button"';
        }
        return
            '<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['oID', 'action']) . 'action=edit&oID=' . $orders_id) . '"' . $link_params . '>' .
                $link_text .
            '</a>';
    }
}
