<?php
// -----
// Admin-level observer class, adds "Edit Orders" buttons and links to Customers->Orders processing.
// Copyright (C) 2017-2019, Vinos de Frutas Tropicales.
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class EditOrdersAdminObserver extends base 
{
    public function __construct() 
    {
        $this->isPre156ZenCart = (PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR < '1.5.6');
        $this->attach(
            $this, 
            array(
                'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS', 
                'NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END',
                'NOTIFY_ADMIN_ORDERS_EDIT_BUTTONS',
                'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',    //-This is the zc156+ version of the above notification.
            )
        );
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4) 
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
                if (is_object($p1)) {
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
                $zc156_icon = '<i class="fa fa-pencil fa-lg" title="' . EO_ICON_DETAILS . '"></i>';
                $p4 .= $this->createEditOrdersLink($p2['orders_id'], zen_image(DIR_WS_IMAGES . EO_BUTTON_ICON_DETAILS, EO_ICON_DETAILS), $zc156_icon);
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
                
            default:
                break;
        }
    }
    
    protected function addEditOrderButton($orders_id, $button_list)
    {
        $updated_button_list = str_replace(
            array(
                EO_IMAGE_BUTTON_EDIT,
                IMAGE_EDIT,
            ),
            array(
                EO_IMAGE_BUTTON_DETAILS,
                IMAGE_DETAILS
            ),
            $button_list
        );
        return $updated_button_list . '&nbsp;' . $this->createEditOrdersLink($orders_id, zen_image_button(EO_IMAGE_BUTTON_EDIT, IMAGE_EDIT), IMAGE_EDIT);
    }

    protected function createEditOrdersLink($orders_id, $link_button, $link_text)
    {
        if ($this->isPre156ZenCart) {
            $anchor_text = $link_button;
            $link_parms = '';
        } else {
            $anchor_text = $link_text;
            $link_parms = ' class="btn btn-primary" role="button"';
        }
        return '&nbsp;<a href="' . zen_href_link(FILENAME_EDIT_ORDERS, zen_get_all_get_params(array('oID', 'action')) . "oID=$orders_id&action=edit", 'NONSSL') . "\"$link_parms>$anchor_text</a>";
    }
}