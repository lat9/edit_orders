# Notifications Issued by Edit Orders

_Edit Orders_ issues various notifications to enable site-specific (or other plugins) to seamlessly integrate with _EO_ without modifications.

## Issued by `edit_orders.php`

These notifications are issued in `global` scope via `$zco_notifier`.

| Notifier | Description |
| ----- |  ----- |
| [EDIT_ORDERS_START_ACTION_PROCESSING](#start-action-processing) | Issued just prior to the start of the _EO_ `action` processing. |
| [EDIT_ORDERS_PRE_UPDATE_ORDER](#pre-update-order) | Issued during the `update_order` processing, just prior to updating the base order database record. |
| [EDIT_ORDERS_PRE_UPDATE_PRODUCTS](#pre-update-products) | Issued during the `update_order` processing, just prior to the loop that adds/updates each product in the order. |
| [EDIT_ORDER_ORDER_UPDATED_SUCCESS](#order-updated-success) | Issued during the `update_order` processing, when the order has been successfully updated. |
| [EDIT_ORDERS_ORDER_UPDATED](#order-updated) | Issued at the end of the `update_order` processing, just prior to the redirect. |
| [EDIT_ORDERS_START_ADD_PRODUCT](#start-add-product) | Issued just prior to the start of the `add_prdct` processing, after the product's quantity and attributes have been selected. |
| [EDIT_ORDERS_PRODUCT_ADDED](#product-added) | Issued near the end of the `add_prdct` processing, just prior to the order's status-history update and the redirect. |
| [EDIT_ORDERS_ADDITIONAL_ADDRESS_ROWS](#additional-address-rows) | Issued at the end of the rendering of the order's address block. |
| [EDIT_ORDERS_ADDITIONAL_CONTACT_INFORMATION](#additional-contact-information) | Issued after rendering the 'base' order's contact information. |
| [EDIT_ORDERS_FORM_ADDITIONAL_INPUTS](#form-additional-inputs) | Issued just prior to rendering the `update` button, allowing additional form inputs to be supplied. |
| [EDIT_ORDERS_PRODUCTS_HEADING_1](#products-heading-1) | Issued just prior to rendering the first heading-column of the order's products' listing. |
| [EDIT_ORDERS_PRODUCTS_DATA_1](#products-data-1) | Issued just prior to rendering the first data-column of the order's products' listing, once for each product in the order. |
| [EDIT_ORDERS_DISPLAY_ONLY_TOTALS](#display-only-totals) | Issued just prior to rendering the order-totals' section. |
| [EDIT_ORDERS_STATUS_DISPLAY_ARRAY_INIT](#status-display-array-init) | Issued just prior to rendering the order's status-history table. |
| [EDIT_ORDERS_ADDITIONAL_OSH_CONTENT](#additional-osh-content) | Issued just after rendering the **Comments** input field. |
| [EDIT_ORDERS_ADDITIONAL_JS](#additional_js) | Issued at the end of the `edit_orders` page's rendering, just prior to the footer. |

## Issued by `edit_orders_functions.php`

Content coming soon!

## Issued by `editOrders.php`

Content coming soon!

## Detailed Descriptions
The sections below document the variables supplied by each notification.

-----

### Issued by `edit_orders.php`


#### Start Action Processing

This notifier fires at the very beginning of _EO_'s processing, just prior to its start of `action` processing.

A watching observer can inspect the contents of the `$_GET` and `$_POST` arrays to determine what to do next.

#### Pre-Update Order

This notifier fires during `update_order` processing, just prior to writing the updates to the order's base database record, in the `orders` table.  The observer has the opportunity to

1. Make updates to the to-be-written `orders` table data.
2. Deny the update.  If denied, it's the observer's responsibility to issue any `messageStack` message identifying why the update was denied.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Identifies the `orders_id` of the order being edited. |
| $p2 | (r/w) Contains a reference to the 'simple' `$sql_data_array` to be written via `zen_db_perform` to the store's `orders` table. |
| $p3 | (r/w) Contains a reference to the (boolean) `$allow_update` flag, initially set to `true`.  The observer sets this value to `(bool)false` if the order-update should be disallowed. |

#### Pre-Update Products

This notifier fires during `update_order` processing, just prior to the loop that adds each product to the order.  The observer can inspect and/or manipulate the `$_POST` data.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Identifies the `orders_id` of the order being edited. |

#### Order Updated Success

This notifier fires at the end of `update_order` processing, _if and only if_ the order was successfully updated.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Identifies the `orders_id` of the order being edited. |

#### Order Updated

This notifier **unconditionally** fires at the very end of `update_order` processing, just prior to the redirect.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the order-object that represents the just-updated order. |

#### Start Add Product

This notifier fires at the beginning of the section that adds a product to the order, after gathering the quantity and any attributes.  The observer can inspect and/or modify the contents of the associated `$_POST` data.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the `orders_id` of the order to which the product is being added. |

#### Product Added

This notifier fires near the end of `add_prdct` processing, just prior to the status-history update and redirect.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the order-object that represents the just-updated order. |

#### Additional Address Rows

This notifier fires after the 'base' address rows are rendered, allowing an observer to supply additional rows associated with the three address elements (customer, billing, shipping).  I'm not proud of this one, and it's on the `deprecation` list!

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the order-object that represents the order. |
| $p2 | (r/w) Contains a reference to the `$additional_rows` string, which is _directly output_ to the screen. |

#### Additional Contact Information

This notifier fires after the 'base' contact information, e.g. telephone, email address, has been rendered, giving an observer the opportunity to supply additional data for the output.

A watching observer appends its additions to the second parameter (`$p2`, below) as arrays formatted as

```
array(
    'label' => label_text,
    'content' => the entry's content
)
```

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the order-object that represents the order. |
| $p2 | (r/w) Contains a reference to the `$additional_content_info` array, formatted as described above.  The value is initialized to an empty array. |

#### Form Additional Inputs

This notifier fires just prior to rendering the `update` button, enabling additional upper-form inputs to be supplied.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the order-object that represents the order. |
| $p2 | (r/w) Contains a reference to the `$additional_inputs` string, to which an observer can _append_ its additional inputs.  The value is initialized as an empty string. |

#### Products Heading 1

This notifier fires just prior to rendering the _first_ products' table heading, allowing an observer to insert the heading for 'leading' columns.

A watching observer appends its additions to the second parameter (`$p2`, below) as arrays formatted as

```
array(
    'align' => $alignment,  // One of 'center', 'right' or 'left' (optional)
    'text' => the heading text string
)
```

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (n/a) |
| $p2 | (r/w) Contains a reference to the `$extra_headings` array, formatted as described above.  The value is initialized to `(bool)false`.  Observers should check that the `$p2` value is specifically `(bool)false` before initializing, since multiple observers might be injecting content! |

#### Products Data 1

This notifier fires just prior to rendering the _first_ products' table data, once per product in the order, allowing an observer to insert the data for 'leading' columns.

A watching observer appends its additions to the second parameter (`$p2`, below) as arrays formatted as

```
array(
    'align' => $alignment,  // One of 'center', 'right' or 'left' (optional)
    'text' => the data's text string
)
```

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) An associative array that contains the `orders_products` values for the current product. |
| $p2 | (r/w) Contains a reference to the `$extra_data` array, formatted as described above.  The value is initialized to `(bool)false`.  Observers should check that the `$p2` value is specifically `(bool)false` before initializing, since multiple observers might be injecting content! |

#### Display Only Totals

This notifier fires just prior to rendering the order's totals' block, enabling an observer to indicate that there are additional order-totals that should be considered _display-only_.  The observer returns a comma-separated string of order-total module names, e.g. `ot_balance_due, ot_payment_received` that, if found in the order, should be displayed but not enabled as inputs.

**Note:** Other observers might have previously added _**their**_ display-only fields, so an observer should check to see if the `$p2`value is an empty string before _**appending**_ its updates.  If the value is not '', then a leading ', ' should be added.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (n/a) |
| $p2 | (r/w) Contains a reference to the `$display_only_totals_list` string, initially set to an empty string. |

#### Status History Display Init

This notifier fires just prior to rendering the order's status-history table, giving an observer the opportunity to manipulate the table to re-arrange the order of each row's display and/or insert additional display fields.  The table's columns (left-to-right) will be displayed in the order specified in this table (first-to-last).

Each table element is an associative array (keyed on the _field name_ in the `orders_status_history` table), containing an array with the following recognized elements:

| Element 'name' | Description |
| ----- | ----- |
| `title` |	(Required) The title to be displayed in the table header for the data column.  Note that the 'title' can be blank, indicating that no title is associated with the database field and that the field is not displayed within the overall status table. |
| `show_function` | (Optional) Identifies the name of the function to be called to display the database value.  The function takes either 1 (the database value) or 2 (the database value and the field name), dpending on the value of the `include_field_name` element. If this element is not supplied, the value present in the database for this field is simply displayed. |
| `include_field_name` | (Optional) If a `show_function` is identified and this element is `(bool)true`, then the `show_function` takes two parameters, as identified above. |
| `align` | (Optional) Identifies the alignment to be applied when rendering the element, one of: 'center', 'right' or 'left' (the default). |

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) The `orders_id` being displayed. |
| $p2 | (r/w) Contains a reference to the `$table_elements` array, initially an array in the above format containing keys for the `date_added`, `customer_notified`, `orders_status_id`, `comments` and, if present in the database, `updated_by` fields. |

#### Additional OSH Content

This notifier fires just after rendering the **Comments** input field, enabling a watching observer to add additional status-history form fields.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) A copy of the current `$order` object. |
| $p2 | (r/w) Contains a reference to the `$additional_osh_content` array, initialized as an empty array.  The observer can add additional HTML content to be output; each array entry is rendered as a separate row on the screen. |

#### Additional JS

This notifier fires at the very end of the `edit_orders` page's rendering, allowing an observer to include additional jQuery/javascript handlers for the page.  Each filenamed specified must exist in the site's `/admin/includes/javascript` sub-directory with a `.js` extension for inclusion.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (n/a) |
| $p2 | (r/w) Contains a reference to the `$addl_js_files` string, initialized as an empty string.  The observer appends the `filename`(s) of additional `.js` files to be included as a comma-separated string.  Note that other observers might have previously included their additional files, so a leading ', ' should be included if the value is not empty when received. |
