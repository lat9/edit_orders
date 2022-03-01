<?php
// -----
// Admin-level observer class, adds "Edit Orders" buttons and links to Customers->Orders processing.
// Copyright (C) 2017-2022, Vinos de Frutas Tropicales.
//
// Last updated for EO v4.6.1, lat9, 20220301
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class EditOrdersAdminObserver extends base 
{
    public function __construct() 
    {
        $zencart_version = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
        $this->is157OrLaterZenCart = ($zencart_version >= '1.5.7');
        $this->isEditOrdersPage = (basename($GLOBALS['PHP_SELF'], '.php') == FILENAME_EDIT_ORDERS);
        $this->attach(
            $this, 
            [
                /* From /admin/orders.php */
                'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS', 
                'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END',
                'NOTIFY_ADMIN_ORDERS_EDIT_BUTTONS',
                'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',    //-This is the zc156+ version of the above notification.
                
                /* From /includes/modules/order_total/ot_shipping.php */
                'NOTIFY_OT_SHIPPING_TAX_CALCS',
            ]
        );

        // -----
        // Starting with zc156, the order-class 'squishes' the delivery address to (bool)false when
        // the order's shipping-method is 'storepickup'.  Watch this event only during Edit Orders' processing!
        //
        if ($this->isEditOrdersPage) {
            $this->attach($this, ['NOTIFY_ORDER_AFTER_QUERY']);
        }
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5) 
    {
        switch ($eventID) {
            // -----
            // Issued during the orders-listing sidebar generation, after the upper button-list has been created.
            //
            // $p1 ... Contains the current $oInfo object, which contains the orders-id.
            // $p2 ... A reference to the current $contents array; the NEXT-TO-LAST element has been updated
            //         with the built-in button list.
            //
            case 'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS': 
                if (is_object($p1)) {
                    $index_to_update = count($p2) - 2;
                    $p2[$index_to_update]['text'] = $this->addEditOrderButton($p1->orders_id, $p2[$index_to_update]['text']);
                }
                break;

            // -----
            // Issued during the orders-listing sidebar generation, after the lower-button-list has been created.
            //
            // $p1 ... Contains the current $oInfo object (could be empty), containing the orders-id.
            // $p2 ... A reference to the current $contents array; the LAST element has been updated
            //         with the built-in button list.
            //
            case 'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END':
                if (is_object($p1) && count($p2) > 0 && isset($_GET['action']) && $_GET['action'] !== 'delete') {
                    $index_to_update = count($p2) - 1;
                    $p2[$index_to_update]['text'] = $this->addEditOrderButton($p1->orders_id, $p2[$index_to_update]['text']);
                }
                break;

            // -----
            // Issued during the orders-listing generation for each order, gives us a chance to add the icon to
            // quickly edit the associated order.
            //
            // $p1 ... An empty array
            // $p2 ... A reference to the current order's database fields array.
            // $p3 ... A reference to the $show_difference variable, unused by this processing.
            // $p4 ... A reference to the $extra_action_icons variable, which will be augmented with an icon
            //         linking to this order's EO processing.
            //
            case 'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE':
                $eo_icon = ($this->is157OrLaterZenCart) ? EO_ZC157_FA_ICON : EO_ZC156_FA_ICON;
                $p4 .= $this->createEditOrdersLink($p2['orders_id'], zen_image(DIR_WS_IMAGES . EO_BUTTON_ICON_DETAILS, EO_ICON_DETAILS), $eo_icon, false);
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
                $p3 .= '&nbsp;' . $this->createEditOrdersLink($p1, zen_image_button(EO_IMAGE_BUTTON_EDIT, IMAGE_EDIT), IMAGE_EDIT);
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
                if ($this->isEditOrdersPage) {
                    $GLOBALS['eo']->eoUpdateOrderShippingTax($p2, $p3, $p4);
                    $p2 = true;
                    $this->detach($this, ['NOTIFY_OT_SHIPPING_TAX_CALCS']);
                    
                    $module = (isset($_SESSION['shipping']) && isset($_SESSION['shipping']['id'])) ? substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_')) : '';
                    if ($module != '' && $module != 'free') {
                        require DIR_WS_CLASSES . 'EditOrdersOtShippingStub.php';
                        $GLOBALS[$module] = new EditOrdersOtShippingStub();
                    }
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
                if (!($class->info['shipping_module_code'] == 'storepickup' && $class->delivery === false)) {
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

            default:
                break;
        }
    }

    protected function addEditOrderButton($orders_id, $button_list)
    {
        $updated_button_list = str_replace(
            [
                EO_IMAGE_BUTTON_EDIT,
                IMAGE_EDIT,
            ],
            [
                EO_IMAGE_BUTTON_DETAILS,
                IMAGE_DETAILS
            ],
            $button_list
        );
        return $updated_button_list . '&nbsp;' . $this->createEditOrdersLink($orders_id, zen_image_button(EO_IMAGE_BUTTON_EDIT, IMAGE_EDIT), IMAGE_EDIT);
    }

    protected function createEditOrdersLink($orders_id, $link_button, $link_text, $include_zc156_parms = true)
    {
        $link_parms = '';
        if ($include_zc156_parms) {
            $link_parms = ' class="btn btn-primary" role="button"';
        } elseif ($this->is157OrLaterZenCart) {
            $link_parms = ' class="btn btn-default btn-edit"';
        }
        return '&nbsp;<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(['oID', 'action']) . "oID=$orders_id&action=edit", 'NONSSL') . "\"$link_parms>$link_text</a>";
    }
}
