<?php
// -----
// Language constants used by the /admin/edit_orders.php processing (Edit Orders).
//
// Last modified v5.0.0
//
$define = [
// Page / Section Headings and common button names and other constants.
    'BUTTON_ADD' => 'Add',
    'BUTTON_CHOOSE' => 'Choose',
    'BUTTON_CLOSE' => 'Close',
    'BUTTON_COMMIT_CHANGES' => 'Commit Changes',
    'BUTTON_RECALCULATE' => 'Recalculate',

    'HEADING_TITLE' => 'Editing Order',
    'HEADING_TITLE_SEARCH' => 'Order ID:',
    'HEADING_TITLE_STATUS' => 'Status:',
    'HEADING_TITLE_ADD_PRODUCT' => 'Adding a Product to Order',

    'TEXT_LABEL_TAX' => 'Tax (%):',
    'TEXT_ORDER_TOTAL_ADDED' => '%1$s was added: %2$s',
    'TEXT_ORDER_TOTAL_REMOVED' => '%1$s was removed: %2$s',
    'TEXT_ORIGINAL_ORDER' => 'Original Order',
    'TEXT_ORIGINAL_VALUE' => 'Original: <code>%s</code>',           //- Tooltip string
    'TEXT_OSH_CHANGED_VALUES' => 'These values were changed in the order:',
    'TEXT_OT_CHANGES' => 'Order Total Changes',
    'TEXT_PRODUCT_CHANGES' => 'Product Changes',
    'TEXT_UPDATED_ORDER' => 'Updated Order',
    'TEXT_VALUE_CHANGED' => '%1$s was changed from %2$s to %3$s',   //- Used by the AJAX processing and for OSH record
    'TEXT_VALUE_UNKNOWN' => 'Unknown [%s]',  //- %s is filled in with the unknown 'entity'

// Table Headings
    'TABLE_HEADING_STATUS_HISTORY' => 'Order Status History &amp; Comments',
    'TABLE_HEADING_COMMENTS' => 'Comments',
    'TABLE_HEADING_CUSTOMERS' => 'Customers',
    'TABLE_HEADING_ORDER_TOTAL' => 'Order Total',
    'TABLE_HEADING_DATE_PURCHASED' => 'Date Purchased',
    'TABLE_HEADING_STATUS' => 'Status',
    'TABLE_HEADING_ACTION' => 'Action',
    'TABLE_HEADING_QUANTITY' => 'Qty.',
    'TABLE_HEADING_PRODUCTS' => 'Products',
    'TABLE_HEADING_TAX' => 'Tax',
    'TABLE_HEADING_TOTAL' => 'Total',
    'TABLE_HEADING_UNIT_PRICE' => 'Unit Price', //- Also used for add/update product modals
    'TABLE_HEADING_UNIT_PRICE_NET' => 'Unit Price (Net)',   //- Also used for add/update product modals
    'TABLE_HEADING_UNIT_PRICE_GROSS' => 'Unit Price (Gross)',   //- Also used for add/update product modals
    'TABLE_HEADING_TOTAL_PRICE' => 'Total Price',
    'TABLE_HEADING_CUSTOMER_NOTIFIED' => 'Customer Notified',
    'TABLE_HEADING_DATE_ADDED' => 'Date Added',
    'TABLE_HEADING_UPDATED_BY' => 'Updated By',

// Order Address Entries
    'BUTTON_MAP_ADDRESS' => 'Map Address',

    'ENTRY_CUSTOMER' => 'Customer Information',
    'ENTRY_CUSTOMER_NAME' => 'Name',
    'ENTRY_CUSTOMER_COMPANY' => 'Company',
    'ENTRY_CUSTOMER_ADDRESS' => 'Address',
    'ENTRY_CUSTOMER_SUBURB' => 'Suburb',
    'ENTRY_CUSTOMER_CITY' => 'City',
    'ENTRY_CUSTOMER_STATE' => 'State',
    'ENTRY_CUSTOMER_POSTCODE' => 'Postcode',
    'ENTRY_CUSTOMER_COUNTRY' => 'Country',
    'ENTRY_SHIPPING_ADDRESS' => 'Shipping Address:',
    'ENTRY_BILLING_ADDRESS' => 'Billing Address:',
    'ENTRY_TELEPHONE_NUMBER' => 'Telephone:',       //- Shortening for modal-display; default is 'Telephone Number:'

    'TEXT_MODAL_ADDRESS_HEADER' => 'Modifying the Order\'s %s', //- %s is filled in with one the the 'xxx Address:' values, above
    'TEXT_STOREPICKUP_NO_SHIP_ADDR' => 'The order is to be picked up at the store, no shipping address.',
    'TEXT_VIRTUAL_NO_SHIP_ADDR' => 'The order contains only <code>virtual</code> products, no shipping address.',

    'PLEASE_SELECT' => 'Please select',
    'TYPE_BELOW' => 'Type a choice below ...',

// Order Payment Entries and Additional Infomation
    'ENTRY_CURRENCY_VALUE' => 'Currency Value (%s):',   //- %s is filled in with the order's currency-code
    'ENTRY_PAYMENT_METHOD' => 'Payment Method:',
    'ENTRY_CREDIT_CARD_TYPE' => 'Credit Card Type:',
    'ENTRY_CREDIT_CARD_OWNER' => 'Credit Card Owner:',
    'ENTRY_CREDIT_CARD_NUMBER' => 'Credit Card Number:',
    'ENTRY_CREDIT_CARD_EXPIRES' => 'Credit Card Expires:',
    'ENTRY_UPDATE_TO_CC' => 'Enter <strong>Credit Card</strong> to view CC fields.',
    'ENTRY_UPDATE_TO_CK' => 'Enter the payment method used for this order to hide CC fields. (<strong>PayPal, Check/Money Order, etc.</strong>)',
    'ENTRY_PURCHASE_ORDER_NUMBER' => 'Purchase Order:',
    'TEXT_CREDIT_CARD' => 'Credit Card',
    'TEXT_PANEL_HEADER_ADDL_INFO' => 'Additional Information',

// Order Status Entries
    'BUTTON_ADD_COMMENT' => 'New Comment',
    'BUTTON_ADD_COMMENT_ALT' => 'Add or Review a Comment for This Order',  //- Also used for the modal form's heading!
    'BUTTON_REMOVE' => 'Remove',
    'BUTTON_REVIEW_COMMENT' => 'Review Comment',

    'ENTRY_STATUS' => 'Status:',
    'ENTRY_CURRENT_STATUS' => 'Current Status: ',
    'ENTRY_NOTIFY_CUSTOMER' => 'Notify Customer:',
    'ENTRY_NOTIFY_COMMENTS' => 'Append Comments:',

    'TEXT_COMMENT_ADDED' => 'Comment for the Order',

// Email Entries
    'EMAIL_SEPARATOR' => '------------------------------------------------------',

    'EMAIL_TEXT_COMMENTS_UPDATE' => '<em>The comments for your order are: </em>',
    'EMAIL_TEXT_DATE_ORDERED' => 'Date Ordered:',
    'EMAIL_TEXT_INVOICE_URL' => 'Detailed Invoice:',
    'EMAIL_TEXT_ORDER_NUMBER' => 'Order Number:',
    'EMAIL_TEXT_STATUS_LABEL' => '%s' . "\n\n",
    'EMAIL_TEXT_STATUS_PLEASE_REPLY' => 'Please reply to this email if you have any questions.' . "\n",
    'EMAIL_TEXT_STATUS_UPDATED' => 'Your order has been updated to the following status:' . "\n",
    'EMAIL_TEXT_SUBJECT' => 'Order Update',

// Success, Warning, and Error Messages
    'ERROR_ADDRESS_COUNTRY_NOT_FOUND' => 'Order #%u cannot be edited. One or more of the addresses in the order uses a country unknown to your store; taxes and some shipping modules will likely not function correctly until the issue has been resolved.<br><br>This typically occurs if an admin deletes, renames or disables a country using the <b>Locations / Taxes :: Countries</b> tool. The issue can be corrected by doing one of the following:<ul><li>Add the country (and name) back to your Zen Cart database.</li><li>Re-enable the country temporarily to enable the order to be edited.</li></ul>',
    'ERROR_CANT_DETERMINE_TAX_RATES' => 'Order #%u cannot be edited, since its tax-rates cannot be determined.',
    'ERROR_DISPLAY_PRICE_WITH_TAX' => 'You have configured Zen Cart to display prices with' . (DISPLAY_PRICE_WITH_TAX_ADMIN !== 'true' ? 'out ' : ' ') . 'tax. This page is currently displaying prices with' . (DISPLAY_PRICE_WITH_TAX !== 'true' ? 'out ' : ' ') . 'tax. Orders cannot be edited until the two settings are the same.',
    'ERROR_NO_PRODUCT_TAX_DESCRIPTION' => 'Order #%1$u cannot be edited. No tax description could be found for <em>%2$s</em> tax-rate (%3$s%%).',
    'ERROR_NO_SHIPPING_TAX_DESCRIPTION' => 'Order #%1$u cannot be edited. No tax description could be found for the shipping tax-rate (%2$s%%).',
    'ERROR_ORDER_DOES_NOT_EXIST' => 'The order does not exist.',
    'ERROR_PRODUCT_ATTRIBUTE_DOES_NOT_EXIST' => 'Order #%1$u cannot be edited. The option name/value (%2$s [#%3$u]/%4$s [#%5$u]) no longer applies to product (%6$s [#%7$u]).',
    'ERROR_PRODUCT_DOES_NOT_EXIST' => 'Order #%1$u cannot be edited. The product (%2$s [#%3$u]) no longer exists.',
    'ERROR_SHIPPING_TAX_RATE_MISSING' => 'Order #%u cannot be edited. Its <code>shipping_tax_rate</code> was not previously recorded.',
    'ERROR_ZEN_ADD_TAX_ROUNDING' => "The store's <code>zen_add_tax</code> function must be updated to enable <em>Edit Orders</em>' use.",

    'SUCCESS_ORDER_UPDATED' => 'Order #%u has been successfully updated.',

    'WARNING_INSUFFICIENT_PRODUCT_STOCK' => 'Insufficient stock for <em>%1$s</em>, requested %2$s with %3$s available.',
    'WARNING_NO_UPDATES_TO_ORDER' => 'Nothing to update; no changes to this order were recorded.',
    'WARNING_ORDER_COUPON_BAD' => 'Warning: The coupon code (%s) for the order is no longer valid. Updating the order will remove any deductions associated with that coupon!',
    'WARNING_ORDER_NOT_UPDATED' => 'Warning: Nothing to change. The order was not updated.',
    'WARNING_ORDER_QTY_OVER_MAX' => 'Warning: The quantity requested exceeded the maximum allowed for an order. The quantity added was reduced to the maximum allowed per order.',

// Order Totals Display
    'ERROR_OT_NOT_INSTALLED' => 'The order-total selected (%s) is not installed and cannot be updated.',

    'TEXT_CHOOSE_SHIPPING_MODULE' => 'Choose a shipping module: ',
    'TEXT_COMMAND_TO_DELETE_CURRENT_COUPON_FROM_ORDER' => 'REMOVE',     //- ALWAYS uppercased!
    'TEXT_COUPON_LINK_TITLE' => 'see the Coupon conditions',
    'TEXT_LABEL_COUPON_CODE' => 'Coupon Code:',
    'TEXT_LABEL_METHOD' => 'Method:',
    'TEXT_LABEL_MODULE' => 'Module:',
    'TEXT_LABEL_TITLE' => 'Title:',
    'TEXT_LABEL_VALUE' => 'Value:',
    'TEXT_OT_ADD_MODAL_TITLE' => 'Add Order Total (%s)',
    'TEXT_OT_UPDATE_MODAL_TITLE' => 'Editing Order Total (%s)',    //- %s is filled in with the order-total's class, e.g. ot_shipping

// Adding/updating a product
    'ERROR_PRODUCT_NOT_FOUND' => 'The requested product (%s) is not present in the order.',
    'ERROR_MODEL_TOO_LONG' => 'The &quot;model&quot; value must not be longer than %u characters.',
    'ERROR_NAME_TOO_LONG' => 'The &quot;name&quot; value must not be longer than %u characters.',
    'ERROR_NO_MATCHING_PRODUCT' => 'No product was found that matches your request; please try again.',
    'ERROR_PRICE_INVALID' => 'The product price and/or onetime charges must be numeric and greater than or equal to zero.',
    'ERROR_QTY_INSUFFICIENT' => 'Insufficient product quantity (%s) is available.',
    'ERROR_QTY_INVALID' => 'The product quantity must be numeric and greater than or equal to zero.',
    'ERROR_TAX_RATE_INVALID' => 'The tax-rate for the product must be a numeric value between 0 and 100.',

    'TEXT_ADD_NEW_PRODUCT' => 'Add Product',        //- Used for button text
    'TEXT_ATTRIBUTES_ONE_TIME_CHARGE' => 'One Time Charges:',
    'TEXT_ATTRIBUTES_READONLY' => ' (r/o)',
    'TEXT_ATTRIBUTES_UNKNOWN_OPTION_TYPE' => 'Unknown option type (%u)',

    'TEXT_FILE_UPLOAD_NOT_SUPPORTED' => 'FILE UPLOAD NOT SUPPORTED',

    'TEXT_LABEL_NAME' => 'Name:',
    'TEXT_LABEL_MODEL' => 'Model:',
    'TEXT_LABEL_QTY_AVAIL' => 'Qty avail:',
    'TEXT_LABEL_QTY' => 'Qty:',

    'TEXT_PRODUCT_ADD_MODAL_TITLE' => 'Add a Product to the Order',
    'TEXT_PRODUCT_ATTRIBUTES' => 'Product Attributes',
    'TEXT_PRODUCT_BEING_ADDED' => 'The product is being added to the order.',
    'TEXT_PRODUCT_CHOOSE_BY_CATEGORY' => 'Choose by Category',
    'TEXT_PRODUCT_CHOOSE_BY_ID' => 'Choose by Product ID',
    'TEXT_PRODUCT_CHOOSE_BY_SEARCH' => 'Choose by Product Name/Model Search',
    'TEXT_PRODUCT_CHOOSE_SUBTITLE' => 'Choose Product',
    'TEXT_PRODUCT_NEW_MODAL_TITLE' => 'New Product',
    'TEXT_PRODUCT_NEW_SELECT_CHOOSE' => 'Select a product from the list below, then click the &quot;Choose&quot; button.',
    'TEXT_PRODUCT_UPDATE_MODAL_TITLE' => 'Updating a Product',

    'TEXT_SELECT_PRODUCT' => 'Select Product:',

    //- These three constants define the message to be recorded for products' changes. All 3
    //  use the same sprintf values:
    //
    // %1$s (qty), %2$s (name), %3$s (model), %4$s (final price), %5$s (tax rate)
    //
    'TEXT_STATUS_PRODUCT_ADDED' => 'Added: %1$s x %2$s [%3$s] @ %4$s (tax-rate %5$s%%)',
    'TEXT_STATUS_PRODUCT_CHANGED' => 'Some of the product details were changed: %1$s x %2$s [%3$s] @ %4$s (tax-rate %5$s%%)',
    'TEXT_STATUS_PRODUCT_REMOVED' => 'Removed: %1$s x %2$s [%3$s] @ %4$s (tax-rate %5$s%%)',

// Navigation Display
    'BUTTON_TO_LIST' => 'Order List',
    'DETAILS' => 'Details',
    'IMAGE_ORDER_DETAILS' => 'Display Order Details',
    'SELECT_ORDER_LIST' => 'Jump to Order:',

// Required for various added zen_cart functions
    'PULL_DOWN_DEFAULT' => 'Please Choose Your Country',

    'TEXT_UNKNOWN_TAX_RATE_MANUAL' => 'Sales Tax (%s%%)',
    'TEXT_UNKNOWN_TAX_RATE' => 'Sales Tax',

// Other elements
    'PAYMENT_CALC_METHOD' => 'Choose product-pricing method:',
        'PAYMENT_CALC_MANUAL' => 'Enable editing',
        'PAYMENT_CALC_AUTOSPECIALS' => 'Editing disallowed',
    'PRODUCT_PRICES_CALC_AUTOSPECIALS' => ' <b>Note:</b> Product prices are <em>automatically</em> calculated and cannot be edited.',
    'PRODUCT_PRICES_CALC_MANUAL' => ' <b>Note:</b> Product prices can be edited.',

    'EO_MESSAGE_ADDRESS_UPDATED' => 'The order\'s %1$s address was updated from: ',   //-%1$s: The type of address (see below) that was updated
        'EO_CUSTOMER' => 'customer',
        'EO_BILLING' => 'billing',
        'EO_DELIVERY' => 'delivery',
        'EO_MESSAGE_ORDER_UPDATED' => 'The order was updated via "Edit Orders". ',
    'EO_MESSAGE_PRICING_AUTO' => 'Pricing was automatically calculated, without specials pricing.',
    'EO_MESSAGE_PRICING_AUTOSPECIALS' => 'Pricing was automatically calculated, using specials pricing.',
    'EO_MESSAGE_PRICING_MANUAL' => 'Pricing was supplied manually.',

    'EO_MESSAGE_PRODUCT_ADDED' => 'Added %1$s x "%2$s" to the order',   //-%1$s: The product quantity, %2$s: The product name
    'EO_MESSAGE_PRODUCT_ATTRIBS_ADDED' => ', with options (%s)',

    'TEXT_PANEL_HEADER_UPDATE_INFO' => 'Order-Update Information',
];
return $define;
