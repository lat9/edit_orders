<?php
// -----
// Language constants used by the /admin/edit_orders.php processing (Edit Orders).
//
// Page / Section Headings
define('HEADING_TITLE', 'Editing Order');
define('HEADING_TITLE_SEARCH', 'Order ID:');
define('HEADING_TITLE_STATUS', 'Status:');
define('HEADING_TITLE_ADD_PRODUCT', 'Adding a Product to Order');

// Table Headings
define('TABLE_HEADING_STATUS_HISTORY', 'Order Status History &amp; Comments');
define('TABLE_HEADING_COMMENTS', 'Comments');
define('TABLE_HEADING_CUSTOMERS', 'Customers');
define('TABLE_HEADING_ORDER_TOTAL', 'Order Total');
define('TABLE_HEADING_DATE_PURCHASED', 'Date Purchased');
define('TABLE_HEADING_STATUS', 'Status');
define('TABLE_HEADING_ACTION', 'Action');
define('TABLE_HEADING_QUANTITY', 'Qty.');
define('TABLE_HEADING_PRODUCTS_MODEL', 'Model');
define('TABLE_HEADING_PRODUCTS', 'Products');
define('TABLE_HEADING_TAX', 'Tax');
define('TABLE_HEADING_TOTAL', 'Total');
define('TABLE_HEADING_UNIT_PRICE', 'Unit Price');
define('TABLE_HEADING_UNIT_PRICE_NET', 'Unit Price (Net)');
define('TABLE_HEADING_UNIT_PRICE_GROSS', 'Unit Price (Gross)');
define('TABLE_HEADING_TOTAL_PRICE', 'Total Price');
define('TABLE_HEADING_CUSTOMER_NOTIFIED', 'Customer Notified');
define('TABLE_HEADING_DATE_ADDED', 'Date Added');
define('TABLE_HEADING_UPDATED_BY', 'Updated By');

// Order Address Entries
define('ENTRY_CUSTOMER', 'Customer Address:');
define('ENTRY_CUSTOMER_NAME', 'Name');
define('ENTRY_CUSTOMER_COMPANY', 'Company');
define('ENTRY_CUSTOMER_ADDRESS', 'Address');
define('ENTRY_CUSTOMER_SUBURB', 'Suburb');
define('ENTRY_CUSTOMER_CITY', 'City');
define('ENTRY_CUSTOMER_STATE', 'State');
define('ENTRY_CUSTOMER_POSTCODE', 'Postcode');
define('ENTRY_CUSTOMER_COUNTRY', 'Country');
define('ENTRY_SHIPPING_ADDRESS', 'Shipping Address:');
define('ENTRY_BILLING_ADDRESS', 'Billing Address:');

// Order Payment Entries
define('ENTRY_PAYMENT_METHOD', 'Payment Method:');
define('ENTRY_CREDIT_CARD_TYPE', 'Credit Card Type:');
define('ENTRY_CREDIT_CARD_OWNER', 'Credit Card Owner:');
define('ENTRY_CREDIT_CARD_NUMBER', 'Credit Card Number:');
define('ENTRY_CREDIT_CARD_EXPIRES', 'Credit Card Expires:');
define('ENTRY_UPDATE_TO_CC', 'Enter <strong>Credit Card</strong> to view CC fields.');
define('ENTRY_UPDATE_TO_CK', 'Enter the payment method used for this order to hide CC fields. (<strong>PayPal, Check/Money Order, Western Union, etc</strong>)');
define('ENTRY_PURCHASE_ORDER_NUMBER', 'Purchase Order:');

// Order Status Entries
define('ENTRY_STATUS', 'Status:');
define('ENTRY_CURRENT_STATUS', 'Current Status: ');
define('ENTRY_NOTIFY_CUSTOMER', 'Notify Customer:');
define('ENTRY_NOTIFY_COMMENTS', 'Append Comments:');

