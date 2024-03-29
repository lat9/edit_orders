<?php
// -----
// Language constants used by the /admin/edit_orders.php processing (Edit Orders).
//
//-Last modified Edit Orders v4.7.0.  Now using zc158+ language array
//
$define = [
// Page / Section Headings
    'HEADING_TITLE' => 'Editing Order',
    'HEADING_TITLE_SEARCH' => 'Order ID:',
    'HEADING_TITLE_STATUS' => 'Status:',
    'HEADING_TITLE_ADD_PRODUCT' => 'Adding a Product to Order',

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
    'TABLE_HEADING_UNIT_PRICE' => 'Unit Price',
    'TABLE_HEADING_UNIT_PRICE_NET' => 'Unit Price (Net)',
    'TABLE_HEADING_UNIT_PRICE_GROSS' => 'Unit Price (Gross)',
    'TABLE_HEADING_TOTAL_PRICE' => 'Total Price',
    'TABLE_HEADING_CUSTOMER_NOTIFIED' => 'Customer Notified',
    'TABLE_HEADING_DATE_ADDED' => 'Date Added',
    'TABLE_HEADING_UPDATED_BY' => 'Updated By',

// Order Address Entries
    'ENTRY_CUSTOMER' => 'Customer Address:',
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

// Order Payment Entries
    'ENTRY_PAYMENT_METHOD' => 'Payment Method:',
    'ENTRY_CREDIT_CARD_TYPE' => 'Credit Card Type:',
    'ENTRY_CREDIT_CARD_OWNER' => 'Credit Card Owner:',
    'ENTRY_CREDIT_CARD_NUMBER' => 'Credit Card Number:',
    'ENTRY_CREDIT_CARD_EXPIRES' => 'Credit Card Expires:',
    'ENTRY_UPDATE_TO_CC' => 'Enter <strong>Credit Card</strong> to view CC fields.',
    'ENTRY_UPDATE_TO_CK' => 'Enter the payment method used for this order to hide CC fields. (<strong>PayPal, Check/Money Order, Western Union, etc</strong>)',
    'ENTRY_PURCHASE_ORDER_NUMBER' => 'Purchase Order:',

// Order Status Entries
    'ENTRY_STATUS' => 'Status:',
    'ENTRY_CURRENT_STATUS' => 'Current Status: ',
    'ENTRY_NOTIFY_CUSTOMER' => 'Notify Customer:',
    'ENTRY_NOTIFY_COMMENTS' => 'Append Comments:',

// Email Entries
    'EMAIL_SEPARATOR' => '------------------------------------------------------',
    'EMAIL_TEXT_SUBJECT' => 'Order Update',
    'EMAIL_TEXT_ORDER_NUMBER' => 'Order Number:',
    'EMAIL_TEXT_INVOICE_URL' => 'Detailed Invoice:',
    'EMAIL_TEXT_DATE_ORDERED' => 'Date Ordered:',
    'EMAIL_TEXT_COMMENTS_UPDATE' => '<em>The comments for your order are: </em>',
    'EMAIL_TEXT_STATUS_UPDATED' => 'Your order has been updated to the following status:' . "\n",
    'EMAIL_TEXT_STATUS_LABEL' => '%s' . "\n\n",
    'EMAIL_TEXT_STATUS_PLEASE_REPLY' => 'Please reply to this email if you have any questions.' . "\n",

// Success, Warning, and Error Messages
    'ERROR_ORDER_DOES_NOT_EXIST' => 'Error: Order does not exist.',
    'SUCCESS_ORDER_UPDATED' => 'Success: Order has been successfully updated.',
    'WARNING_DISPLAY_PRICE_WITH_TAX' => 'Warning: You have configured Zen Cart to display prices with' . (DISPLAY_PRICE_WITH_TAX_ADMIN !== 'true' ? 'out ' : ' ') . 'tax. This page is currently displaying prices with' . (DISPLAY_PRICE_WITH_TAX !== 'true' ? 'out ' : ' ') . 'tax.',
    'WARNING_ADDRESS_COUNTRY_NOT_FOUND' => 'Warning: One or more of the customer address fields contains a country name unknown to Zen Cart (&quot;Locations / Taxes&quot;-&gt;&quot;Countries&quot;).<br />Taxes and some shipping modules may not function correctly until the issue has been resolved.<br /><br />This typically occurs if someone deletes or renames a country&#39;s name from Zen Cart (&quot;Locations / Taxes&quot;-&gt;&quot;Countries&quot;). You can fix the issue by doing one of the following: <ul><li>Add the country (and name) back to the Zen Cart database.</li><li>Adjust the country name to match one of the country names in the Zen Cart database.</li></ul>',
    'WARNING_ORDER_NOT_UPDATED' => 'Warning: Nothing to change. The order was not updated.',
    'WARNING_ORDER_QTY_OVER_MAX' => 'Warning: The quantity requested exceeded the maximum allowed for an order. The quantity added was reduced to the maximum allowed per order.',
    'WARNING_ORDER_COUPON_BAD' => 'Warning: The coupon code was not found in the database. Note: the title / text of a coupon is usually formatted like &quot;Discount Coupon : coupon_code :&quot;. ',
    'WARNING_INSUFFICIENT_PRODUCT_STOCK' => 'Insufficient stock for <em>%1$s</em>, requested %2$s with %3$s available.',

    'ERROR_ZEN_ADD_TAX_ROUNDING' => "The store's <code>zen_add_tax</code> function must be updated to enable <em>Edit Orders</em>' use.",

// Product & Attribute Display
    'TEXT_ATTRIBUTES_ONE_TIME_CHARGE' => 'One Time Charges: &nbsp;&nbsp;',
    'TEXT_ATTRIBUTES_UPLOAD_NONE' => 'No file was uploaded',

// Order Totals Display
    'TEXT_ADD_ORDER_TOTAL' => 'Add ',
    'TEXT_CHOOSE_SHIPPING_MODULE' => 'Choose a shipping module: ',
    'TEXT_COMMAND_TO_DELETE_CURRENT_COUPON_FROM_ORDER' => 'REMOVE',

// Add a Product
    'TEXT_ADD_NEW_PRODUCT' => 'Add Product',
    'ADDPRODUCT_TEXT_CATEGORY_CONFIRM' => 'OK',
    'ADDPRODUCT_TEXT_SELECT_PRODUCT' => 'Choose product',
    'ADDPRODUCT_TEXT_PRODUCT_CONFIRM' => 'OK',
    'ADDPRODUCT_TEXT_SELECT_OPTIONS' => 'Choose options',
    'ADDPRODUCT_TEXT_OPTIONS_CONFIRM' => 'OK',
    'ADDPRODUCT_TEXT_OPTIONS_NOTEXIST' => 'No Options: Skipped..',
    'ADDPRODUCT_TEXT_CONFIRM_QUANTITY' => '&nbsp;Qty&nbsp;',
    'ADDPRODUCT_TEXT_CONFIRM_ADDNOW' => 'Add now',
    'ADDPRODUCT_TEXT_STEP1' => 'Step 1:',
    'ADDPRODUCT_TEXT_STEP2' => 'Step 2:',
    'ADDPRODUCT_TEXT_STEP3' => 'Step 3:',
    'ADDPRODUCT_TEXT_STEP4' => 'Step 4:',
    'ADDPRODUCT_SPECIALS_SALES_PRICE' => 'Use Specials/Sales Price',
    'ADDPRODUCT_TEXT_NO_OPTIONS' => 'No selectable options exist',
    'ADDPRODUCT_CHOOSE_CATEGORY' => 'Choose Category',

// Navigation Display
    'IMAGE_ORDER_DETAILS' => 'Display Order Details',
    'BUTTON_TO_LIST' => 'Order List',
    'SELECT_ORDER_LIST' => 'Jump to Order:',

    'DETAILS' => 'Details',

// Required for various added zen_cart functions
    'TEXT_UNKNOWN_TAX_RATE' => 'Tax',
    'PULL_DOWN_DEFAULT' => 'Please Choose Your Country',

// Absolute's Product Attribute Grid
    'WARNING_ATTRIBUTE_OPTION_GRID' => 'Warning: Absolute\'s Product Attribute Grid was detected, however the Product Option Type for Attribute Grid was not fully installed in the database. Temporarily configuring PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID = 23997.',

// Other elements
    'RESET_TOTALS' => 'Reset totals prior to update? ',
    'PAYMENT_CALC_METHOD' => 'Choose product-pricing method:',
        'PAYMENT_CALC_MANUAL' => 'As entered',
        'PAYMENT_CALC_AUTO' => 'Automatically, without specials pricing',
        'PAYMENT_CALC_AUTOSPECIALS' => 'Automatically, using specials pricing',
    'PRODUCT_PRICES_CALC_AUTO' => ' <b>Note:</b> Pricing for products will be <em>automatically</em> calculated <em>without</em> &quot;specials&quot; pricing.',
    'PRODUCT_PRICES_CALC_AUTOSPECIALS' => ' <b>Note:</b> Pricing for products will be <em>automatically</em> calculated, using &quot;specials&quot; pricing.',
    'PRODUCT_PRICES_CALC_MANUAL' => ' <b>Note:</b> Pricing for products will use the value(s) that you enter.',
    'EO_PRICE_AUTO_GRID_MESSAGE' => 'Auto Calculated',

    'EO_MESSAGE_PRICING_AUTO' => 'Pricing was automatically calculated, without specials pricing.',
    'EO_MESSAGE_PRICING_AUTOSPECIALS' => 'Pricing was automatically calculated, using specials pricing.',
    'EO_MESSAGE_PRICING_MANUAL' => 'Pricing was supplied manually.',
    'EO_MESSAGE_ORDER_UPDATED' => 'The order was updated via "Edit Orders". ',
    'EO_MESSAGE_ADDRESS_UPDATED' => 'The order\'s %1$s address was updated from: ',   //-%1$s: The type of address (see below) that was updated
        'EO_CUSTOMER' => 'customer',
        'EO_BILLING' => 'billing',
        'EO_DELIVERY' => 'delivery',
    'EO_MESSAGE_PRODUCT_ADDED' => 'Added %1$s x "%2$s" to the order',   //-%1$s: The product quantity, %2$s: The product name
    'EO_MESSAGE_ATTRIBS_ADDED' => ', with options (%s)',

    'EO_SHIPPING_TAX_DESCRIPTION' => 'Shipping Tax (%s%%)',
    'EO_FREE_SHIPPING' => 'Free Shipping',
];

return $define;
