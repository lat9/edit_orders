<?php
// -----
// Admin-level filename definitions for the Edit Orders plugin.
//
// Last updated: v5.0.0
//
define('FILENAME_EDIT_ORDERS', 'edit_orders');

// -----
// Set auto-loading for the storefront shopping-cart class; it's
// extended for use by the eoCart class, which is session-instantiated.
//
global $psr4Autoloader;
$psr4Autoloader->setClassFile('shoppingCart', DIR_FS_CATALOG . DIR_WS_CLASSES . 'shopping_cart.php');