// Email Entries
define('EMAIL_SEPARATOR', '------------------------------------------------------');
define('EMAIL_TEXT_SUBJECT', 'Order Update');
define('EMAIL_TEXT_ORDER_NUMBER', 'Order Number:');
define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice:');
define('EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:');
define('EMAIL_TEXT_COMMENTS_UPDATE', '<em>The comments for your order are: </em>');
define('EMAIL_TEXT_COMMENTS_TRACKING_UPDATE', '<em>Items from your order will be shipping soon!</em>');
define('EMAIL_TEXT_STATUS_UPDATED', 'Your order has been updated to the following status:' . "\n");
define('EMAIL_TEXT_STATUS_LABEL', '%s' . "\n\n");
define('EMAIL_TEXT_STATUS_PLEASE_REPLY', 'Please reply to this email if you have any questions.' . "\n");

// Success, Warning, and Error Messages
define('ERROR_ORDER_DOES_NOT_EXIST', 'Error: Order does not exist.');
define('SUCCESS_ORDER_UPDATED', 'Success: Order has been successfully updated.');
define('WARNING_DISPLAY_PRICE_WITH_TAX', 'Warning: You have configured Zen Cart to display prices with' . (DISPLAY_PRICE_WITH_TAX_ADMIN != 'true' ? 'out ' : ' ') . 'tax. This page is currently displaying prices with' . (DISPLAY_PRICE_WITH_TAX != 'true' ? 'out ' : ' ') . 'tax.');
define('WARNING_ADDRESS_COUNTRY_NOT_FOUND', 'Warning: One or more of the customer address fields contains a country name unknown to Zen Cart (&quot;Locations / Taxes&quot;-&gt;&quot;Countries&quot;).<br />Taxes and some shipping modules may not function correctly until the issue has been resolved.<br /><br />This typically occurs if someone deletes or renames a country&#39;s name from Zen Cart (&quot;Locations / Taxes&quot;-&gt;&quot;Countries&quot;). You can fix the issue by doing one of the following: <ul><li>Add the country (and name) back to the Zen Cart database.</li><li>Adjust the country name to match one of the country names in the Zen Cart database.</li></ul>');
define('WARNING_ORDER_NOT_UPDATED', 'Warning: Nothing to change. The order was not updated.');
define('WARNING_ORDER_QTY_OVER_MAX', 'Warning: The quantity requested exceeded the maximum allowed for an order. The quantity added was reduced to the maximum allowed per order.');
define('WARNING_ORDER_COUPON_BAD', 'Warning: The coupon code was not found in the database. Note: the title / text of a coupon is usually formatted like &quot;Discount Coupon : coupon_code :&quot;. ');
define('WARNING_INSUFFICIENT_PRODUCT_STOCK', 'Insufficient stock for <em>%1$s</em>, requested %2$s with %3$s available.');

define('ERROR_ZEN_ADD_TAX_ROUNDING', "The store's <code>zen_add_tax</code> function must be updated to enable <em>Edit Orders</em>' use.");

define ('ERROR_ZC155_NO_SANITIZER', 'You must install the Zen Cart 1.5.5 <em>AdminRequestSanitizer</em> class before you can use Edit Orders on this site.');
// Product & Attribute Display
define('TEXT_ATTRIBUTES_ONE_TIME_CHARGE', 'One Time Charges: &nbsp;&nbsp;');
define('TEXT_ATTRIBUTES_UPLOAD_NONE', 'No file was uploaded');

// Order Totals Display
define('TEXT_ADD_ORDER_TOTAL', 'Add ');
define('TEXT_CHOOSE_SHIPPING_MODULE', 'Choose a shipping module: ');
if (!defined('TEXT_COMMAND_TO_DELETE_CURRENT_COUPON_FROM_ORDER')) define('TEXT_COMMAND_TO_DELETE_CURRENT_COUPON_FROM_ORDER', 'REMOVE');

