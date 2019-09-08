# Notifiers Watched by Edit Orders

Edit Order's admin-level observer "watches" for the following notifications, enabling integration with a base Zen Cart without core-file modifications:

| Notifier | Issued by | Description |
| ----- | ----- | ----- |
| NOTIFY_ADMIN_ORDERS_MENU_BUTTONS | orders.php | Enables _EO_ to insert its 'Edit' button at the top of the currently-selected order's sidebox display. |
| NOTIFY_ADMIN_ORDERS_MENU_BUTTONS_END | orders.php | Enables _EO_ to insert its 'Edit' button at the bottom of the currently-selected order's sidebox display. |
| NOTIFY_ADMIN_ORDERS_EDIT_BUTTONS | orders.php | Enables _EO_ to insert its 'Edit' button at the bottom of an order's detailed (full) display. |
| NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE | orders.php | Enables _EO_ to insert its 'Edit' icon as part of the orders' listing display. |
| NOTIFY_OT_SHIPPING_TAX_CALCS | ot_shipping.php | Used by _EO_ to override the order's shipping-tax calculations when an order is updated. |
