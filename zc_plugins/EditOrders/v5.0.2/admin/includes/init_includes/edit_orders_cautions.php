<?php
// -----
// This module checks to see if Edit Orders (admin/edit_orders.php) is being used to update an order with one (or more)
// of the following conditions:
//
// 1) A product in the order is currently disabled, its stock-quantity+order-quantity is a positive value and 
//    the store's configuration uses:
//    - Stock->Subtract Stock, set to 'true'.
//    - Stock->Show products when out of stock, set to '0'
//
//    Updating the order will cause the disabled product to be re-enabled.  This issue has been
//    around since at least v4.0.4.
//
// 2) A product in the order was deleted/removed from the shop after the order was placed.
//
//    Updating the order "might" result in the product's name being erased from the order.  Not clear when this issue
//    was introduced; v4.0.4 processes correctly.
//
// 3) An attributed product exists in the order, but a purchased attribute is no longer available on the shop (i.e. that
//    attribute has been deleted from the product).
//
//    Viewing that order will show a **different** attribute selected, since the purchased one doesn't exist
//    anymore, and updating the order will change the attribute from the one purchased without updating pricing.  This
//    issue has also been around since at least v4.0.4.
//
// If one of those conditions is detected, this module's processing informs the admin user via caution message that
// the order, once edited, might cause issues.
//
if (basename($_SERVER['PHP_SELF']) == 'edit_orders.php' && isset($_GET['oID'])) {
    $check_oid = (int)$_GET['oID'];
    
    $eo_op = $db->Execute(
        "SELECT orders_products_id, products_id, products_name, products_quantity
           FROM " . TABLE_ORDERS_PRODUCTS . "
          WHERE orders_id = $check_oid
       ORDER BY orders_products_id ASC"
    );
    while (!$eo_op->EOF) {
        $eoOpID = $eo_op->fields['orders_products_id'];
        $eoPID = $eo_op->fields['products_id'];
        $eo_product_name = $eo_op->fields['products_name'];
        $eo_product_qty = $eo_op->fields['products_quantity'];
        $eo_check = $db->Execute(
            "SELECT products_status, products_quantity
               FROM " . TABLE_PRODUCTS . "
              WHERE products_id = $eoPID
              LIMIT 1"
        );
        if ($eo_check->EOF) {
            $messageStack->add("Caution: The product ($eo_product_name) no longer exists in the store.  Updating the order <em>might</em> result in the product's name being removed from the order.");
        } else {
            if ($eo_check->fields['products_status'] == 0 && STOCK_LIMITED == 'true' && SHOW_PRODUCTS_SOLD_OUT == '0' && $eo_product_qty + $eo_check->fields['products_quantity'] > 0) {
                $messageStack->add("Caution: The product ($eo_product_name) is currently disabled.  Updating the order will re-enable that product.");
            }
            
            $eo_opa = $db->Execute(
                "SELECT products_options, products_options_values, products_options_id, products_options_values_id
                   FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                  WHERE orders_id = $check_oid
                    AND orders_products_id = $eoOpID
               ORDER BY orders_products_attributes_id ASC"
            );
            while (!$eo_opa->EOF) {
                $eo_attrib_check = $db->Execute(
                    "SELECT products_attributes_id
                       FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
                      WHERE products_id = $eoPID
                        AND options_id = {$eo_opa->fields['products_options_id']}
                        AND options_values_id = {$eo_opa->fields['products_options_values_id']}
                      LIMIT 1"
                );
                if ($eo_attrib_check->EOF) {
                    $messageStack->add("Caution: The attribute ({$eo_opa->fields['products_options']}: {$eo_opa->fields['products_options_values']}) no longer exists for the product ($eo_product_name).  Updating the order will <b>replace that attribute</b> in the order."); 
                }
                $eo_opa->MoveNext();
            }
        }
        $eo_op->MoveNext();
    }
}