// Add a Product
define('TEXT_ADD_NEW_PRODUCT', 'Add Product');
define('ADDPRODUCT_TEXT_CATEGORY_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_SELECT_PRODUCT', 'Choose product');
define('ADDPRODUCT_TEXT_PRODUCT_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_SELECT_OPTIONS', 'Choose options');
define('ADDPRODUCT_TEXT_OPTIONS_CONFIRM', 'OK');
define('ADDPRODUCT_TEXT_OPTIONS_NOTEXIST', 'No Options: Skipped..');
define('ADDPRODUCT_TEXT_CONFIRM_QUANTITY', '&nbsp;Qty&nbsp;');
define('ADDPRODUCT_TEXT_CONFIRM_ADDNOW', 'Add now');
define('ADDPRODUCT_TEXT_STEP1', 'Step 1:');
define('ADDPRODUCT_TEXT_STEP2', 'Step 2:');
define('ADDPRODUCT_TEXT_STEP3', 'Step 3:');
define('ADDPRODUCT_TEXT_STEP4', 'Step 4:');
define('ADDPRODUCT_SPECIALS_SALES_PRICE', 'Use Specials/Sales Price');
define('ADDPRODUCT_TEXT_NO_OPTIONS', 'No selectable options exist');
define('ADDPRODUCT_CHOOSE_CATEGORY', 'Choose Category');

// Navigation Display
define('IMAGE_ORDER_DETAILS', 'Display Order Details');
define('BUTTON_TO_LIST', 'Order List');
define('SELECT_ORDER_LIST', 'Jump to Order:');

// Required for various added zen_cart functions
define('TEXT_UNKNOWN_TAX_RATE', 'Tax');
define('PULL_DOWN_DEFAULT', 'Please Choose Your Country');

// Ty Package Tracker
define('TABLE_HEADING_TRACKING_ID', 'Tracking ID');
define('TABLE_HEADING_CARRIER_NAME', 'Carrier');
define('ENTRY_ADD_TRACK', 'Add Tracking ID');
define('IMAGE_TRACK', 'Add Tracking ID');
define('HEADING_TITLE_ORDER_DETAILS', 'Order # ');

// Absolute's Product Attribute Grid
define('WARNING_ATTRIBUTE_OPTION_GRID', 'Warning: Absolute\'s Product Attribute Grid was detected, however the Product Option Type for Attribute Grid was not fully installed in the database. Temporarily configuring PRODUCTS_OPTIONS_TYPE_ATTRIBUTE_GRID = 23997.');

// Other elements
define('RESET_TOTALS', 'Reset totals prior to update? ');
define('PAYMENT_CALC_METHOD', 'Choose product-pricing method:');
    define('PAYMENT_CALC_MANUAL', 'As entered');
    define('PAYMENT_CALC_AUTO', 'Automatically, without specials pricing');
    define('PAYMENT_CALC_AUTOSPECIALS', 'Automatically, using specials pricing');
define('PRODUCT_PRICES_CALC_AUTO', ' <b>Note:</b> Pricing for products will be <em>automatically</em> calculated <em>without</em> &quot;specials&quot; pricing.');
define('PRODUCT_PRICES_CALC_AUTOSPECIALS', ' <b>Note:</b> Pricing for products will be <em>automatically</em> calculated, using &quot;specials&quot; pricing.');
define('PRODUCT_PRICES_CALC_MANUAL', ' <b>Note:</b> Pricing for products will use the value(s) that you enter.');

define('EO_MESSAGE_PRICING_AUTO', 'Pricing was automatically calculated, without specials pricing.');
define('EO_MESSAGE_PRICING_AUTOSPECIALS', 'Pricing was automatically calculated, using specials pricing.');
define('EO_MESSAGE_PRICING_MANUAL', 'Pricing was supplied manually.');
define('EO_MESSAGE_ORDER_UPDATED', 'The order was updated via "Edit Orders". ');
define('EO_MESSAGE_PRODUCT_ADDED', 'Added %1$s x "%2$s" to the order');   //-%1$s: The product quantity, %2$s: The product name
define('EO_MESSAGE_ATTRIBS_ADDED', ', with options (%s)');

define('EO_SHIPPING_TAX_DESCRIPTION', 'Shipping Tax (%s%%)');
define('EO_FREE_SHIPPING', 'Free Shipping');