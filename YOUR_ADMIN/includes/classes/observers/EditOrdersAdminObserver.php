<?php
// -----
// Admin-level observer class, adds "Edit Orders" buttons and links to Customers->Orders processing.
// Copyright (C) 2017-2023, Vinos de Frutas Tropicales.
//
// Last updated for EO v4.7.0
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
                    /* From /includes/classes/order.php */
                    'NOTIFY_ORDER_AFTER_QUERY',

                    /* From /includes/functions/functions_taxes.php */
                    'ZEN_GET_TAX_LOCATIONS',

                    /* From /includes/modules/order_total/ot_shipping.php */
                    'NOTIFY_OT_SHIPPING_TAX_CALCS',
                ]
            );
        }
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5)
    {
        switch ($eventID) {
            // -----
            // Issued during the orders-listing sidebar generation, after the upper button-list has been created.
            //
            // $p1 ... Contains the current $oInfo object, which contains the orders-id.
            // $p2 ... A reference to the current $contents array; the 'Edit' button will be added on its own line.
            //
            case 'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS': 
                $p2[] = [
                    'align' => 'text-center',
                    'text' => $this->addEditOrderButton($p1->orders_id),
                ];
                break;

            // -----
            // Issued during the orders-listing sidebar generation, after the lower-button-list has been created.
            //
            // $p1 ... Contains the current $oInfo object (could be empty), containing the orders-id.
            // $p2 ... A reference to the current $contents array; the 'Edit' button will be added on its own line.
            //
            case 'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END':
                if ((!isset($_GET['action']) || $_GET['action'] !== 'delete') && !empty($p1)) {
                    $p2[] = [
                        'align' => 'text-center',
                        'text' => $this->addEditOrderButton($p1->orders_id),
                    ];
                }
                break;

            // -----
            // Issued during the orders-listing generation for each order, gives us a chance to add the icon to
            // quickly edit the associated order.
            //
            // Starting with v4.7.0, displayed only if so-configured.
            //
            // $p1 ... An empty array
            // $p2 ... A reference to the current order's database fields array.
            // $p3 ... A reference to the $show_difference variable, unused by this processing.
            // $p4 ... A reference to the $extra_action_icons variable, which will be augmented with an icon
            //         linking to this order's EO processing.
            //
            case 'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE':
                $p4 .= $this->createEditOrdersLink(
                    $p2['orders_id'],
                    null,
                    '<i class="fa fa-wrench fa-sm overlay" title="' . EO_ICON_DETAILS . '"></i>'
                );
                break;

            // -----
            // Issued during an order's detailed display, allows the insertion of the "edit" button to link
            // the order to the "Edit Orders" processing.
            //
            // $p1 ... The order's ID
            // $p2 ... A reference to the order-class object.
            // $p3 ... A reference to the $extra_buttons string, which is updated to include that edit button.
            //
            case 'NOTIFY_ADMIN_ORDERS_EDIT_BUTTONS':
                $p3 .= '&nbsp;' . $this->addEditOrderButton($p1);
                break;

            // -----
            // Issued during the order-totals' construction by the ot_shipping module, giving observers the chance
            // to override the shipping tax-related calculations.
            //
            // NOTE: The auto-loader has positioned the load of this class at 999, hopefully as the last watching observer
            // to load.  That allows this processing to 'assume' that it should provide that value if no other watcher
            // has intervened.
            //
            // $p1 ... n/a
            // $p2 ... A reference to the boolean flag that identifies whether/not the tax has been previously handled.
            // $p3 ... A reference to the module's $shipping_tax value.
            // $p4 ... A reference to the module's $shipping_tax_description string.
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
            case 'NOTIFY_OT_SHIPPING_TAX_CALCS':
                $GLOBALS['eo']->eoUpdateOrderShippingTax($p2, $p3, $p4);
                $p2 = true;
                $this->detach($this, ['NOTIFY_OT_SHIPPING_TAX_CALCS']);

                $module = (isset($_SESSION['shipping']['id'])) ? substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_')) : '';
                if ($module !== '' && $module !== 'free') {
                    require DIR_WS_CLASSES . 'EditOrdersOtShippingStub.php';
                    $GLOBALS[$module] = new EditOrdersOtShippingStub();
                }
                break;

            // -----
            // Issued at the end of an order's recreation (watched during EO processing only). Change introduced 
            // in zc156b now sets the order's shipping information to (bool)false when the order's shipping is 'storepickup'.
            //
            // If the order's shipping method *is* 'storepickup, we'll gather and restore the delivery-address
            // information for Edit Orders' display.
            //
            // On entry:
            //
            // $p2 ... Identifies the order-id.
            //
            case 'NOTIFY_ORDER_AFTER_QUERY':
                if (!($class->info['shipping_module_code'] === 'storepickup' && $class->delivery === false)) {
                    break;
                }
                $order = $GLOBALS['db']->Execute(
                    "SELECT *
                       FROM " . TABLE_ORDERS . "
                      WHERE orders_id = " . (int)$p2 . "
                      LIMIT 1"
                );
                if ($order->EOF) {
                    break;
                }
                $class->delivery = [
                    'name' => $order->fields['delivery_name'],
                    'company' => $order->fields['delivery_company'],
                    'street_address' => $order->fields['delivery_street_address'],
                    'suburb' => $order->fields['delivery_suburb'],
                    'city' => $order->fields['delivery_city'],
                    'postcode' => $order->fields['delivery_postcode'],
                    'state' => $order->fields['delivery_state'],
                    'country' => $order->fields['delivery_country'],
                    'format_id' => $order->fields['delivery_address_format_id']
                ];
                break;

            // -----
            // Added v4.7.0, replacing function override on EO page.
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
                } else {
                    $_SESSION['customer_id'] = $order->customer['id'];

                    if (STORE_PRODUCT_TAX_BASIS === 'Shipping') {
                        global $eo;
                        if ($eo->eoOrderIsVirtual($order)) {
                            if (is_array($order->billing['country'])) {
                                $customer_country_id = $order->billing['country']['id'];
                            } else {
                                $customer_country_id = $eo->getCountryId($order->billing['country']);
                            }
                            $customer_zone_id = $eo->getZoneId((int)$customer_country_id, $order->billing['state']);
                        } else {
                            if (is_array($order->delivery['country'])) {
                                $customer_country_id = $order->delivery['country']['id'];
                            } else {
                                $customer_country_id = $eo->getCountryId($order->delivery['country']);
                            }
                            $customer_zone_id = $eo->getZoneId((int)$customer_country_id, $order->delivery['state']);
                        }
                    } elseif (STORE_PRODUCT_TAX_BASIS === 'Billing') {
                        if (is_array ($order->billing['country'])) {
                            $customer_country_id = $order->billing['country']['id'];
                        } else {
                            $customer_country_id = $eo->getCountryId($order->billing['country']);
                        }
                        $customer_zone_id = $eo->getZoneId((int)$customer_country_id, $order->billing['state']);
                    }
                }
                $_SESSION['customer_country_id'] = $customer_country_id;
                $_SESSION['customer_zone_id'] = $customer_zone_id;

                $p2 = [
                    'zone_id' => $customer_zone_id,
                    'country_id' => $customer_country_id,
                ];
                break;

            default:
                break;
        }
    }

    protected function addEditOrderButton($orders_id)
    {
        return $this->createEditOrdersLink($orders_id, 'button', IMAGE_EDIT);
    }

    protected function createEditOrdersLink($orders_id, $link_button, $link_text)
    {
        if ($link_button !== null) {
            $link_parms = ' class="btn btn-primary" role="button"';
        } else {
            $link_parms = ' class="btn btn-default btn-sm btn-edit" role="button"';
        }
        return
            '<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['oID', 'action']) . 'action=edit&oID=' . $orders_id) . '"' . $link_parms . '>' .
                $link_text .
            '</a>';
    }
}
